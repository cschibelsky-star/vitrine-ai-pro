"""Mandatory read-only preflight gate before an authorized operation.

This gate deliberately cannot execute connector mutations. The calling host
must check the returned decision, and re-check it immediately before writes.
"""
from typing import Any
from ops.preflight.connector_runner import collect_and_plan


def gate_operation(operation: dict[str, Any], probes: dict) -> dict[str, Any]:
    """Return a fail-closed decision tied to the requested project and action."""
    if not isinstance(operation, dict):
        raise ValueError("operation required")
    project = operation.get("project_id")
    capability = operation.get("capability")
    environment = operation.get("environment")
    if not isinstance(project, str) or not project:
        raise ValueError("project_id required")
    if not isinstance(capability, str) or not capability:
        raise ValueError("capability required")
    # The pilot is HML-only. Never interpret a request for production as HML.
    if environment != "hml":
        return {
            "project_id": project, "capability": capability,
            "status": "blocked", "route": None,
            "findings": [{"code": "ENVIRONMENT_NOT_AUTHORIZED",
                          "kind": "connector_policy",
                          "detail": "Only explicit HML operations are supported"}],
            "executed": False, "requires_explicit_execute": True,
        }
    result = collect_and_plan(operation, probes)
    result["environment"] = "hml"
    # Guard against accidental inclusion of untrusted write intents.
    if operation.get("mutation_authorized") is not True:
        result["status"] = "blocked"
        result["route"] = None
        result["findings"].append({
            "code": "MUTATION_NOT_AUTHORIZED", "kind": "connector_policy",
            "detail": "Explicit operation-scoped EXECUTAR is missing",
        })
    return result
