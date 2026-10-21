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

    def test_runner_collects_authorized_probe_contracts(self):
        from ops.preflight.connector_runner import collect_and_plan
        probes = {
            "git": lambda: {"ok": True, "dirty": False, "ahead": 0, "behind": 0},
            "ci": lambda: {"status": "completed", "conclusion": "success"},
            "docker": lambda: {"ok": True, "network_exists": True, "port_available": True, "cache_ready": True},
            "secret": lambda: {"ok": True, "source_present": True, "source_authorized": True,
                               "destination_key_allowed": True, "migration_allowed": True},
            "connectors": lambda: {"super": {"ok": True, "contract_version": 1, "available": True,
                                              "capabilities": ["compose_up"], "project_ids": ["factory"],
                                              "policy_allowed": True}},
        }
        result = collect_and_plan({"project_id": "factory", "capability": "compose_up"}, probes)
        self.assertEqual(result["status"], "ready")
        self.assertEqual(result["route"], "super")
        self.assertFalse(result["executed"])

    def test_runner_blocks_when_probe_missing_or_raises(self):
        from ops.preflight.connector_runner import collect_and_plan
        def failed():
            raise RuntimeError("sensitive details must not leak")
        result = collect_and_plan({"project_id": "factory", "capability": "import_secret"},
                                  {"git": failed})
        self.assertEqual(result["status"], "blocked")
        self.assertIsNone(result["route"])
        self.assertNotIn("sensitive details", str(result))
        self.assertIn("PROBE_UNVERIFIED", [x["code"] for x in result["findings"]])

    def test_factory_live_shape_does_not_fake_secret_policy(self):
        from ops.preflight.factory_connector_adapter import plan_factory_via_readonly
        plan = plan_factory_via_readonly(
            git_compare={"ok": True, "dirty": False, "ahead": 0, "behind": 0},
            runtime_status={"ok": True, "secrets": {"FACTORY_VIA_READ_TOKEN": {"present": False}}},
            binding_status={"ok": True, "bindings": [{"target_key": "FACTORY_VIA_READ_TOKEN",
                            "source_project_id": "factory", "source_present": False}]},
            health_status={"ok": True, "health": "healthy"},
            capability_contracts={},
        )
        self.assertEqual(plan["status"], "blocked")
        self.assertIsNone(plan["route"])
        codes = [x["code"] for x in plan["findings"]]
        self.assertIn("SECRET_SOURCE_UNAVAILABLE", codes)
        self.assertIn("SECRET_IMPORT_POLICY", codes)
        self.assertIn("NO_AUTHORIZED_ROUTE", codes)

    def test_operation_gate_requires_hml_and_explicit_authorization(self):
        from ops.preflight.operation_gate import gate_operation
        from ops.preflight.connector_runner import READ_ONLY_KEYS
        for environment, authorized in (("production", True), ("hml", False)):
            plan = gate_operation(
                {"project_id": "factory", "capability": "compose_up",
                 "environment": environment, "mutation_authorized": authorized},
                {})
            self.assertEqual(plan["status"], "blocked")
            self.assertIsNone(plan["route"])
            self.assertFalse(plan["executed"])

    def test_operation_gate_cannot_authorize_without_observations(self):
        from ops.preflight.operation_gate import gate_operation
        plan = gate_operation(
            {"project_id": "factory", "capability": "import_secret",
             "environment": "hml", "mutation_authorized": True},
            {})
        self.assertEqual(plan["status"], "blocked")
        self.assertIn("PROBE_UNVERIFIED", [x["code"] for x in plan["findings"]])

    def test_executor_boundary_blocks_without_probes(self):
        from ops.preflight.executor_boundary import execute_hml_operation, PreflightDenied
        calls = []
        with self.assertRaises(PreflightDenied):
            execute_hml_operation(
                {"project_id": "factory", "capability": "compose_up",
                 "environment": "hml", "mutation_authorized": True},
                {}, lambda operation, route: calls.append(route))
        self.assertEqual(calls, [])

    def test_executor_boundary_runs_only_after_double_preflight(self):
        from ops.preflight.executor_boundary import execute_hml_operation
        counts = {"git": 0, "writes": 0}
        def git_probe():
            counts["git"] += 1
            return {"ok": True, "dirty": False, "ahead": 0, "behind": 0}
        probes = {
            "git": git_probe,
            "ci": lambda: {"status": "completed", "conclusion": "success"},
            "docker": lambda: {"ok": True, "network_exists": True,
                                "port_available": True, "cache_ready": True},
            "secret": lambda: {"ok": True},
            "connectors": lambda: {"super": {"ok": True, "contract_version": 1,
                "available": True, "capabilities": ["compose_up"],
                "project_ids": ["factory"], "policy_allowed": True}},
        }
        def executor(operation, route):
            counts["writes"] += 1
            return {"ok": True}
        result = execute_hml_operation({"project_id": "factory",
                "capability": "compose_up", "environment": "hml",
                "mutation_authorized": True}, probes, executor)
        self.assertEqual(result["status"], "executed")
        self.assertEqual(counts["git"], 2)
        self.assertEqual(counts["writes"], 1)

    def test_executor_boundary_rejects_changed_state_without_writing(self):
        from ops.preflight.executor_boundary import execute_hml_operation, PreflightDenied
        calls = {"git": 0, "writes": 0}
        def changing_git():
            calls["git"] += 1
            return {"ok": True, "dirty": calls["git"] > 1,
                    "ahead": 0, "behind": 0}
        probes = {
            "git": changing_git,
            "ci": lambda: {"status": "completed", "conclusion": "success"},
            "docker": lambda: {"ok": True, "network_exists": True,
                                "port_available": True, "cache_ready": True},
            "secret": lambda: {"ok": True},
            "connectors": lambda: {"super": {"ok": True, "contract_version": 1,
                "available": True, "capabilities": ["compose_up"],
                "project_ids": ["factory"], "policy_allowed": True}},
        }
        def write(operation, route):
            calls["writes"] += 1
        with self.assertRaises(PreflightDenied):
            execute_hml_operation({"project_id": "factory",
                "capability": "compose_up", "environment": "hml",
                "mutation_authorized": True}, probes, write)
        self.assertEqual(calls["writes"], 0)


if __name__ == "__main__":
    unittest.main()
