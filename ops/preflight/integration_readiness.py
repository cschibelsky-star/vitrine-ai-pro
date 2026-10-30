"""Gate a cross-project integration on real connector capability AND runtime readiness.

The Super Centro's generic project_capability_check tests tool coverage, not
whether credentials and authenticated requests are ready. No mutation occurs.
"""


def integration_readiness(*, factory_capabilities: dict, via_capabilities: dict,
                          factory_runtime: dict, via_binding: dict,
                          factory_health: dict, authenticated_probe: dict | None = None) -> dict:
    failures = []

    def require(condition: bool, code: str, detail: str) -> None:
        if not condition:
            failures.append({"code": code, "detail": detail})

    require(factory_capabilities.get("ok") is True and
            factory_capabilities.get("fully_supported") is True,
            "FACTORY_TOOLS_UNAVAILABLE", "Factory tool capability has not been verified")
    require(via_capabilities.get("ok") is True and
            via_capabilities.get("fully_supported") is True,
            "VIA_TOOLS_UNAVAILABLE", "VIA tool capability has not been verified")
    require(factory_health.get("ok") is True and factory_health.get("health") == "healthy",
            "FACTORY_NOT_HEALTHY", "Factory HML health has not been verified")

    token_entry = factory_runtime.get("secrets", {}).get("FACTORY_VIA_READ_TOKEN", {})
    require(factory_runtime.get("ok") is True and
            isinstance(token_entry, dict) and token_entry.get("present") is True,
            "FACTORY_TOKEN_ABSENT", "Factory managed token is not present")
    binds = via_binding.get("bindings")
    bind = next((item for item in binds if isinstance(item, dict)
                 and item.get("target_key") == "FACTORY_VIA_READ_TOKEN"
                 and item.get("source_project_id") == "factory"), None) if isinstance(binds, list) else None
    require(via_binding.get("ok") is True and bind is not None and
            bind.get("enabled") is True and bind.get("source_present") is True and
            bind.get("target_present") is True,
            "VIA_BINDING_UNREADY", "Factory-to-VIA credential binding is not ready")
    probe = authenticated_probe if isinstance(authenticated_probe, dict) else {}
    require(probe.get("ok") is True and probe.get("authenticated") is True and
            probe.get("target") == "factory_hml",
            "E2E_AUTH_NOT_VERIFIED", "End-to-end authenticated VIA-to-Factory call is not verified")

    return {"status": "ready" if not failures else "blocked",
            "findings": failures, "integration": "via_factory_hml",
            "executed": False}
