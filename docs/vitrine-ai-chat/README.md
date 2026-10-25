# Vitrine AI Chat — implementation foundation

Status: **isolated source branch; not deployed; no production migration**.

## Goal
A multimodel workspace for Roteia, Invision (subject to actual authorized API access) and other Centro IA providers, with a later VIA consumer. No parallel provider keys or routing logic.

## Foundation implemented
- `App\\Services\\Ai\\Chat\\AiChatOrchestrator`: controlled chat dispatch through existing `AiRoutingService`.
- `send`: validates text length and restricts execution to text capabilities.
- `compare`: independent sequential answers from two or three explicitly selected authorized agents.
- Normalized execution metadata. Existing Centro IA execution persistence remains in its existing service; this does **not** yet provide a chat thread history.

## Integration boundaries
1. Backend authorization and tenancy must resolve permitted `AiAgent` identities before calling the orchestrator. Never accept an agent ID from an anonymous public request.
2. Secure provider credentials remain in the server-side Centro IA runtime.
3. Invision: unknown connector/API contract. Do not claim it works until endpoint, authentication, model list and allowed usage are verified.
4. Roteia: existing configured adapter is reused. Real API requests and billing were NOT exercised by this PR.
5. Future schema: conversations, messages, participant model choices, costs, file references, audit and user ownership.
6. Future controller: authenticated, rate-limited endpoints, streaming where supported, ownership checks, attachment validation, secure download and retention policy.
7. Future UI: project threads, specialist profiles, model selector, compare mode and VIA access.
8. Never activate multi-provider fan-out without quotas, a spending cap and user visibility.

## Acceptance gates before a deploy
- Unit tests and CI; review model and tenant authorization; no secret exposure.
- Database migration in isolated HML, with backup/rollback plan.
- Chat history isolation across accounts and projects.
- Roteia chat success/failure/timeout and accounting; live Invision smoke test only after API verification.
- Rate limiting, spending quotas, accessibility/mobile QA, audit log verification.

## Scope not implemented yet
Web interface, REST chat endpoints, chat thread DB, uploads, image generation in chat, model catalog, actual Invision connector, streaming, cost dashboard, VIA integration. These remain backlog, not completed capabilities.
