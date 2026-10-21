"""Pure read-only connector response adapters for the orchestrator.

Do not infer permissions from the presence of a method. An authorized,
versioned capability statement must be supplied by each connector.
"""
from typing import Any
from ops.preflight.connector_observations import build_observations
from ops.preflight.connector_preflight import preflight

ROUTES = ("super", "v5", "vps", "v4")


def connector_inventory(responses: dict[str, Any], project_id: str) -> dict[str, dict]:
    """Normalize signed/verified capability responses, fail closed otherwise.

    The caller validates the provenance of responses; no network or writes occur.
    """
    result: dict[str, dict] = {}
    for route in ROUTES:
        raw = responses.get(route)
        if not isinstance(raw, dict):
            continue
        # A version and explicit success are mandatory. Unsupported versions fail closed.
        version = raw.get("contract_version")
        if raw.get("ok") is not True or version != 1:
            continue
        capabilities = raw.get("capabilities")
        projects = raw.get("project_ids")
        if not isinstance(capabilities, list) or not all(isinstance(x, str) for x in capabilities):
            continue
        if not isinstance(projects, list) or project_id not in projects:
            continue
        if raw.get("policy_allowed") is not True:
            continue
        result[route] = {
            "available": raw.get("available") is True,
            "capabilities": capabilities,
            "policy_allowed": True,
            "project_allowed": True,
            "project_ids": projects,
        }
    return result


def plan_from_readonly_responses(operation: dict, responses: dict) -> dict:
    """Bridge explicit connector responses into the existing preflight engine."""
    project = operation["project_id"]
    capability = operation["capability"]
    facts = build_observations(
        git=responses.get("git", {}),
        ci=responses.get("ci", {}),
        docker=responses.get("docker", {}),
        secret=responses.get("secret", {}),
        connector_inventory=connector_inventory(responses.get("connectors", {}), project),
        project_id=project,
        capability=capability,
    )
    return preflight(operation, facts)
