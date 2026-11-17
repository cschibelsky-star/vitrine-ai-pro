import unittest
from avello_adapter import AvelloError
from avello_mcp_bridge import AvelloInference, AvelloMcpBridge

class AvelloMcpBridgeTests(unittest.TestCase):
    def test_disabled(self):
        with self.assertRaisesRegex(AvelloError, "bridge_disabled"):
            AvelloMcpBridge().infer(AvelloInference("gpt", "", "test"))
    def test_rejects_unknown_provider(self):
        with self.assertRaisesRegex(AvelloError, "provider_not_allowed"):
            AvelloMcpBridge(lambda *_: "ok", enabled=True).infer(
                AvelloInference("unknown", "", "test"))
    def test_contract_and_unverified_result(self):
        calls = []
        def caller(tool, args):
            calls.append((tool, args))
            return {"text": "answer"}
        result = AvelloMcpBridge(caller, enabled=True).infer(
            AvelloInference("claude", "", "review"))
        self.assertEqual(calls, [("avello_ask", {"prompt":"review", "provider":"claude"})])
        self.assertEqual(result["verification"], "unverified")
        self.assertNotIn("execution_id", result)
    def test_empty_response_fails_closed(self):
        with self.assertRaisesRegex(AvelloError, "empty_response"):
            AvelloMcpBridge(lambda *_: None, enabled=True).infer(
                AvelloInference("gpt", "", "test"))
    def test_exception_sanitized(self):
        def failure(*_): raise RuntimeError("token-secret")
        with self.assertRaisesRegex(AvelloError, "^avello_mcp_call_failed$"):
            AvelloMcpBridge(failure, enabled=True).infer(
                AvelloInference("gpt", "", "test"))

if __name__ == "__main__": unittest.main()
