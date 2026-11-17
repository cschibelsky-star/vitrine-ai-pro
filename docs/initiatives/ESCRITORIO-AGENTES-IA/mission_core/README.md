# Mission core M2 — isolated prototype

Python 3.10+. Run: `cd docs/initiatives/ESCRITORIO-AGENTES-IA/mission_core && python -m unittest -v`.

Implements domain checks DEV/ECO, state transitions, in-memory events, request idempotency and fail-closed approval. This is a test-only reference, not a deployed mission manager. No database, authentication, trusted VIA service, network, providers or external mutations. Never expose this prototype as an API without durable storage, server-side identity and trusted approval signatures. Four local unit tests passed before publishing. GitHub CI not executed.
