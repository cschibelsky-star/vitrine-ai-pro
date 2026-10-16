from __future__ import annotations

import json
import os
import re
import subprocess
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

APP_ROOT = Path(os.getenv("VITRINE_APP_ROOT", "/srv/factory/vitrine-ai-pro")).resolve()
LARAVEL_CONTAINER = os.getenv("LARAVEL_CONTAINER", "vitrine_app")
BROKER_TOKEN = os.getenv("OPS_BROKER_TOKEN", "")
AUDIT_LOG = Path(os.getenv("OPS_AUDIT_LOG", "/var/log/vitrine-ops/audit.jsonl"))
TIMEOUT = int(os.getenv("OPS_TIMEOUT", "900"))

app = FastAPI(title="Vitrine IA Pro Operations Broker", docs_url=None, redoc_url=None)

ALLOWED_FACTORY_COMMANDS = {
    "factory:health",
    "factory:sync",
    "factory:engine-test",
    "factory:smart-qa2",
    "factory:release-status",
    "factory:production-status",
    "factory:produce",
    "factory:produce-request",
    "factory:build-and-install",
    "factory:install-system",
    "factory:install-final",
    "factory:finish-project",
    "commercial:factory-status",
    "commercial:factory-intake",
}
ALLOWED_CONTAINERS = {"vitrine_app", "vitrine_web", "vitrine_app_hml", "vitrine_web_hml"}
SAFE_VALUE = re.compile(r"^[A-Za-z0-9_.:@/+=, -]{0,500}$")

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


def auth(authorization: str | None = Header(default=None)) -> None:
    if not BROKER_TOKEN or authorization != f"Bearer {BROKER_TOKEN}":
        raise HTTPException(status_code=401, detail="unauthorized")


def audit(action: str, payload: dict[str, Any], result: dict[str, Any]) -> None:
    AUDIT_LOG.parent.mkdir(parents=True, exist_ok=True)
    record = {"at": datetime.now(timezone.utc).isoformat(), "action": action, "payload": payload, "result": result}
    with AUDIT_LOG.open("a", encoding="utf-8") as handle:
        handle.write(json.dumps(record, ensure_ascii=False) + "\n")


def run(command: list[str], cwd: Path | None = None) -> dict[str, Any]:
    proc = subprocess.run(command, cwd=str(cwd or APP_ROOT), text=True, capture_output=True, timeout=TIMEOUT, check=False)
    return {"exit_code": proc.returncode, "stdout": proc.stdout[-50000:], "stderr": proc.stderr[-20000:]}


def require_confirm(value: str) -> None:
    if value != "EXECUTAR":
        raise HTTPException(status_code=409, detail="confirmation_required")

@app.get("/health")
def health() -> dict[str, Any]:
    return {"ok": True, "mode": "controlled-write"}

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
    audit("test", {"confirm": "***"}, result)
    return result

@app.post("/cache-clear", dependencies=[Depends(auth)])
def cache_clear(confirm: str) -> dict[str, Any]:
    require_confirm(confirm)
    result = run(["docker", "exec", LARAVEL_CONTAINER, "php", "artisan", "optimize:clear"], Path("/"))
    audit("cache_clear", {"confirm": "***"}, result)
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
