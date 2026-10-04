from __future__ import annotations

import os
from datetime import datetime, timezone
from pathlib import Path
from typing import Any

import httpx

from server import APP_ROOT, _run, mcp

BROKER_URL = os.getenv("OPS_BROKER_URL", "http://ops_broker:8770").rstrip("/")
BROKER_TOKEN = os.getenv("OPS_BROKER_TOKEN", "")
BROKER_TIMEOUT = float(os.getenv("OPS_REQUEST_TIMEOUT", "1200"))

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
        "absolute_paths": ["/app/supervisor.py"],
        "paths": [],
        "command_prefixes": [],
    },
    "n8n_catalog": {
        "purpose": "Catálogo controlado de workflows n8n",
        "absolute_paths": ["/app/workflow_catalog.py"],
        "paths": [],
        "command_prefixes": [],
    },
    "factory_kernel": {
        "purpose": "Inventário e decisão discovery-first da Factory",
        "absolute_paths": ["/app/factory_kernel.py"],
        "paths": [],
        "command_prefixes": [],
    },
}


def _broker_get(path: str) -> dict[str, Any]:
    headers = {"Authorization": f"Bearer {BROKER_TOKEN}"}
    try:
        with httpx.Client(timeout=BROKER_TIMEOUT) as client:
            response = client.get(f"{BROKER_URL}{path}", headers=headers)
        try:
            body = response.json()
        except Exception:
            body = {"raw": response.text}
        return {
            "ok": response.is_success,
            "status_code": response.status_code,
            "body": body,
        }
    except Exception as exc:
        return {"ok": False, "status_code": 0, "body": {"error": str(exc)}}


def _artisan_inventory() -> dict[str, Any]:
    response = _broker_get("/inventory/artisan")
    body = response.get("body", {}) if isinstance(response.get("body"), dict) else {}
    return {
        "ok": bool(response.get("ok") and body.get("ok")),
        "commands": body.get("commands", []) if isinstance(body.get("commands"), list) else [],
        "diagnostic": {
            "broker_status": response.get("status_code"),
            "detail": body.get("diagnostic", body),
        },
    }


def _path_state(relative: str) -> dict[str, Any]:
    path = (APP_ROOT / relative).resolve()
    exists = path.exists()
    return {
        "relative_path": relative,
        "exists": exists,
        "kind": "directory" if path.is_dir() else "file" if path.is_file() else "missing",
        "entries": len(list(path.iterdir())) if path.is_dir() else None,
    }


def _absolute_path_state(raw_path: str) -> dict[str, Any]:
    path = Path(raw_path)
    exists = path.exists()
    return {
        "absolute_path": raw_path,
        "exists": exists,
        "kind": "directory" if path.is_dir() else "file" if path.is_file() else "missing",
        "entries": len(list(path.iterdir())) if path.is_dir() else None,
    }


def _component_state(name: str, definition: dict[str, Any], commands: list[str]) -> dict[str, Any]:
    paths = [_path_state(item) for item in definition.get("paths", [])]
    absolute_paths = [_absolute_path_state(item) for item in definition.get("absolute_paths", [])]
    matched_commands = sorted(
        command
        for command in commands
        if any(command.startswith(prefix) for prefix in definition.get("command_prefixes", []))
    )
    exists = any(item["exists"] for item in paths + absolute_paths) or bool(matched_commands)
    return {
        "name": name,
        "purpose": definition["purpose"],
        "exists": exists,
        "paths": paths,
        "absolute_paths": absolute_paths,
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


def _container_inventory() -> dict[str, Any]:
    response = _broker_get("/inventory/containers")
    body = response.get("body", {}) if isinstance(response.get("body"), dict) else {}
    containers = body.get("containers", []) if isinstance(body.get("containers"), list) else []
    return {
        "ok": bool(response.get("ok") and body.get("ok")),
        "containers": containers,
        "diagnostic": {
            "broker_status": response.get("status_code"),
            "detail": body.get("diagnostic", body),
        },
    }


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

    factory_like = [item.get("name", "") for item in containers if "factory" in item.get("name", "").lower()]
    if len(factory_like) > 1:
        signals.append({"type": "multiple_factory_containers", "containers": factory_like})
    return signals


def factory_kernel_inventory_impl() -> dict[str, Any]:
    """Implementação interna chamável diretamente e também pelo wrapper MCP."""
    artisan = _artisan_inventory()
    commands = artisan["commands"]
    components = {
        name: _component_state(name, definition, commands)
        for name, definition in KNOWN_COMPONENTS.items()
    }
    repositories = _repository_inventory()
    docker_inventory = _container_inventory()
    containers = docker_inventory["containers"]
    duplicate_signals = _duplicate_signals(repositories, containers)
    return {
        "checked_at": datetime.now(timezone.utc).isoformat(),
        "kernel_version": "1.2.0",
        "policy": "Construir somente se não existir; caso exista, reutilizar, centralizar ou evoluir.",
        "components": components,
        "artisan_command_count": len(commands),
        "artisan_commands": commands,
        "repositories": repositories,
        "containers": containers,
        "duplicate_signals": duplicate_signals,
        "diagnostics": {
            "artisan": artisan["diagnostic"],
            "containers": docker_inventory["diagnostic"],
        },
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
        "artisan_command_count": inventory["artisan_command_count"],
        "container_count": len(inventory["containers"]),
        "duplicate_signals": inventory["duplicate_signals"],
        "inventory": inventory["components"],
    }
