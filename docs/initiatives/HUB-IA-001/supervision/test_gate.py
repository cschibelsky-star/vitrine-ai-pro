import copy
import unittest
from gate import evaluate

BASE = {
    "schema_version": "1.0", "mission_id": "TOOL-CODE-02", "environment": "isolated",
    "source": "codex-windows", "evidence_verified": True, "supervisor_approved": True,
    "tests": {"total": 5, "passed": 5, "failed": 0},
    "changed_files": ["order_service.py"], "original_data_preserved": True,
    "diff_sha256": "a" * 64,
}

class GateTests(unittest.TestCase):
    def test_approved_when_complete(self):
        self.assertTrue(evaluate(copy.deepcopy(BASE))["can_advance"])
    def test_reported_user_result_alone_does_not_pass(self):
        a = copy.deepcopy(BASE); a["evidence_verified"] = False
        self.assertFalse(evaluate(a)["can_advance"])
    def test_requires_independent_supervisor(self):
        a = copy.deepcopy(BASE); a["supervisor_approved"] = False
        self.assertFalse(evaluate(a)["can_advance"])
    def test_failing_tests_block(self):
        a = copy.deepcopy(BASE); a["tests"]["failed"] = 3
        self.assertFalse(evaluate(a)["can_advance"])
    def test_unexpected_file_blocks(self):
        a = copy.deepcopy(BASE); a["changed_files"].append("tests/test_order_service.py")
        self.assertFalse(evaluate(a)["can_advance"])
    def test_invalid_diff_blocks(self):
        a = copy.deepcopy(BASE); a["diff_sha256"] = "xyz"
        self.assertFalse(evaluate(a)["can_advance"])
    def test_non_isolated_blocks(self):
        a = copy.deepcopy(BASE); a["environment"] = "production"
        self.assertFalse(evaluate(a)["can_advance"])

if __name__ == "__main__":
    unittest.main()
