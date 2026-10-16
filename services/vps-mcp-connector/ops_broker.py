from __future__ import annotations

import hashlib
import json
import os
import re
import shutil
import subprocess
import tarfile
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import httpx
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

APP_ROOT = Path(os.getenv("VITRINE_APP_ROOT", "/srv/factory/vitrine-ai-pro")).resolve()
LARAVEL_CONTAINER = os.getenv("LARAVEL_CONTAINER", "vitrine_app")
BROKER_TOKEN = os.getenv("OPS_BROKER_TOKEN", "")
AUDIT_LOG = Path(os.getenv("OPS_AUDIT_LOG", "/var/log/vitrine-ops/audit.jsonl"))
GIT_PRESERVATION_ROOT = Path(os.getenv("GIT_PRESERVATION_ROOT", "/srv/backups/vitrine-ai-pro/git-preservation")).resolve()
TIMEOUT = int(os.getenv("OPS_TIMEOUT", "900"))
N8N_BASE_URL = os.getenv("N8N_BASE_URL", "http://n8n:5678").rstrip("/")
N8N_WEBHOOK_TOKEN = os.getenv("N8N_WEBHOOK_TOKEN", "")
N8N_CATALOG_RAW = os.getenv("N8N_WORKFLOW_CATALOG_JSON", "{}").strip() or "{}"

app = FastAPI(title="Vitrine IA Pro Operations Broker", docs_url=None, redoc_url=None)

ALLOWED_FACTORY_COMMANDS = {
    "factory:health", "factory:sync", "factory:engine-test", "factory:smart-qa2",
    "factory:release-status", "factory:production-status", "factory:produce",
    "factory:produce-request", "factory:build-and-install", "factory:install-system",
    "factory:install-final", "factory:finish-project", "commercial:factory-status",
    "commercial:factory-intake",
}
ALLOWED_CONTAINERS = {"vitrine_app", "vitrine_web", "vitrine_app_hml", "vitrine_web_hml"}
SAFE_VALUE = re.compile(r"^[A-Za-z0-9_.:@/+=, -]{0,500}$")
SAFE_ALIAS = re.compile(r"^[a-z0-9][a-z0-9._-]{0,79}$")
SAFE_PATH = re.compile(r"^/[A-Za-z0-9_./-]{1,300}$")


class CommandRequest(BaseModel):
    command: str
    arguments: list[str] = Field(default_factory=list, max_length=20)
    confirm: str = ""


class ContainerRequest(BaseModel):
    container: str
    confirm: str = ""


class DeployRequest(BaseModel):
    branch: str
    confirm: str = ""


class PreserveGitRequest(BaseModel):
    confirm: str = ""


class N8NWorkflowRequest(BaseModel):
    alias: str
    payload: dict[str, Any] = Field(default_factory=dict)
    confirm: str = ""


def auth(authorization: str | None = Header(default=None)) -> None:
    if not BROKER_TOKEN or authorization != f"Bearer {BROKER_TOKEN}":
        raise HTTPException(status_code=401, detail="unauthorized")


def audit(action: str, payload: dict[str, Any], result: dict[str, Any]) -> None:
    AUDIT_LOG.parent.mkdir(parents=True, exist_ok=True)
    safe_payload = dict(payload)
    if "confirm" in safe_payload:
        safe_payload["confirm"] = "***"
    record = {"at": datetime.now(timezone.utc).isoformat(), "action": action, "payload": safe_payload, "result": result}
    with AUDIT_LOG.open("a", encoding="utf-8") as handle:
        handle.write(json.dumps(record, ensure_ascii=False) + "\n")


def run(command: list[str], cwd: Path | None = None) -> dict[str, Any]:
    try:
        proc = subprocess.run(
            command,
            cwd=str(cwd or APP_ROOT),
            text=True,
            capture_output=True,
            timeout=TIMEOUT,
            check=False,
        )
        return {"exit_code": proc.returncode, "stdout": proc.stdout[-50000:], "stderr": proc.stderr[-20000:]}
    except subprocess.TimeoutExpired as exc:
        return {"exit_code": 124, "stdout": exc.stdout or "", "stderr": "timeout"}
    except Exception as exc:
        return {"exit_code": 1, "stdout": "", "stderr": str(exc)}


def require_confirm(value: str) -> None:
    if value != "EXECUTAR":
        raise HTTPException(status_code=409, detail="confirmation_required")


def workflow_catalog() -> dict[str, dict[str, Any]]:
    try:
        parsed = json.loads(N8N_CATALOG_RAW)
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=500, detail=f"invalid_n8n_catalog:{exc}") from exc
    if not isinstance(parsed, dict):
        raise HTTPException(status_code=500, detail="invalid_n8n_catalog")
    return {str(name): item for name, item in parsed.items() if isinstance(item, dict)}


@app.get("/health")
def health() -> dict[str, Any]:
    return {"ok": True, "mode": "controlled-write", "n8n_catalog_count": len(workflow_catalog())}


@app.get("/inventory/artisan", dependencies=[Depends(auth)])
def inventory_artisan() -> dict[str, Any]:
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "list", "--raw"], Path("/"))
    commands = [
        line.strip().split()[0]
        for line in result.get("stdout", "").splitlines()
        if line.strip()
    ] if result.get("exit_code") == 0 else []
    response = {
        "ok": result.get("exit_code") == 0,
        "container": LARAVEL_CONTAINER,
        "commands": commands,
        "command_count": len(commands),
        "diagnostic": result,
    }
    audit("inventory_artisan", {}, {"ok": response["ok"], "command_count": response["command_count"]})
    return response


@app.get("/inventory/containers", dependencies=[Depends(auth)])
def inventory_containers() -> dict[str, Any]:
    result = run(["docker", "ps", "-a", "--format", "{{.Names}}|{{.Image}}|{{.Status}}"], Path("/"))
    containers: list[dict[str, str]] = []
    if result.get("exit_code") == 0:
        for line in result.get("stdout", "").splitlines():
            parts = line.split("|", 2)
            if len(parts) == 3:
                containers.append({"name": parts[0], "image": parts[1], "status": parts[2]})
    response = {
        "ok": result.get("exit_code") == 0,
        "containers": containers,
        "container_count": len(containers),
        "diagnostic": result,
    }
    audit("inventory_containers", {}, {"ok": response["ok"], "container_count": response["container_count"]})
    return response


@app.post("/artisan", dependencies=[Depends(auth)])
def artisan(req: CommandRequest) -> dict[str, Any]:
    require_confirm(req.confirm)
    if req.command not in ALLOWED_FACTORY_COMMANDS:
        raise HTTPException(status_code=403, detail="command_not_allowed")
    if any(not SAFE_VALUE.fullmatch(value) for value in req.arguments):
        raise HTTPException(status_code=422, detail="unsafe_argument")
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", req.command, *req.arguments], Path("/"))
    audit("artisan", req.model_dump(), result)
    return result


@app.post("/test", dependencies=[Depends(auth)])
def test(confirm: str) -> dict[str, Any]:
    require_confirm(confirm)
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "test"], Path("/"))
    audit("test", {"confirm": confirm}, result)
    return result


@app.post("/cache-clear", dependencies=[Depends(auth)])
def cache_clear(confirm: str) -> dict[str, Any]:
    require_confirm(confirm)
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "optimize:clear"], Path("/"))
    audit("cache_clear", {"confirm": confirm}, result)
    return result


@app.post("/restart-container", dependencies=[Depends(auth)])
def restart_container(req: ContainerRequest) -> dict[str, Any]:
    require_confirm(req.confirm)
    if req.container not in ALLOWED_CONTAINERS:
        raise HTTPException(status_code=403, detail="container_not_allowed")
    result = run(["docker", "restart", req.container], Path("/"))
    audit("restart_container", req.model_dump(), result)
    return result


def _sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for chunk in iter(lambda: handle.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


@app.post("/git/preserve", dependencies=[Depends(auth)])
def preserve_git_worktree(req: PreserveGitRequest) -> dict[str, Any]:
    """Preserva HEAD, mudanças rastreadas e não rastreadas sem alterar a árvore Git."""
    require_confirm(req.confirm)

    inside = run(["git", "rev-parse", "--is-inside-work-tree"])
    if inside["exit_code"] != 0 or inside["stdout"].strip() != "true":
        raise HTTPException(status_code=409, detail="app_root_not_git_repository")

    timestamp = datetime.now(timezone.utc).strftime("%Y%m%dT%H%M%SZ")
    head = run(["git", "rev-parse", "HEAD"])
    branch = run(["git", "branch", "--show-current"])
    status_before = run(["git", "status", "--porcelain=v1", "-z"])
    if any(item["exit_code"] != 0 for item in (head, branch, status_before)):
        raise HTTPException(status_code=500, detail="git_snapshot_failed")

    snapshot_id = f"{timestamp}-{head['stdout'].strip()[:12]}"
    final_dir = GIT_PRESERVATION_ROOT / snapshot_id
    temporary_dir = GIT_PRESERVATION_ROOT / f".{snapshot_id}.tmp"
    if final_dir.exists() or temporary_dir.exists():
        raise HTTPException(status_code=409, detail="snapshot_already_exists")

    temporary_dir.mkdir(parents=True, exist_ok=False)
    skipped: list[str] = []
    try:
        bundle_path = temporary_dir / "head.bundle"
        patch_path = temporary_dir / "tracked.patch"
        untracked_path = temporary_dir / "untracked.tar.gz"

        with bundle_path.open("wb") as output:
            bundle = subprocess.run(
                ["git", "bundle", "create", "-", "HEAD"],
                cwd=str(APP_ROOT),
                stdout=output,
                stderr=subprocess.PIPE,
                timeout=TIMEOUT,
                check=False,
            )
        if bundle.returncode != 0:
            raise RuntimeError(f"bundle_failed:{bundle.stderr.decode(errors='replace')[-2000:]}")

        with patch_path.open("wb") as output:
            patch = subprocess.run(
                ["git", "diff", "--binary", "HEAD", "--"],
                cwd=str(APP_ROOT),
                stdout=output,
                stderr=subprocess.PIPE,
                timeout=TIMEOUT,
                check=False,
            )
        if patch.returncode != 0:
            raise RuntimeError(f"patch_failed:{patch.stderr.decode(errors='replace')[-2000:]}")

        untracked = subprocess.run(
            ["git", "ls-files", "-z", "--others", "--exclude-standard"],
            cwd=str(APP_ROOT),
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            timeout=TIMEOUT,
            check=False,
        )
        if untracked.returncode != 0:
            raise RuntimeError(f"untracked_list_failed:{untracked.stderr.decode(errors='replace')[-2000:]}")

        names = [item.decode("utf-8", errors="surrogateescape") for item in untracked.stdout.split(b"\0") if item]
        with tarfile.open(untracked_path, "w:gz", dereference=False) as archive:
            for name in names:
                source = APP_ROOT / name
                try:
                    source.relative_to(APP_ROOT)
                except ValueError:
                    skipped.append(name)
                    continue
                if not source.exists() and not source.is_symlink():
                    skipped.append(name)
                    continue
                archive.add(source, arcname=name, recursive=True)

        status_after = run(["git", "status", "--porcelain=v1", "-z"])
        if status_after["exit_code"] != 0 or status_after["stdout"] != status_before["stdout"]:
            raise RuntimeError("working_tree_changed_during_preservation")

        manifest = {
            "snapshot_id": snapshot_id,
            "created_at": datetime.now(timezone.utc).isoformat(),
            "repository": str(APP_ROOT),
            "branch": branch["stdout"].strip() or "(detached HEAD)",
            "head": head["stdout"].strip(),
            "status_porcelain_sha256": hashlib.sha256(status_before["stdout"].encode()).hexdigest(),
            "artifacts": {
                "head.bundle": {"sha256": _sha256(bundle_path), "size": bundle_path.stat().st_size},
                "tracked.patch": {"sha256": _sha256(patch_path), "size": patch_path.stat().st_size},
                "untracked.tar.gz": {"sha256": _sha256(untracked_path), "size": untracked_path.stat().st_size},
            },
            "untracked_entries": len(names),
            "skipped_entries": skipped,
            "worktree_unchanged": True,
        }
        (temporary_dir / "manifest.json").write_text(
            json.dumps(manifest, ensure_ascii=False, indent=2) + "\n",
            encoding="utf-8",
        )
        temporary_dir.rename(final_dir)
    except Exception:
        shutil.rmtree(temporary_dir, ignore_errors=True)
        raise

    result = {
        "ok": True,
        "snapshot_id": snapshot_id,
        "path": str(final_dir),
        "branch": manifest["branch"],
        "head": manifest["head"],
        "artifacts": manifest["artifacts"],
        "untracked_entries": manifest["untracked_entries"],
        "skipped_entries": manifest["skipped_entries"],
        "worktree_unchanged": True,
    }
    audit("preserve_git_worktree", req.model_dump(), result)
    return result


@app.post("/deploy-branch", dependencies=[Depends(auth)])
def deploy_branch(req: DeployRequest) -> dict[str, Any]:
    require_confirm(req.confirm)
    if not re.fullmatch(r"[A-Za-z0-9._/-]{1,120}", req.branch):
        raise HTTPException(status_code=422, detail="invalid_branch")
    if run(["git", "status", "--porcelain"])["stdout"].strip():
        raise HTTPException(status_code=409, detail="working_tree_not_clean")
    backup = f"ops-backup-{datetime.now(timezone.utc).strftime('%Y%m%d-%H%M%S')}"
    steps = {
        "backup_branch": run(["git", "branch", backup]),
        "fetch": run(["git", "fetch", "origin", req.branch]),
        "checkout": run(["git", "checkout", req.branch]),
        "pull": run(["git", "pull", "--ff-only", "origin", req.branch]),
        "composer": run(["docker", "exec", LARAVEL_CONTAINER, "composer", "install", "--no-dev", "--prefer-dist", "--no-interaction", "--optimize-autoloader"], Path("/")),
        "migrate": run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "migrate", "--force"], Path("/")),
        "cache": run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "optimize"], Path("/")),
    }
    result = {"backup_branch": backup, "steps": steps, "ok": all(v["exit_code"] == 0 for v in steps.values())}
    audit("deploy_branch", req.model_dump(), result)
    return result


@app.post("/n8n/workflow", dependencies=[Depends(auth)])
def n8n_workflow(req: N8NWorkflowRequest) -> dict[str, Any]:
    require_confirm(req.confirm)
    if not SAFE_ALIAS.fullmatch(req.alias):
        raise HTTPException(status_code=422, detail="invalid_workflow_alias")
    item = workflow_catalog().get(req.alias)
    if not item:
        raise HTTPException(status_code=404, detail="workflow_not_cataloged")
    if not bool(item.get("enabled", False)):
        raise HTTPException(status_code=403, detail="workflow_disabled")
    method = str(item.get("method", "POST")).upper()
    path = str(item.get("path", ""))
    if method != "POST" or not SAFE_PATH.fullmatch(path) or ".." in path:
        raise HTTPException(status_code=422, detail="unsafe_workflow_definition")
    headers = {"Content-Type": "application/json"}
    if N8N_WEBHOOK_TOKEN:
        headers["X-Vitrine-Webhook-Token"] = N8N_WEBHOOK_TOKEN
    try:
        with httpx.Client(timeout=TIMEOUT, follow_redirects=False) as client:
            response = client.post(f"{N8N_BASE_URL}{path}", json=req.payload, headers=headers)
        result = {
            "ok": response.is_success,
            "alias": req.alias,
            "status_code": response.status_code,
            "response": response.text[-20000:],
        }
    except Exception as exc:
        result = {"ok": False, "alias": req.alias, "error": str(exc)}
    audit("n8n_workflow", req.model_dump(), result)
    return result
