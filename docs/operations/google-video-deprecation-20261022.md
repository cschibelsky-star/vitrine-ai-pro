# Google video model migration readiness

The four Gemini Developer API video previews listed below retire on 2026-10-22. This change prepares source auditing; it does not migrate a running service or deploy code.

| Existing model | Migration contract |
| --- | --- |
| gemini-omni-flash-preview | gemini-omni-1.1-flash via Interactions API |
| veo-3.1-generate-preview | Cloud Veo adapter, or a validated Omni adapter |
| veo-3.1-fast-generate-preview | Cloud Veo adapter, or a validated Omni adapter |
| veo-3.1-lite-generate-preview | Cloud availability must be checked separately, or a validated Omni adapter |

Omni is a video model. It is not a replacement for editorial/text/image Gemini models. Replacing a Veo model string with Omni inside predictLongRunning is incompatible with the Interactions contract. REST Interactions video output is in the model_output steps' video content; output_video is an SDK convenience field.

## Repository findings

config/marketing_video.php and config/marketing_agents.php contain Veo preview defaults. GeminiVeoVideoProvider performs direct Gemini Developer API generation and polling. GeminiVeoSceneRenderer currently dispatches through the hub with local image-motion fallback; its unused preview model read is removed by this change. Legacy operation refresh stays unchanged.

Source defaults are not evidence that a project consumed a model. Identify the Google project from authenticated model usage/metrics, then correlate model, endpoint, timestamps and request/operation IDs with sanitized application records. Never print API keys or full provider configurations.

## Blocking prerequisites

- Authenticated Google usage evidence to attribute the affected project.
- Actual deployed model/endpoint selection, including dynamic hub/gateway records.
- Cloud project, authentication, supported region and quota/access for preserving Veo.
- Mocked payload, operation polling, error, asset-ingestion and consumer contract tests for the chosen adapter.
- Homologation validation and review before production changes.
- Preserve historical operation IDs; polling must not resubmit generation.

Regular/fast Cloud model IDs are veo-3.1-generate-001 and veo-3.1-fast-generate-001. Lite -001 remains Preview in the cited Cloud documentation. No blind model substitution is included here. Remaining active preview defaults are unresolved migration blockers.

## Offline checks

python3 -m unittest discover -s tests/offline -p 'test_google_video_model_audit.py'
python3 scripts/audit_google_video_models.py --root . --fail-on-affected

The read-only auditor reports exact model references from app/config/routes, never source lines or credentials. It excludes environment files and credential/storage/log locations. Its explicit release gate exits 2 when affected references remain; it is not installed in current CI or schedules. Four regressions cover migration families, credential exclusions, stable-model boundaries and the CLI exit gate. These tests are not API or end-to-end migration tests.

Sources:
- https://ai.google.dev/gemini-api/docs/deprecations/
- https://ai.google.dev/gemini-api/docs/omni
- https://docs.cloud.google.com/gemini-enterprise-agent-platform/models/veo/3-1-generate
