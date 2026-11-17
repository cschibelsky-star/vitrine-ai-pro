"""Isolated DEV/ECO mission state machine; no API, persistence, or runtime calls."""
from dataclasses import dataclass, field
from enum import Enum
from uuid import uuid4

class MissionError(ValueError):
    pass

class State(str, Enum):
    PLANNED = 'planned'
    ASSIGNED = 'assigned'
    EXECUTING = 'executing'
    TESTING = 'testing'
    AWAITING = 'awaiting_independent_verification'
    REWORK = 'rework'
    APPROVED = 'approved'
    COMPLETED = 'completed'

ALLOWED = {
    State.PLANNED: {State.ASSIGNED},
    State.ASSIGNED: {State.EXECUTING},
    State.EXECUTING: {State.TESTING},
    State.TESTING: {State.AWAITING},
    State.AWAITING: {State.REWORK, State.APPROVED},
    State.REWORK: {State.ASSIGNED},
    State.APPROVED: {State.COMPLETED},
    State.COMPLETED: set(),
}

@dataclass
class Mission:
    title: str
    domain: str
    tenant_id: str | None = None
    mission_id: str = field(default_factory=lambda: str(uuid4()))
    state: State = State.PLANNED
    evidence: list[str] = field(default_factory=list)
    events: list[dict] = field(default_factory=list)
    verified_by: str | None = None
    _requests: dict[str, State] = field(default_factory=dict, repr=False)

    def __post_init__(self):
        if not self.title.strip() or self.domain not in {'DEV', 'ECO'}:
            raise MissionError('invalid_mission')
        if self.domain == 'ECO' and not self.tenant_id:
            raise MissionError('tenant_required')
        if self.domain == 'DEV' and self.tenant_id is not None:
            raise MissionError('cross_domain_tenant')

    def add_evidence(self, reference: str):
        if not reference.strip():
            raise MissionError('empty_evidence')
        if reference not in self.evidence:
            self.evidence.append(reference)

    def transition(self, target: State, *, request_id: str, actor: str,
                   independent_verification: bool = False):
        if not request_id.strip() or not actor.strip():
            raise MissionError('missing_identity')
        if request_id in self._requests:
            if self._requests[request_id] != target:
                raise MissionError('idempotency_conflict')
            return self.state
        if target not in ALLOWED[self.state]:
            raise MissionError('invalid_transition')
        if target in {State.APPROVED, State.COMPLETED}:
            if not self.evidence or not self.verified_by:
                raise MissionError('independent_verification_required')
        if target == State.APPROVED:
            # A caller-supplied boolean is not trusted VIA approval.
            raise MissionError('trusted_gate_not_connected')
        previous = self.state
        self.state = target
        self._requests[request_id] = target
        self.events.append({'from': previous.value, 'to': target.value,
                            'actor': actor, 'request_id': request_id})
        return self.state
