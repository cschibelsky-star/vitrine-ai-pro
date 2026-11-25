# Agent Factory F01 — draft DNA

Factory providers: Roteia, Invision, OpenAI, Gemini. Avello is not a Factory provider; it belongs to the initial Office inference flow. No changes to the Marketing registry or Office PR #183.

Reuse the existing Factory module. DNA versions are draft build artifacts, not a second active ai_agents registry. A separate append-only version table avoids repurposing generic FactoryBlueprint status as VIA approval. The eventual admission adapter must use the canonical AiAgent registry after authenticating VIA and verifying evidence.

F01 includes a pure validator, internal persistence service, migration file, and unit tests. Explicit active administrator, owner-bound revision, immutable domain/company, optimistic version check. Requested skills never grant tools. All drafts disabled, zero spending permission. Model/skill existence and company access require validation before any admission; company_id is only a draft reference. The service is not exposed over HTTP. Existing generic Factory permissions are not reused because a legacy fallback may permit access without an authorization method.

Not included: UI, runtime provider transport, verified catalog, authorization service for VIA, admission, integration or database tests. No migration applied, merge, deploy or API calls. Unit test execution and CI status must be checked before acceptance. Next: isolated persistence tests with real schema, explicit tenant authorization, read/list endpoint and visual constructor. Do not enable any draft.
