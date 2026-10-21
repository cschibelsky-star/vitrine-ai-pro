"""Contract checks for the read-only connector preflight."""
import unittest
from ops.preflight.connector_preflight import preflight


def baseline(capability):
    return {
        "git": {"dirty": False, "synced": True},
        "docker": {"network_exists": True, "port_available": True, "cache_ready": True},
        "ci": {"success": True},
        "secret": {"source_present": True, "source_authorized": True, "destination_key_allowed": True, "migration_allowed": True},
        "connectors": {
            "super": {"available": True, "capabilities": [capability], "policy_allowed": True, "project_allowed": True},
            "v5": {"available": True, "capabilities": [capability], "policy_allowed": True, "project_allowed": True},
        },
    }


class PreflightTests(unittest.TestCase):
    def run_check(self, capability, facts):
        return preflight({"project_id": "factory", "capability": capability}, facts)

    def test_ready_prefers_super_and_never_executes(self):
        result = self.run_check("compose_up", baseline("compose_up"))
        self.assertEqual(result["status"], "ready")
        self.assertEqual(result["route"], "super")
        self.assertFalse(result["executed"])

    def test_exhausted_docker_network_blocks(self):
        facts = baseline("compose_up")
        facts["docker"]["network_exists"] = False
        result = self.run_check("compose_up", facts)
        self.assertEqual(result["status"], "blocked")
        self.assertIn("NETWORK_UNAVAILABLE", [f["code"] for f in result["findings"]])

    def test_cache_not_ready_blocks(self):
        facts = baseline("compose_up")
        facts["docker"]["cache_ready"] = False
        result = self.run_check("compose_up", facts)
        self.assertIn("CACHE_UNVERIFIED", [f["code"] for f in result["findings"]])

    def test_container_secret_import_policy_blocks(self):
        facts = baseline("import_secret")
        facts["secret"]["migration_allowed"] = False
        result = self.run_check("import_secret", facts)
        self.assertEqual(result["status"], "blocked")
        self.assertIn("SECRET_IMPORT_POLICY", [f["code"] for f in result["findings"]])

    def test_fallback_requires_explicit_policy_approval(self):
        facts = baseline("compose_up")
        facts["connectors"]["super"]["policy_allowed"] = False
        result = self.run_check("compose_up", facts)
        self.assertEqual(result["route"], "v5")

    def test_no_route_fails_closed(self):
        facts = baseline("import_secret")
        facts["connectors"]["super"]["project_allowed"] = False
        facts["connectors"]["v5"]["policy_allowed"] = False
        result = self.run_check("import_secret", facts)
        self.assertEqual(result["status"], "blocked")
        self.assertIsNone(result["route"])

    def test_unverified_state_is_not_success(self):
        result = self.run_check("compose_up", {"connectors": {}})
        self.assertEqual(result["status"], "blocked")
        self.assertTrue(result["findings"])

    def test_adapter_uses_real_shaped_read_only_responses(self):
        from ops.preflight.connector_observations import build_observations
        facts = build_observations(
            git={"ok": True, "dirty": False, "ahead": 0, "behind": 0},
            ci={"status": "completed", "conclusion": "success"},
            docker={"ok": True, "network_exists": True, "port_available": True, "cache_ready": True},
            secret={"ok": True, "source_present": True, "source_authorized": False,
                    "destination_key_allowed": True, "migration_allowed": False},
            connector_inventory={"super": {"available": True, "capabilities": ["import_secret"],
                                            "policy_allowed": True, "project_allowed": True,
                                            "project_ids": ["factory"]}},
            project_id="factory", capability="import_secret",
        )
        plan = self.run_check("import_secret", facts)
        self.assertEqual(plan["status"], "blocked")
        self.assertIn("SECRET_IMPORT_POLICY", [x["code"] for x in plan["findings"]])

    def test_adapter_rejects_missing_permissions_and_string_booleans(self):
        from ops.preflight.connector_observations import build_observations
        facts = build_observations(
            git={"ok": True, "dirty": "false", "ahead": 0, "behind": 0},
            ci={"status": "pending", "conclusion": None},
            docker={"ok": True, "network_exists": "true", "port_available": True},
            secret={"ok": False, "source_present": True},
            connector_inventory={"v5": {"available": True, "capabilities": ["compose_up"],
                                        "policy_allowed": True, "project_allowed": True,
                                        "project_ids": ["via"]}},
            project_id="factory", capability="compose_up",
        )
        plan = self.run_check("compose_up", facts)
        self.assertEqual(plan["status"], "blocked")
        self.assertIsNone(plan["route"])

    def test_versioned_connector_bridge_accepts_authorized_contract(self):
        from ops.preflight.connector_bridge import plan_from_readonly_responses
        responses = {
            "git": {"ok": True, "dirty": False, "ahead": 0, "behind": 0},
            "ci": {"status": "completed", "conclusion": "success"},
            "docker": {"ok": True, "network_exists": True, "port_available": True, "cache_ready": True},
            "secret": {"ok": True, "source_present": True, "source_authorized": True,
                       "destination_key_allowed": True, "migration_allowed": True},
            "connectors": {"super": {"ok": True, "contract_version": 1, "available": True,
                                     "capabilities": ["compose_up"], "project_ids": ["factory"],
                                     "policy_allowed": True}},
        }
        plan = plan_from_readonly_responses({"project_id": "factory", "capability": "compose_up"}, responses)
        self.assertEqual(plan["status"], "ready")
        self.assertEqual(plan["route"], "super")

    def test_bridge_rejects_unknown_contract_version(self):
        from ops.preflight.connector_bridge import plan_from_readonly_responses
        responses = {
            "git": {"ok": True, "dirty": False, "ahead": 0, "behind": 0},
            "ci": {"status": "completed", "conclusion": "success"},
            "docker": {"ok": True, "network_exists": True, "port_available": True, "cache_ready": True},
            "connectors": {"super": {"ok": True, "contract_version": 2, "available": True,
                                     "capabilities": ["compose_up"], "project_ids": ["factory"],
                                     "policy_allowed": True}},
        }
        plan = plan_from_readonly_responses({"project_id": "factory", "capability": "compose_up"}, responses)
        self.assertEqual(plan["status"], "blocked")
        self.assertIn("NO_AUTHORIZED_ROUTE", [f["code"] for f in plan["findings"]])


if __name__ == "__main__":
    unittest.main()
