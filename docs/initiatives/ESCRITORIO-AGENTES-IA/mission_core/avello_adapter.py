"""Avello-first isolated execution boundary. No live transport or API assumptions."""
from dataclasses import dataclass
from typing import Callable
from uuid import UUID
from mission import Mission, MissionError, State

class AvelloError(RuntimeError):
    pass

@dataclass(frozen=True)
class ExecutionReceipt:
    mission_id: str
    provider: str
    model: str
    external_execution_id: str
    outcome: str
    evidence_ref: str
    duration_ms: int | None = None
    cost: float | None = None

class AvelloExecutionAdapter:
    """Injected callable is a *trusted, authenticated* Avello client supplied later.

    No network call occurs unless enabled=True and a transport is explicitly injected.
    """
    def __init__(self, *, transport: Callable | None = None, enabled: bool = False):
        self._transport = transport
        self._enabled = enabled

    def execute(self, mission: Mission, *, model: str, prompt: str,
                request_id: str, actor: str) -> ExecutionReceipt:
        if not self._enabled or self._transport is None:
            raise AvelloError('avello_calls_disabled')
        if mission.state != State.EXECUTING:
            raise MissionError('mission_not_executing')
        if not all(isinstance(v, str) and v.strip() for v in (model, prompt, request_id, actor)):
            raise AvelloError('invalid_request')
        # Domain and tenant remain attached to the envelope; the real Avello
        # client must enforce authorization and isolation server-side.
        envelope = {'mission_id': mission.mission_id, 'domain': mission.domain,
                    'tenant_id': mission.tenant_id, 'model': model,
                    'prompt': prompt, 'request_id': request_id}
        try:
            response = self._transport(envelope)
        except Exception as exc:
            raise AvelloError('avello_transport_failure') from exc
        if not isinstance(response, dict) or response.get('status') != 'completed':
            raise AvelloError('avello_execution_not_completed')
        if response.get('mission_id') != mission.mission_id or response.get('provider') != 'avello':
            raise AvelloError('avello_response_identity_mismatch')
        if response.get('model') != model:
            raise AvelloError('avello_model_mismatch')
        execution_id = response.get('execution_id')
        evidence = response.get('evidence_ref')
        if not all(isinstance(v, str) and v.strip() for v in (execution_id, evidence)):
            raise AvelloError('avello_evidence_missing')
        receipt = ExecutionReceipt(mission.mission_id, 'avello', model, execution_id,
                                   'completed', evidence,
                                   response.get('duration_ms'), response.get('cost'))
        mission.add_evidence(evidence)
        # A completed Avello call is NOT an approved mission or trusted VIA gate.
        return receipt
