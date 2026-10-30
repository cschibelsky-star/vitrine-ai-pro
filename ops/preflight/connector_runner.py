"""Preflight runner with injected READ-ONLY probes and explicit fail-closed policy.

Probes are supplied by the authenticated connector host, never inferred from
the existence of a tool. This runner performs no state changes and never reads
secret values. Secret probes must return presence/status booleans only.
"""
from collections.abc import Callable, Mapping
from typing import Any
from ops.preflight.connector_bridge import plan_from_readonly_responses

READ_ONLY_KEYS = ("git", "ci", "docker", "secret", "connectors")


def collect_and_plan(operation: dict[str, str],
                     probes: Mapping[str, Callable[[], dict[str, Any]]]) -> dict[str, Any]:
    """Collect authorized observations; any failure or missing probe blocks."""
    if not operation.get("project_id") or not operation.get("capability"):
        raise ValueError("project_id and capability required")
    responses: dict[str, dict] = {}
    probe_errors: list[str] = []
    for key in READ_ONLY_KEYS:
        probe = probes.get(key)
        if probe is None or not callable(probe):
            probe_errors.append(key + ":missing_probe")
            continue
        try:
            value = probe()
            if not isinstance(value, dict):
                probe_errors.append(key + ":invalid_response")
            else:
                responses[key] = value
        except Exception:
            # Never echo exceptions, which can contain connector tokens or payloads.
            probe_errors.append(key + ":probe_failed")
    result = plan_from_readonly_responses(operation, responses)
    if probe_errors:
        result["status"] = "blocked"
        result["route"] = None
        result["findings"].extend({
            "code": "PROBE_UNVERIFIED", "kind": "external_dependency",
            "detail": error
        } for error in probe_errors)
    result["executed"] = False
    return result
