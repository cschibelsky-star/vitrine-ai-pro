"""Safe projection of actual Super Centro read-only operational responses.

This is an evidence adapter, NOT a capability grant. Real connector responses
often have no versioned capability declaration; such responses remain blocked.
"""
from typing import Any
from ops.preflight.connector_runner import collect_and_plan


def _dict(value: Any) -> dict:
    return value if isinstance(value, dict) else {}


def project_readonly_probes(*, git_compare: dict, runtime_status: dict,
                            binding_status: dict, health_status: dict,
                            capability_contracts: dict, ci_run: dict | None = None):
    """Project actual connector results into sanitized, closed-by-default probes.

    Avoid passing source objects through to the planner; they may contain secrets.
    """
    git = _dict(git_compare)
    runtime = _dict(runtime_status)
    binding = _dict(binding_status)
    health = _dict(health_status)
    runs = _dict(ci_run)
    secrets = _dict(runtime.get("secrets"))
    via = _dict(secrets.get("FACTORY_VIA_READ_TOKEN"))
    binds = binding.get("bindings")
    eligible_bind = next(
        (b for b in binds if isinstance(b, dict)
         and b.get("target_key") == "FACTORY_VIA_READ_TOKEN"
         and b.get("source_project_id") == "factory"),
        None,
    ) if isinstance(binds, list) else None
    # Read-only status cannot prove import permissions, source file ACLs,
    # Docker network allocation, free ports or cache readiness.
    return {
        "git": lambda: {
            "ok": git.get("ok") is True,
            "dirty": git.get("dirty") if type(git.get("dirty")) is bool else None,
            "ahead": git.get("ahead"),
            "behind": git.get("behind"),
        },
        "ci": lambda: {
            "status": runs.get("status"),
            "conclusion": runs.get("conclusion"),
        },
        "docker": lambda: {
            "ok": health.get("ok") is True,
            "network_exists": None,
            "port_available": None,
            "cache_ready": None,
        },
        "secret": lambda: {
            "ok": runtime.get("ok") is True and binding.get("ok") is True,
            "source_present": via.get("present") is True and bool(eligible_bind)
                              and eligible_bind.get("source_present") is True,
            "source_authorized": None,
            "destination_key_allowed": None,
            "migration_allowed": None,
        },
        "connectors": lambda: _dict(capability_contracts),
    }


def plan_factory_via_readonly(*, git_compare: dict, runtime_status: dict,
                              binding_status: dict, health_status: dict,
                              capability_contracts: dict, ci_run: dict | None = None) -> dict:
    """A live-observation-shaped Factory/VIA secret preflight, fail closed."""
    return collect_and_plan(
        {"project_id": "factory", "capability": "import_secret"},
        project_readonly_probes(
            git_compare=git_compare,
            runtime_status=runtime_status,
            binding_status=binding_status,
            health_status=health_status,
            capability_contracts=capability_contracts,
            ci_run=ci_run,
        ),
    )
