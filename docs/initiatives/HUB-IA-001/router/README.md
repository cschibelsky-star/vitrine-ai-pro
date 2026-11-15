# HUB-IA-001: multiprovider router (isolated prototype)
Scope: Codex and later Vitrine Centro IA only. ChatGPT configuration stays untouched.
Local loopback HTTP POST /v1/responses; explicit model identifier provider::model. Supports invision, roteia, avello.
Every request requires HUB_LOCAL_TOKEN as a Bearer header, explicit HUB_ALLOWED_MODELS, configured HTTPS URL and API key for the selected provider and HUB_ENABLE_CALLS=1. All calls blocked by default; no fallback between providers.
Provider variables: INVISION_BASE_URL/INVISION_API_KEY; ROTEIA_BASE_URL/ROTEIA_API_KEY; AVELLO_BASE_URL/AVELLO_API_KEY.
Run offline checks from this directory: python -m unittest -v test_router.py. Tests use mocked transport; no paid API calls.
Run service only for intentional local evaluation: python router.py (binds 127.0.0.1 port 18761). Do not enable paid calls before verified server-side cost gate.
LIMITATIONS: Codex application integration not proven; provider Responses API/tool-calling support not verified for Roteia/Avello; display catalog not implemented; no authenticated upstream billing/audit, cancel/retry policy, production protections, or VIA integration. Not installed. Do not edit Codex or ChatGPT settings, HML or PROD using this prototype.
