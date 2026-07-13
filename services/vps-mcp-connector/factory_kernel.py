from __future__ import annotations

from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from server import APP_ROOT, _run, mcp

RUNTIME_ROOT = Path("/app")

KNOWN_COMPONENTS: dict[str, dict[str, Any]] = {
    "factory_engine": {
        "purpose": "Produção, instalação e finalização de sistemas",
        "paths": ["app/Factory", "storage/app/factory"],
        "command_prefixes": ["factory:"],
    },
    "commercial_intake": {
        "purpose": "Entrada comercial e transformação de demanda em pedido",
        "paths": ["storage/app/factory/commercial-intake"],
        "command_prefixes": ["commercial:factory-"],
    },
    "build_registry": {
        "purpose": "Histórico e artefatos de builds",
        "paths": [
            "storage/app/factory/builds",
            "storage/app/factory/real-builds",
            "storage/app/factory/enterprise-builds",
            "storage/app/factory/history",
        ],
        "command_prefixes": ["factory:release-", "factory:production-"],
    },
    "provisioning": {
        "purpose": "Provisionamento e homologação de sites/produtos",
        "paths": ["app/Console/Commands", "app/Services"],
        "command_prefixes": ["site:", "factory:install-"],
    },
    "supervisor": {
        "purpose": "Orquestração operacional da VPS, Factory e n8n",
        "paths": ["runtime:supervisor.py"],
        "command_prefixes": [],
    },
    "n8n_catalog": {
        "purpose": "Catálogo controlado de workflows n8n",
        "paths": ["runtime:workflow_catalog.py"],
        "command_prefixes": [],
    },
    "factory_kernel": {
        "purpose": "Descoberta, inventário e decisão antes de construir",
        "paths": ["runtime:factory_kernel.py"],
        "command_prefixes": [],
    },
}


def _artisan_commands() -> list[str]:
    result = _run(["docker", "exec", "vitrine_app", "php", "artisan", "list", "--raw"], cwd=Path("/"))
    if result.get("exit_code") != 0:
        return []
    return [line.strip().split()[0] for line in result.get("stdout", "").splitlines() if line.strip()]


def _resolve_path(reference: str) -> tuple[Path, str]:
    if reference.startswith("runtime:"):
        relative = reference.removeprefix("runtime:")
        return (RUNTIME_ROOT / relative).resolve(), reference
    candidate = Path(reference)
    if candidate.is_absolute():
        return candidate.resolve(), reference
    return (APP_ROOT / reference).resolve(), reference


def _path_state(reference: str) -> dict[str, Any]:
    path, label = _resolve_path(reference)
    exists = path.exists()
    return {
        "path_reference": label,
        "resolved_path": str(path),
        "exists": exists,
        "kind": "directory" if path.is_dir() else "file" if path.is_file() else "missing",
        "entries": len(list(path.iterdir())) if path.is_dir() else None,
    }


def _component_state(name: str, definition: dict[str, Any], commands: list[str]) -> dict[str, Any]:
    paths = [_path_state(item) for item in definition["paths"]]
    matched_commands = sorted(
        command
        for command in commands
        if any(command.startswith(prefix) for prefix in definition["command_prefixes"])
    )
    exists = any(item["exists"] for item in paths) or bool(matched_commands)
    return {
        "name": name,
        "purpose": definition["purpose"],
        "exists": exists,
        "paths": paths,
        "commands": matched_commands,
        "decision": "reuse_or_evolve" if exists else "eligible_to_build",
    }


def _repository_inventory() -> list[dict[str, Any]]:
    result = _run(
        ["sh", "-lc", "find /srv -maxdepth 4 -type d -name .git -printf '%h\\n' 2>/dev/null | sort -u"],
        cwd=Path("/"),
    )
    repositories: list[dict[str, Any]] = []
    for raw_path in result.get("stdout", "").splitlines():
        repo_path = raw_path.strip()
        if not repo_path:
            continue
        branch = _run(["git", "-C", repo_path, "branch", "--show-current"], cwd=Path("/"))
        remote = _run(["git", "-C", repo_path, "remote", "get-url", "origin"], cwd=Path("/"))
        status = _run(["git", "-C", repo_path, "status", "--porcelain"], cwd=Path("/"))
        repositories.append(
            {
                "path": repo_path,
                "branch": branch.get("stdout", "").strip(),
                "origin": remote.get("stdout", "").strip(),
                "dirty": bool(status.get("stdout", "").strip()),
            }
        )
    return repositories


def _container_inventory() -> list[dict[str, Any]]:
    result = _run(
        ["docker", "ps", "-a", "--format", "{{.Names}}|{{.Image}}|{{.Status}}"],
        cwd=Path("/"),
    )
    containers: list[dict[str, Any]] = []
    for line in result.get("stdout", "").splitlines():
        parts = line.split("|", 2)
        if len(parts) == 3:
            containers.append({"name": parts[0], "image": parts[1], "status": parts[2]})
    return containers


def _duplicate_signals(repositories: list[dict[str, Any]], containers: list[dict[str, Any]]) -> list[dict[str, Any]]:
    signals: list[dict[str, Any]] = []
    by_origin: dict[str, list[str]] = {}
    for repo in repositories:
        origin = repo.get("origin", "")
        if origin:
            by_origin.setdefault(origin, []).append(repo["path"])
    for origin, paths in by_origin.items():
        if len(paths) > 1:
            signals.append({"type": "duplicate_repository_origin", "origin": origin, "paths": paths})

    factory_like = [item["name"] for item in containers if "factory" in item["name"].lower()]
    if len(factory_like) > 1:
        signals.append({"type": "multiple_factory_containers", "containers": factory_like})
    return signals


def factory_kernel_inventory_impl() -> dict[str, Any]:
    """Implementação interna chamável diretamente e também pelo wrapper MCP."""
    commands = _artisan_commands()
    components = {
        name: _component_state(name, definition, commands)
        for name, definition in KNOWN_COMPONENTS.items()
    }
    repositories = _repository_inventory()
    containers = _container_inventory()
    duplicate_signals = _duplicate_signals(repositories, containers)
    return {
        "checked_at": datetime.now(timezone.utc).isoformat(),
        "kernel_version": "1.1.2",
        "policy": "Construir somente se não existir; caso exista, reutilizar, centralizar ou evoluir.",
        "components": components,
        "artisan_command_count": len(commands),
        "repositories": repositories,
        "containers": containers,
        "duplicate_signals": duplicate_signals,
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_inventory() -> dict[str, Any]:
    """Mapeia o que já existe antes de qualquer nova construção na Factory."""
    return factory_kernel_inventory_impl()


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_decide(capability: str) -> dict[str, Any]:
    """Decide se uma capacidade deve ser reaproveitada, evoluída ou construída, sem executar alterações."""
    normalized = " ".join(capability.lower().replace("_", " ").replace("-", " ").split())
    inventory = factory_kernel_inventory_impl()
    matches: list[dict[str, Any]] = []

    for name, component in inventory["components"].items():
        haystack = " ".join([name.replace("_", " "), component["purpose"].lower()])
        score = sum(1 for token in normalized.split() if token and token in haystack)
        if score:
            matches.append({"score": score, **component})

    matches.sort(key=lambda item: item["score"], reverse=True)
    existing = [item for item in matches if item["exists"]]

    if existing:
        decision = "reuse_or_evolve"
        reason = "Foi localizada capacidade existente com finalidade compatível. Não criar duplicação."
    elif matches:
        decision = "build_after_validation"
        reason = "A capacidade é conhecida, mas não foi detectada nesta instalação. Validar VPS, GitHub e n8n antes de construir."
    else:
        decision = "discovery_required"
        reason = "Não há correspondência suficiente. Fazer auditoria ampliada antes de autorizar nova construção."

    return {
        "capability": capability,
        "decision": decision,
        "reason": reason,
        "matches": matches[:5],
        "duplicate_signals": inventory["duplicate_signals"],
        "next_action": {
            "reuse_or_evolve": "Abrir e evoluir o componente existente.",
            "build_after_validation": "Confirmar ausência em todos os ambientes e então construir na branch oficial.",
            "discovery_required": "Pesquisar código, comandos Artisan, workflows n8n, containers e builds.",
        }[decision],
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_manifest() -> dict[str, Any]:
    """Retorna um manifesto operacional compacto para o Supervisor IA."""
    inventory = factory_kernel_inventory_impl()
    return {
        "kernel_version": inventory["kernel_version"],
        "generated_at": inventory["checked_at"],
        "policy": inventory["policy"],
        "existing": [name for name, item in inventory["components"].items() if item["exists"]],
        "missing": [name for name, item in inventory["components"].items() if not item["exists"]],
        "duplicate_signals": inventory["duplicate_signals"],
        "inventory": inventory["components"],
    }
