"""Contract boundary for an authorized Avello MCP caller; no network calls here."""
from dataclasses import dataclass
from typing import Callable
from avello_adapter import AvelloError

# Read-only discovery confirmed these tool names, not their wire transport.
SUPPORTED_PROVIDERS = frozenset({"gpt", "claude"})

@dataclass(frozen=True)
class AvelloInference:
    provider: str
    model: str
    prompt: str

class AvelloMcpBridge:
    """MCP invocation must be supplied by an authenticated, approved runtime.

    A plain avello_ask result is advice, not evidence of a completed mission.
    This bridge returns an *unverified* inference response, never an ExecutionReceipt.
    """
    def __init__(self, caller: Callable | None = None, *, enabled: bool = False):
        self._caller = caller
        self._enabled = enabled

    def infer(self, request: AvelloInference):
        if not self._enabled or self._caller is None:
            raise AvelloError("avello_bridge_disabled")
        if request.provider not in SUPPORTED_PROVIDERS:
            raise AvelloError("avello_provider_not_allowed")
        if not isinstance(request.prompt, str) or not request.prompt.strip():
            raise AvelloError("avello_prompt_required")
        if not isinstance(request.model, str):
            raise AvelloError("avello_model_invalid")
        args = {"prompt": request.prompt, "provider": request.provider}
        if request.model.strip():
            args["model"] = request.model
        try:
            result = self._caller("avello_ask", args)
        except Exception:
            raise AvelloError("avello_mcp_call_failed") from None
        if result is None:
            raise AvelloError("avello_empty_response")
        return {"verification": "unverified", "provider": request.provider,
                "model_requested": request.model, "raw_result": result}
