from __future__ import annotations

import json
import os
import re
import subprocess
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
    proc = subprocess.run(command, cwd=str(cwd or APP_ROOT), text=True, capture_output=True, timeout=TIMEOUT, check=False)
    return {"exit_code": proc.returncode, "stdout": proc.stdout[-50000:], "stderr": proc.stderr[-20000:]}


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
