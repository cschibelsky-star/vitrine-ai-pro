from __future__ import annotations

import os
from typing import Any

import httpx

from server import mcp

BROKER_URL = os.getenv("OPS_BROKER_URL", "http://ops_broker:8770")
BROKER_TOKEN = os.getenv("OPS_BROKER_TOKEN", "")
TIMEOUT = float(os.getenv("OPS_REQUEST_TIMEOUT", "1200"))


def _post(path: str, payload: dict[str, Any] | None = None, params: dict[str, Any] | None = None) -> dict[str, Any]:
    headers = {"Authorization": f"Bearer {BROKER_TOKEN}"}
    with httpx.Client(timeout=TIMEOUT) as client:
        response = client.post(f"{BROKER_URL}{path}", json=payload, params=params, headers=headers)
    try:
        body = response.json()
    except Exception:
        body = {"raw": response.text}
    return {"status_code": response.status_code, "ok": response.is_success, "body": body}


@mcp.tool(annotations={"readOnlyHint": False, "destructiveHint": False})
def run_factory_command(command: str, arguments: list[str] | None = None, confirm: str = "") -> dict[str, Any]:
    """Executa somente comandos Factory/Comercial presentes na allowlist. Use confirm='EXECUTAR'."""
    return _post("/artisan", {"command": command, "arguments": arguments or [], "confirm": confirm})


@mcp.tool(annotations={"readOnlyHint": False, "destructiveHint": False})
def run_laravel_tests(confirm: str = "") -> dict[str, Any]:
    """Executa a suíte Laravel no container principal. Use confirm='EXECUTAR'."""
    return _post("/test", params={"confirm": confirm})


@mcp.tool(annotations={"readOnlyHint": False, "destructiveHint": False})
def clear_laravel_cache(confirm: str = "") -> dict[str, Any]:
    """Limpa caches Laravel pelo comando optimize:clear. Use confirm='EXECUTAR'."""
    return _post("/cache-clear", params={"confirm": confirm})


@mcp.tool(annotations={"readOnlyHint": False, "destructiveHint": True})
def restart_application_container(container: str, confirm: str = "") -> dict[str, Any]:
    """Reinicia somente containers da aplicação presentes na allowlist. Use confirm='EXECUTAR'."""
    return _post("/restart-container", {"container": container, "confirm": confirm})


@mcp.tool(annotations={"readOnlyHint": False, "destructiveHint": True})
def deploy_git_branch(branch: str, confirm: str = "") -> dict[str, Any]:
    """Implanta branch com backup, fast-forward, Composer, migrations e optimize. Recusa árvore Git suja. Use confirm='EXECUTAR'."""
    return _post("/deploy-branch", {"branch": branch, "confirm": confirm})


if __name__ == "__main__":
    host = os.getenv("MCP_HOST", "0.0.0.0")
    port = int(os.getenv("MCP_PORT", "8765"))
    mcp.run(transport="http", host=host, port=port)
