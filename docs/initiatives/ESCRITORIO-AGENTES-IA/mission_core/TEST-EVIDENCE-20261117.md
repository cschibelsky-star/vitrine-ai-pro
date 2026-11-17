# Local verification — 2026-11-17

## Scope
The mission_core source was read through the authenticated GitHub connector from branch feature/escritorio-avello-adapter-isolated-20261117. Direct curl to raw.githubusercontent.com failed DNS resolution in the isolated test container.

A local *reconstructed* copy of mission.py and avello_adapter.py, with tests equivalent to the nine repository test cases, was executed using Python unittest. This is NOT a byte-for-byte GitHub checkout, not the repository's original test suite, and not GitHub CI.

## Observed command and result
Command: `cd /tmp/escritorio_test && python -m unittest -v test_core`
Result: 9 tests, 9 passed, 0 failed (0.001 s).

Covered: DEV/ECO validation, state/idempotency, fail-closed VIA approval, rework, adapter disabled by default, mock receipt without approval, provider identity mismatch, missing evidence, sanitized transport error.

## Avello connectivity
Connector status (read-only) reports calls_enabled=true and remote_execution=false. No `avello_smoke` or `avello_ask` was invoked: these may consume provider credits. Real inference, runtime bridge, authenticated MCP transport, provenance and durable idempotency remain unverified.

## Gate
Do not merge or deploy. Run original `python -m unittest discover -v` from an actual repository checkout or GitHub CI before accepting the implementation. Then review runtime security boundaries and obtain separate authorization for a paid one-shot inference test.
