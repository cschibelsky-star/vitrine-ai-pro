"""Normalize read-only connector observations into strict preflight facts.

No credentials, logs, raw environment variables, or secret content are accepted.
An absent observation is *unknown*, never successful.
"""
from typing import Any


def _boolean(value: Any) -> bool | None:
    return value if type(value) is bool else None


def _is_ok(data: dict) -> bool:
    return data.get("ok") is True


def build_observations(*, git: dict, ci: dict, docker: dict,
                       secret: dict, connector_inventory: dict,
                       project_id: str, capability: str) -> dict:
    """Accept only sanitized read-only connector responses and explicit facts."""
    if not project_id or not capability:
        raise ValueError("project_id and capability required")
    clean = {
        "git": {
            "dirty": _boolean(git.get("dirty")) if _is_ok(git) else None,
            "synced": (
                git.get("ahead") == 0 and git.get("behind") == 0
                and isinstance(git.get("ahead"), int)
                and isinstance(git.get("behind"), int)
            ) if _is_ok(git) else None,
        },
        "ci": {"success": ci.get("conclusion") == "success" and ci.get("status") == "completed"},
        "docker": {
            "network_exists": _boolean(docker.get("network_exists")) if _is_ok(docker) else None,
            "port_available": _boolean(docker.get("port_available")) if _is_ok(docker) else None,
            "cache_ready": _boolean(docker.get("cache_ready")) if _is_ok(docker) else None,
        },
        "secret": {
            "source_present": _boolean(secret.get("source_present")) if _is_ok(secret) else None,
            "source_authorized": _boolean(secret.get("source_authorized")) if _is_ok(secret) else None,
            "destination_key_allowed": _boolean(secret.get("destination_key_allowed")) if _is_ok(secret) else None,
            "migration_allowed": _boolean(secret.get("migration_allowed")) if _is_ok(secret) else None,
        },
        "connectors": {},
    }
    for route in ("super", "v5", "vps", "v4"):
        entry = connector_inventory.get(route, {})
        if not isinstance(entry, dict):
            continue
        caps = entry.get("capabilities", [])
        clean["connectors"][route] = {
            "available": entry.get("available") is True,
            "capabilities": [capability] if isinstance(caps, list) and capability in caps else [],
            "policy_allowed": entry.get("policy_allowed") is True,
            "project_allowed": entry.get("project_allowed") is True and (
                not isinstance(entry.get("project_ids"), list) or project_id in entry["project_ids"]
            ),
        }
    return clean
