from __future__ import annotations

import json
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

from server import APP_ROOT, _run, mcp

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
        "paths": ["services/vps-mcp-connector/supervisor.py"],
        "command_prefixes": [],
    },
    "n8n_catalog": {
        "purpose": "Catálogo controlado de workflows n8n",
        "paths": ["services/vps-mcp-connector/workflow_catalog.py"],
        "command_prefixes": [],
    },
}


def _artisan_commands() -> list[str]:
    result = _run(["docker", "exec", "vitrine_app", "php", "artisan", "list", "--raw"], cwd=Path("/"))
    if result.get("exit_code") != 0:
        return []
    return [line.strip().split()[0] for line in result.get("stdout", "").splitlines() if line.strip()]


def _path_state(relative: str) -> dict[str, Any]:
    path = (APP_ROOT / relative).resolve()
    exists = path.exists()
    return {
        "relative_path": relative,
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


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_inventory() -> dict[str, Any]:
    """Mapeia o que já existe antes de qualquer nova construção na Factory."""
    commands = _artisan_commands()
    components = {
        name: _component_state(name, definition, commands)
        for name, definition in KNOWN_COMPONENTS.items()
    }
    return {
        "checked_at": datetime.now(timezone.utc).isoformat(),
        "policy": "Construir somente se não existir; caso exista, reutilizar, centralizar ou evoluir.",
        "components": components,
        "artisan_command_count": len(commands),
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_decide(capability: str) -> dict[str, Any]:
    """Decide se uma capacidade deve ser reaproveitada, evoluída ou construída, sem executar alterações."""
    normalized = " ".join(capability.lower().replace("_", " ").replace("-", " ").split())
    inventory = factory_kernel_inventory()
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
        "next_action": {
            "reuse_or_evolve": "Abrir e evoluir o componente existente.",
            "build_after_validation": "Confirmar ausência em todos os ambientes e então construir na branch oficial.",
            "discovery_required": "Pesquisar código, comandos Artisan, workflows n8n, containers e builds.",
        }[decision],
    }


@mcp.tool(annotations={"readOnlyHint": True, "destructiveHint": False})
def factory_kernel_manifest() -> dict[str, Any]:
    """Retorna um manifesto operacional compacto para o Supervisor IA."""
    inventory = factory_kernel_inventory()
    return {
        "kernel_version": "1.0.0",
        "generated_at": inventory["checked_at"],
        "policy": inventory["policy"],
        "existing": [name for name, item in inventory["components"].items() if item["exists"]],
        "missing": [name for name, item in inventory["components"].items() if not item["exists"]],
        "inventory": inventory["components"],
    }
