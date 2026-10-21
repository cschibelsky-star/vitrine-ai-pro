"""Fail-closed, read-only planning preflight for operational connector routes.

This module does NOT execute commands or fetch secret values. The orchestrator
must supply fresh observations from authorised read-only connectors.
"""
from dataclasses import dataclass, asdict
from typing import Any

ERROR_KINDS = {"project_error", "connector_policy", "executor_block", "external_dependency"}
ROUTE_ORDER = ("super", "v5", "vps", "v4")


@dataclass(frozen=True)
class Finding:
    code: str
    kind: str
    detail: str


def preflight(operation: dict[str, Any], observations: dict[str, Any]) -> dict[str, Any]:
    """Plan a requested operation without mutation. Unknown required state blocks."""
    project = operation.get("project_id")
    capability = operation.get("capability")
    if not isinstance(project, str) or not project or not isinstance(capability, str) or not capability:
        raise ValueError("project_id and capability are required")

    errors: list[Finding] = []
    def blocked(code: str, kind: str, detail: str) -> None:
        errors.append(Finding(code, kind, detail))

    repo = observations.get("git", {})
    if repo.get("dirty") is not False:
        blocked("GIT_UNVERIFIED", "external_dependency", "Workspace cleanliness not verified")
    if repo.get("synced") is not True:
        blocked("REVISION_UNVERIFIED", "external_dependency", "Canonical revision not reconciled")

    if capability in ("compose_up", "deploy_hml"):
        docker = observations.get("docker", {})
        if docker.get("network_exists") is not True:
            blocked("NETWORK_UNAVAILABLE", "external_dependency", "Approved existing network not verified")
        if docker.get("port_available") is not True:
            blocked("PORT_UNVERIFIED", "external_dependency", "Port binding not verified")
        if docker.get("cache_ready") is not True:
            blocked("CACHE_UNVERIFIED", "project_error", "Cache driver/table readiness unverified")
        if observations.get("ci", {}).get("success") is not True:
            blocked("CI_NOT_GREEN", "external_dependency", "CI success not verified")

    if capability in ("import_secret", "bind_secret"):
        secret = observations.get("secret", {})
        if secret.get("source_present") is not True:
            blocked("SECRET_SOURCE_UNAVAILABLE", "external_dependency", "Secret source not present")
        if secret.get("source_authorized") is not True:
            blocked("SECRET_SOURCE_POLICY", "connector_policy", "Source transfer policy not approved")
        if secret.get("destination_key_allowed") is not True:
            blocked("SECRET_DESTINATION_POLICY", "connector_policy", "Destination key not allowlisted")
        if secret.get("migration_allowed") is not True:
            blocked("SECRET_IMPORT_POLICY", "connector_policy", "Broker migration/import permission unavailable")

    connectors = observations.get("connectors", {})
    eligible = []
    for route in ROUTE_ORDER:
        info = connectors.get(route, {})
        if info.get("available") is True and capability in info.get("capabilities", []):
            if info.get("policy_allowed") is True and info.get("project_allowed") is True:
                eligible.append(route)
    if not eligible:
        blocked("NO_AUTHORIZED_ROUTE", "connector_policy", "No eligible connector with verified policy")

    result = {
        "project_id": project,
        "capability": capability,
        "status": "ready" if not errors else "blocked",
        "route": eligible[0] if not errors else None,
        "findings": [asdict(x) for x in errors],
        "requires_explicit_execute": True,
        "executed": False,
    }
    return result
