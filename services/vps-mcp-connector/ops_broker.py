from __future__ import annotations

import base64
import json
import os
import re
import shutil
import subprocess
import zipfile
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import httpx
from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

APP_ROOT = Path(os.getenv("VITRINE_APP_ROOT", "/srv/factory/vitrine-ai-pro")).resolve()
PROJECTS_ROOT = Path(os.getenv("PROJECTS_ROOT", "/srv/projects")).resolve()
NGINX_CONF_ROOT = Path(os.getenv("NGINX_CONF_ROOT", "/srv/vitrine/docker/nginx/conf.d")).resolve()
LARAVEL_CONTAINER = os.getenv("LARAVEL_CONTAINER", "vitrine_app")
NGINX_CONTAINER = os.getenv("NGINX_CONTAINER", "vitrine_nginx")
BROKER_TOKEN = os.getenv("OPS_BROKER_TOKEN", "")
AUDIT_LOG = Path(os.getenv("OPS_AUDIT_LOG", "/var/log/vitrine-ops/audit.jsonl"))
TIMEOUT = int(os.getenv("OPS_TIMEOUT", "1200"))
MAX_ARCHIVE_BYTES = int(os.getenv("PROJECT_MAX_ARCHIVE_BYTES", str(25 * 1024 * 1024)))
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
SAFE_PROJECT = re.compile(r"^[a-z0-9][a-z0-9-]{1,62}[a-z0-9]$")
SAFE_DOMAIN = re.compile(r"^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$")


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


class N8NWorkflowRequest(BaseModel):
    alias: str
    payload: dict[str, Any] = Field(default_factory=dict)
    confirm: str = ""


class ProjectPublishArchiveRequest(BaseModel):
    project: str
    archive_base64: str = Field(min_length=4)
    domain: str | None = None
    environment: str = "homolog"
    confirm: str = ""


def auth(authorization: str | None = Header(default=None)) -> None:
    if not BROKER_TOKEN or authorization != f"Bearer {BROKER_TOKEN}":
        raise HTTPException(status_code=401, detail="unauthorized")


def audit(action: str, payload: dict[str, Any], result: dict[str, Any]) -> None:
    AUDIT_LOG.parent.mkdir(parents=True, exist_ok=True)
    safe_payload = dict(payload)
    if "confirm" in safe_payload:
        safe_payload["confirm"] = "***"
    if "archive_base64" in safe_payload:
        safe_payload["archive_base64"] = f"<redacted:{len(str(payload.get('archive_base64', '')))} chars>"
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


def _safe_extract_zip(archive_path: Path, destination: Path) -> None:
    with zipfile.ZipFile(archive_path) as archive:
        members = archive.infolist()
        total_uncompressed = sum(item.file_size for item in members)
        if total_uncompressed > MAX_ARCHIVE_BYTES * 8:
            raise HTTPException(status_code=413, detail="archive_uncompressed_too_large")
        for item in members:
            member_path = (destination / item.filename).resolve()
            if destination not in member_path.parents and member_path != destination:
                raise HTTPException(status_code=422, detail="unsafe_archive_path")
            if item.is_dir():
                continue
            if item.external_attr >> 16 & 0o170000 == 0o120000:
                raise HTTPException(status_code=422, detail="archive_symlink_not_allowed")
        archive.extractall(destination)


def _project_source_root(extract_root: Path) -> Path:
    entries = [item for item in extract_root.iterdir() if item.name not in {"__MACOSX", ".DS_Store"}]
    if len(entries) == 1 and entries[0].is_dir():
        return entries[0]
    return extract_root


def _write_next_dockerfile(project_dir: Path) -> None:
    dockerfile = project_dir / "Dockerfile"
    if dockerfile.exists():
        return
    dockerfile.write_text(
        """FROM node:22-alpine AS deps\nWORKDIR /app\nCOPY package*.json ./\nRUN if [ -f package-lock.json ]; then npm ci; else npm install; fi\n\nFROM node:22-alpine AS builder\nWORKDIR /app\nCOPY --from=deps /app/node_modules ./node_modules\nCOPY . .\nENV NEXT_TELEMETRY_DISABLED=1\nRUN npm run build\n\nFROM node:22-alpine AS runner\nWORKDIR /app\nENV NODE_ENV=production\nENV NEXT_TELEMETRY_DISABLED=1\nRUN addgroup --system --gid 1001 nodejs && adduser --system --uid 1001 nextjs\nCOPY --from=builder /app/public ./public\nCOPY --from=builder --chown=nextjs:nodejs /app/.next/standalone ./\nCOPY --from=builder --chown=nextjs:nodejs /app/.next/static ./.next/static\nUSER nextjs\nEXPOSE 3000\nENV PORT=3000\nENV HOSTNAME=0.0.0.0\nCMD [\"node\", \"server.js\"]\n""",
        encoding="utf-8",
    )


def _write_compose(project_dir: Path, project: str) -> Path:
    compose_path = project_dir / "compose.vitrine.yml"
    compose_path.write_text(
        f"""services:\n  app:\n    build:\n      context: .\n      dockerfile: Dockerfile\n    container_name: {project}_app\n    restart: unless-stopped\n    environment:\n      NODE_ENV: production\n      PORT: 3000\n      HOSTNAME: 0.0.0.0\n    networks:\n      - vitrine_net\n    healthcheck:\n      test: [\"CMD\", \"node\", \"-e\", \"fetch('http://127.0.0.1:3000/').then(r=>process.exit(r.ok?0:1)).catch(()=>process.exit(1))\"]\n      interval: 15s\n      timeout: 5s\n      retries: 8\n      start_period: 20s\n\nnetworks:\n  vitrine_net:\n    external: true\n""",
        encoding="utf-8",
    )
    return compose_path


def _write_nginx_conf(project: str, domain: str) -> Path:
    NGINX_CONF_ROOT.mkdir(parents=True, exist_ok=True)
    conf_path = NGINX_CONF_ROOT / f"{project}.conf"
    conf_path.write_text(
        f"""server {{\n    listen 80;\n    server_name {domain};\n\n    location / {{\n        proxy_pass http://{project}_app:3000;\n        proxy_http_version 1.1;\n        proxy_set_header Host $host;\n        proxy_set_header X-Real-IP $remote_addr;\n        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;\n        proxy_set_header X-Forwarded-Proto $scheme;\n        proxy_set_header Upgrade $http_upgrade;\n        proxy_set_header Connection \"upgrade\";\n    }}\n}}\n""",
        encoding="utf-8",
    )
    return conf_path


@app.get("/health")
def health() -> dict[str, Any]:
    return {
        "ok": True,
        "mode": "controlled-write",
        "n8n_catalog_count": len(workflow_catalog()),
        "project_publish_archive": True,
    }


@app.get("/inventory/artisan", dependencies=[Depends(auth)])
def inventory_artisan() -> dict[str, Any]:
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "list", "--raw"], Path("/"))
    commands = [line.strip().split()[0] for line in result.get("stdout", "").splitlines() if line.strip()] if result.get("exit_code") == 0 else []
    response = {"ok": result.get("exit_code") == 0, "container": LARAVEL_CONTAINER, "commands": commands, "command_count": len(commands), "diagnostic": result}
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
    response = {"ok": result.get("exit_code") == 0, "containers": containers, "container_count": len(containers), "diagnostic": result}
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


@app.post("/project/publish-archive", dependencies=[Depends(auth)])
def project_publish_archive(req: ProjectPublishArchiveRequest) -> dict[str, Any]:
    require_confirm(req.confirm)
    if not SAFE_PROJECT.fullmatch(req.project):
        raise HTTPException(status_code=422, detail="invalid_project")
    if req.environment != "homolog":
        raise HTTPException(status_code=422, detail="only_homolog_environment_allowed")
    domain = req.domain or f"{req.project}.hml.vitrineiapro.com.br"
    if not SAFE_DOMAIN.fullmatch(domain):
        raise HTTPException(status_code=422, detail="invalid_domain")

    try:
        archive_bytes = base64.b64decode(req.archive_base64, validate=True)
    except Exception as exc:
        raise HTTPException(status_code=422, detail="invalid_archive_base64") from exc
    if len(archive_bytes) > MAX_ARCHIVE_BYTES:
        raise HTTPException(status_code=413, detail="archive_too_large")
    if not archive_bytes.startswith(b"PK"):
        raise HTTPException(status_code=422, detail="archive_must_be_zip")

    project_dir = PROJECTS_ROOT / req.project
    staging_dir = PROJECTS_ROOT / ".staging" / f"{req.project}-{datetime.now(timezone.utc).strftime('%Y%m%d%H%M%S')}"
    backup_dir: Path | None = None
    archive_path = staging_dir / "project.zip"
    extract_root = staging_dir / "extract"
    steps: dict[str, Any] = {}

    try:
        staging_dir.mkdir(parents=True, exist_ok=False)
        extract_root.mkdir()
        archive_path.write_bytes(archive_bytes)
        _safe_extract_zip(archive_path, extract_root)
        source_root = _project_source_root(extract_root)
        if not (source_root / "package.json").is_file():
            raise HTTPException(status_code=422, detail="nextjs_package_json_not_found")

        package = json.loads((source_root / "package.json").read_text(encoding="utf-8"))
        dependencies = {**package.get("dependencies", {}), **package.get("devDependencies", {})}
        if "next" not in dependencies:
            raise HTTPException(status_code=422, detail="only_nextjs_archive_supported")

        if project_dir.exists():
            backup_dir = PROJECTS_ROOT / ".backups" / f"{req.project}-{datetime.now(timezone.utc).strftime('%Y%m%d%H%M%S')}"
            backup_dir.parent.mkdir(parents=True, exist_ok=True)
            shutil.move(str(project_dir), str(backup_dir))
        shutil.copytree(source_root, project_dir)

        _write_next_dockerfile(project_dir)
        compose_path = _write_compose(project_dir, req.project)
        _write_nginx_conf(req.project, domain)

        steps["docker_build_up"] = run(["docker", "compose", "-f", str(compose_path), "up", "-d", "--build", "--remove-orphans"], project_dir)
        if steps["docker_build_up"]["exit_code"] != 0:
            raise RuntimeError("docker_build_failed")

        steps["nginx_test"] = run(["docker", "exec", NGINX_CONTAINER, "nginx", "-t"], Path("/"))
        if steps["nginx_test"]["exit_code"] != 0:
            raise RuntimeError("nginx_test_failed")
        steps["nginx_reload"] = run(["docker", "exec", NGINX_CONTAINER, "nginx", "-s", "reload"], Path("/"))
        if steps["nginx_reload"]["exit_code"] != 0:
            raise RuntimeError("nginx_reload_failed")

        health_url = f"http://{req.project}_app:3000/"
        health: dict[str, Any]
        try:
            with httpx.Client(timeout=15, follow_redirects=True) as client:
                response = client.get(health_url)
            health = {"ok": response.is_success, "status_code": response.status_code, "url": health_url}
        except Exception as exc:
            health = {"ok": False, "url": health_url, "error": str(exc)}
        if not health["ok"]:
            raise RuntimeError("health_check_failed")

        result = {
            "ok": True,
            "project": req.project,
            "environment": req.environment,
            "domain": domain,
            "url": f"http://{domain}",
            "container": f"{req.project}_app",
            "health": health,
            "backup": str(backup_dir) if backup_dir else None,
            "steps": steps,
        }
        audit("project_publish_archive", req.model_dump(), result)
        return result
    except HTTPException:
        raise
    except Exception as exc:
        rollback: dict[str, Any] = {"attempted": False}
        if backup_dir and backup_dir.exists():
            rollback["attempted"] = True
            if project_dir.exists():
                shutil.rmtree(project_dir, ignore_errors=True)
            shutil.move(str(backup_dir), str(project_dir))
            rollback["restored"] = True
        result = {"ok": False, "project": req.project, "error": str(exc), "steps": steps, "rollback": rollback}
        audit("project_publish_archive", req.model_dump(), result)
        raise HTTPException(status_code=500, detail=result) from exc
    finally:
        shutil.rmtree(staging_dir, ignore_errors=True)


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
        result = {"ok": response.is_success, "alias": req.alias, "status_code": response.status_code, "response": response.text[-20000:]}
    except Exception as exc:
        result = {"ok": False, "alias": req.alias, "error": str(exc)}
    audit("n8n_workflow", req.model_dump(), result)
    return result
