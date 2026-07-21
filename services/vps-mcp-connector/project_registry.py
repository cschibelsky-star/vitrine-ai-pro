from __future__ import annotations

import json
import os
from pathlib import Path
from typing import Any

from server import _run, mcp

_DEFAULT_PROJECTS: dict[str, dict[str, Any]] = {
    "vitrine-ai-pro": {
        "name": "Vitrine IA Pro Factory",
        "root": "/srv/factory/vitrine-ai-pro",
        "type": "laravel",
        "containers": ["vitrine_app", "vitrine_web"],
        "redis_container": "vitrine_redis",
        "domains": ["painel.vitrineiapro.com.br"],
    },
    "vitrine-ai-social-enterprise": {
        "name": "Vitrine IA Social Enterprise",
        "root": "/srv/apps/vitrine-ai-social-enterprise",
        "type": "laravel",
        "containers": ["studio_app", "studio_web", "studio_worker", "studio_scheduler"],
        "domains": [],
    },
    "vps-mcp-connector": {
        "name": "VPS MCP Connector",
        "root": "/srv/connectors/vitrine-vps-mcp",
        "type": "python-mcp",
        "containers": ["vitrine_vps_mcp_connector", "vitrine_mcp_ops_broker", "vitrine_mcp_docker_proxy"],
        "domains": [],
    },
}


def _load_projects() -> dict[str, dict[str, Any]]:
    projects = dict(_DEFAULT_PROJECTS)
    raw = os.getenv("VPS_PROJECT_REGISTRY_JSON", "").strip()
    if not raw:
        return projects
    try:
        custom = json.loads(raw)
    except json.JSONDecodeError:
        return projects
    if not isinstance(custom, dict):
        return projects
    for slug, definition in custom.items():
        if isinstance(slug, str) and isinstance(definition, dict):
            projects[slug] = definition
    return projects


PROJECTS = _load_projects()


def resolve_project(project_slug: str) -> tuple[str, dict[str, Any], Path]:
    slug = " ".join(project_slug.strip().split())
    if slug not in PROJECTS:
        raise ValueError(f"Projeto não cadastrado: {slug}")
    definition = PROJECTS[slug]
    root = Path(str(definition.get("root", ""))).resolve()
    allowed_roots = [Path(str(item.get("root", ""))).resolve() for item in PROJECTS.values()]
    if root not in allowed_roots:
        raise ValueError("Diretório do projeto fora da allowlist.")
    return slug, definition, root


def _container_state(container: str) -> dict[str, Any]:
    result = _run(["docker", "inspect", "--format", "{{json .State}}", container], cwd=Path("/"))
    return {"container": container, "result": result}


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def projects_catalog() -> dict[str, Any]:
    """Lista projetos explicitamente cadastrados no Supervisor Multi-Projetos."""
    items = []
    for slug, definition in sorted(PROJECTS.items()):
        root = Path(str(definition.get("root", ""))).resolve()
        items.append({
            "slug": slug,
            "name": definition.get("name", slug),
            "type": definition.get("type", "unknown"),
            "root": str(root),
            "root_exists": root.exists(),
            "containers": definition.get("containers", []),
            "domains": definition.get("domains", []),
        })
    return {"count": len(items), "projects": items}


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def project_status(project_slug: str) -> dict[str, Any]:
    """Retorna inventário seguro de um projeto cadastrado, selecionado por slug."""
    slug, definition, root = resolve_project(project_slug)
    return {
        "slug": slug,
        "name": definition.get("name", slug),
        "type": definition.get("type", "unknown"),
        "root": str(root),
        "root_exists": root.exists(),
        "git_repository": (root / ".git").exists(),
        "containers": [_container_state(name) for name in definition.get("containers", [])],
        "domains": definition.get("domains", []),
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def project_git_status(project_slug: str) -> dict[str, Any]:
    """Consulta Git de um projeto cadastrado sem aceitar caminhos arbitrários."""
    slug, definition, root = resolve_project(project_slug)
    if not root.exists():
        return {"slug": slug, "found": False, "root": str(root)}
    if not (root / ".git").exists():
        return {"slug": slug, "found": True, "git_repository": False, "root": str(root)}
    return {
        "slug": slug,
        "name": definition.get("name", slug),
        "root": str(root),
        "status": _run(["git", "status", "--short", "--branch"], cwd=root),
        "head": _run(["git", "rev-parse", "HEAD"], cwd=root),
        "remotes": _run(["git", "remote", "-v"], cwd=root),
        "recent_commits": _run(["git", "log", "--oneline", "--decorate", "-10"], cwd=root),
        "diff_stat": _run(["git", "diff", "--stat"], cwd=root),
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def project_log_tail(project_slug: str, relative_log: str = "storage/logs/laravel.log", lines: int = 200) -> dict[str, Any]:
    """Lê um log permitido dentro do projeto cadastrado. Não aceita caminhos absolutos."""
    slug, definition, root = resolve_project(project_slug)
    if Path(relative_log).is_absolute() or ".." in Path(relative_log).parts:
        raise ValueError("Caminho de log inválido.")
    allowed_prefixes = ("storage/logs/", "logs/")
    normalized = relative_log.replace("\\", "/")
    if not normalized.startswith(allowed_prefixes):
        raise ValueError("Somente logs em storage/logs ou logs podem ser lidos.")
    path = (root / relative_log).resolve()
    if root != path and root not in path.parents:
        raise ValueError("Log fora do projeto cadastrado.")
    safe_lines = max(1, min(int(lines), 1000))
    if not path.exists() or not path.is_file():
        return {"slug": slug, "found": False, "path": str(path)}
    return {
        "slug": slug,
        "name": definition.get("name", slug),
        "found": True,
        "path": str(path),
        "result": _run(["tail", "-n", str(safe_lines), str(path)], cwd=root),
    }
