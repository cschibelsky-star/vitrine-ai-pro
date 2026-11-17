# Avello-first execution adapter — verified connector boundary (2026-11-17)

## Approved decision
Avello is the initial inference executor for DEV/ECO mission calls. Avello2 is a separate product/connector, not an internal Vitrine IA Pro component. Do not confuse inference with remote execution of code or tools.

## Read-only connector evidence (2026-11-17)
Both Vitrine Avello and Vitrine Avello2 status tools returned:
- gpt_key_present=true; claude_key_present=true; claude_model_configured=true
- calls_enabled=true; remote_execution=false; google_oauth_enabled=true
- gpt_default_model=gpt-6.1-sol; claude_default_model=claude-opus-5
- gpt_models=gpt-6.1-sol,gpt-6-sol,gpt-6-luna,gpt-5.6-terra,gpt-5.6-sol,gpt-5.6-luna

These are connector-reported readiness indicators, NOT a successful authenticated inference, authorization of API spend, or working mission transport.

## Observed tool contract
- avello_status({}): safe readiness inspection
- avello_models({provider?}): model catalog, no inference
- avello_ask({prompt,provider?,model?}): development assistance / inference; connector description explicitly says it does NOT execute returned code or edit projects
- avello_smoke({provider?}): one-shot connectivity probe; not run because it may issue a paid call

The plugin tools are available in the ChatGPT connector environment. Their availability does NOT imply a callable URL or authentication scheme for the Python mission_core runtime. A future server-side MCP bridge needs its own authenticated implementation and verification. Do not embed ChatGPT tool names, tokens or guessed HTTP endpoints in mission_core.

## Isolated adapter contract (internal; not Avello's published wire schema)
Request: mission_id, domain DEV/ECO, tenant_id (ECO only), model, prompt, request_id.
Response: status completed, provider avello, matching mission_id/model, execution_id, evidence_ref; optional duration_ms and cost.
The current adapter uses an injected callable, disabled by default; it does not contact Avello itself. Response fields are internal normalization requirements, not a claim that avello_ask natively returns these fields.

## Execution boundaries
1. Server authenticates operator, mission, tenant, model allowlist, request idempotency and cost authorization before invoking Avello.
2. Authorized bridge invokes Avello for inference and returns sanitized provenance with a durable execution identifier.
3. For code/files/tools, use separately authorized Factory/Codex executor; remote_execution=false must not be bypassed or misrepresented.
4. VIA independently verifies evidence and controls gate. Provider success never approves a mission.
5. On missing bridge, failed identity, unknown costs or unavailable supervisor: fail closed.

## Current verification and blockers
- Connector status: queried successfully (read-only) on both connectors.
- Real Avello inference/smoke: NOT executed; no paid call authorized.
- Python unit tests: source exists, execution not confirmed in this session; do not claim green.
- No runtime deployment, persistence, authenticated bridge, durable idempotency, HML, merge or production.
- Container network could not resolve GitHub raw URL; remote files were inspected through the authenticated GitHub connector.

## Next safe gate
Implement a verified MCP/Avello bridge contract with authenticated server-side identity, cost limit, retry policy, and real response schema; test in an isolated environment with explicit authorization for any paid call. Do not expose an unverified adapter as operational.
