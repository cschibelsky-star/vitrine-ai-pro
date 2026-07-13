from __future__ import annotations

import json
import os
import shlex
import subprocess
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import psutil
from fastmcp import FastMCP

APP_ROOT = Path(os.getenv("VITRINE_APP_ROOT", "/srv/factory/vitrine-ai-pro")).resolve()
MAX_OUTPUT = int(os.getenv("MCP_MAX_OUTPUT", "30000"))
COMMAND_TIMEOUT = int(os.getenv("MCP_COMMAND_TIMEOUT", "20"))

mcp = FastMCP(
    name="Vitrine IA Pro — VPS Operations Read Only",
    instructions=(
        "Conector operacional somente leitura da VPS da Vitrine IA Pro. "
        "Use as ferramentas para diagnosticar infraestrutura, Docker, Git, Laravel, filas e logs. "
        "Nunca tente alterar arquivos, executar deploy, reiniciar serviços ou expor segredos."
    ),
)


def _clip(text: str) -> str:
    return text if len(text) <= MAX_OUTPUT else text[:MAX_OUTPUT] + "\n[SAIDA TRUNCADA]"


def _run(command: list[str], cwd: Path | None = None) -> dict[str, Any]:
    result = subprocess.run(
        command,
        cwd=str(cwd or APP_ROOT),
        text=True,
        capture_output=True,
        timeout=COMMAND_TIMEOUT,
        check=False,
        env={**os.environ, "LC_ALL": "C.UTF-8"},
    )
    return {
        "command": " ".join(shlex.quote(part) for part in command),
        "exit_code": result.returncode,
        "stdout": _clip(result.stdout),
        "stderr": _clip(result.stderr),
    }


def _safe_log_path(relative_path: str) -> Path:
    candidate = (APP_ROOT / relative_path).resolve()
    allowed = (APP_ROOT / "storage" / "logs").resolve()
    if candidate != allowed and allowed not in candidate.parents:
        raise ValueError("Somente arquivos dentro de storage/logs podem ser lidos.")
    return candidate


@mcp.tool()
def system_health() -> dict[str, Any]:
    """Retorna CPU, memória, disco, uptime e horário da VPS."""
    memory = psutil.virtual_memory()
    disk = psutil.disk_usage("/")
    return {
        "checked_at": datetime.now(timezone.utc).isoformat(),
        "cpu_percent": psutil.cpu_percent(interval=0.5),
        "memory": {
            "total": memory.total,
            "available": memory.available,
            "percent": memory.percent,
        },
        "disk": {
            "total": disk.total,
            "used": disk.used,
            "free": disk.free,
            "percent": disk.percent,
        },
        "boot_time": datetime.fromtimestamp(psutil.boot_time(), tz=timezone.utc).isoformat(),
    }


@mcp.tool()
def docker_status() -> dict[str, Any]:
    """Lista containers Docker e o consumo resumido de espaço, sem executar mudanças."""
    containers = _run(["docker", "ps", "-a", "--format", "{{json .}}"], cwd=Path("/"))
    usage = _run(["docker", "system", "df"], cwd=Path("/"))
    return {"containers": containers, "storage": usage}


@mcp.tool()
def git_status() -> dict[str, Any]:
    """Compara o estado local do projeto com branches e remotos Git conhecidos."""
    return {
        "status": _run(["git", "status", "--short", "--branch"]),
        "head": _run(["git", "rev-parse", "HEAD"]),
        "remotes": _run(["git", "remote", "-v"]),
        "branches": _run(["git", "branch", "-avv"]),
        "recent_commits": _run(["git", "log", "--oneline", "--decorate", "-20"]),
        "untracked": _run(["git", "ls-files", "--others", "--exclude-standard"]),
    }


@mcp.tool()
def laravel_status() -> dict[str, Any]:
    """Executa comandos Laravel somente leitura dentro do container principal."""
    base = ["docker", "exec", "vitrine_app", "php", "artisan"]
    return {
        "about": _run(base + ["about"], cwd=Path("/")),
        "migrations": _run(base + ["migrate:status"], cwd=Path("/")),
        "schedule": _run(base + ["schedule:list"], cwd=Path("/")),
        "factory_commands": _run(base + ["list", "--raw"], cwd=Path("/")),
    }


@mcp.tool()
def queue_status() -> dict[str, Any]:
    """Mostra processos relacionados a filas e scheduler."""
    return {
        "processes": _run(
            ["ps", "-eo", "pid,ppid,user,etime,cmd"],
            cwd=Path("/"),
        ),
        "redis": _run(
            ["docker", "exec", "vitrine_redis", "redis-cli", "ping"],
            cwd=Path("/"),
        ),
    }


@mcp.tool()
def read_laravel_log(lines: int = 200, filename: str = "laravel.log") -> dict[str, Any]:
    """Lê as últimas linhas de um log Laravel permitido. Máximo de 1000 linhas."""
    safe_lines = max(1, min(int(lines), 1000))
    path = _safe_log_path(f"storage/logs/{filename}")
    if not path.exists() or not path.is_file():
        return {"found": False, "path": str(path)}
    result = _run(["tail", "-n", str(safe_lines), str(path)], cwd=APP_ROOT)
    return {"found": True, "path": str(path), "result": result}


@mcp.tool()
def factory_inventory() -> dict[str, Any]:
    """Inventaria componentes, comandos e diretórios conhecidos da Factory sem modificá-los."""
    commands = _run(
        [
            "docker",
            "exec",
            "vitrine_app",
            "sh",
            "-lc",
            "php artisan list --raw | grep '^factory:' || true",
        ],
        cwd=Path("/"),
    )
    directories = []
    for relative in [
        "app/Factory",
        "storage/app/factory/blueprints",
        "storage/app/factory/builds",
        "storage/app/factory/real-builds",
        "storage/app/factory/enterprise-builds",
        "storage/app/factory/history",
    ]:
        path = APP_ROOT / relative
        directories.append(
            {
                "path": str(path),
                "exists": path.exists(),
                "is_directory": path.is_dir(),
                "entries": len(list(path.iterdir())) if path.is_dir() else 0,
            }
        )
    return {"commands": commands, "directories": directories}


@mcp.tool()
def audit_snapshot() -> dict[str, Any]:
    """Gera um retrato consolidado e somente leitura da operação da VPS."""
    return {
        "system": system_health(),
        "docker": docker_status(),
        "git": git_status(),
        "laravel": laravel_status(),
        "factory": factory_inventory(),
    }


if __name__ == "__main__":
    host = os.getenv("MCP_HOST", "0.0.0.0")
    port = int(os.getenv("MCP_PORT", "8765"))
    mcp.run(transport="sse", host=host, port=port)
