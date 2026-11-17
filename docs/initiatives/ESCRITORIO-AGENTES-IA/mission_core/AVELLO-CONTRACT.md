# Avello-first execution adapter — isolated contract (2026-11-17)

## Decision
Avello is the initial execution provider for DEV/ECO mission calls. This is an adapter boundary, not a claim that an Avello endpoint is authenticated or operational. Do not infer the Avello2 product is an internal Vitrine IA Pro component.

## Contract
Request envelope: mission_id, domain DEV/ECO, tenant_id (ECO only), model, prompt, request_id. Response: status completed, provider avello, matching mission_id/model, execution_id, evidence_ref; optional duration_ms and cost. Fail closed on absent/mismatched fields. A completed provider call never implies VIA approval.

## Security and integration gates
- Disabled by default; injection of authenticated transport required.
- No URLs, tokens, or API formats guessed; validate Avello's real published contract before writing a network client.
- Runtime must enforce server-side identity, tenant isolation, durable idempotency, allowlisted tools, budget ceilings, timeouts, retries, sanitized logs and response authenticity.
- Evidence references are untrusted until independently verified. The current mission state machine blocks approval.
- No paid API call, deploy, HML or production access in this increment.

## Test plan
Unit tests use an injected mock transport and cover disabled-by-default, valid response without mission approval, mismatched provider, missing evidence and sanitized failures. Tests are NOT proof of real Avello connectivity.
