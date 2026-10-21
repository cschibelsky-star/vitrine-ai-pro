"""Controlled executor boundary for the HML preflight pilot.

Only the injected approved executor may mutate. The guard rejects stale or
unverified plans, rechecks immediately before the action and never retries
a possibly partially executed operation.
"""
from collections.abc import Callable
from typing import Any
from ops.preflight.operation_gate import gate_operation


class PreflightDenied(Exception):
    pass


def execute_hml_operation(operation: dict[str, Any],
                          probes: dict,
                          executor: Callable[[dict[str, Any], str], Any]) -> dict:
    """Require an explicit operation-scoped authorization and an eligible route."""
    if operation.get("mutation_authorized") is not True:
        raise PreflightDenied("MUTATION_NOT_AUTHORIZED")
    initial = gate_operation(operation, probes)
    if initial.get("status") != "ready" or not initial.get("route"):
        raise PreflightDenied("PREFLIGHT_BLOCKED")
    # A second read prevents a stale preflight from approving a changed state.
    latest = gate_operation(operation, probes)
    if latest.get("status") != "ready" or latest.get("route") != initial["route"]:
        raise PreflightDenied("PREFLIGHT_CHANGED")
    # Do not log payload, exception details, or secret values.
    result = executor(operation, latest["route"])
    return {"status": "executed", "route": latest["route"],
            "project_id": operation["project_id"], "result": result}
