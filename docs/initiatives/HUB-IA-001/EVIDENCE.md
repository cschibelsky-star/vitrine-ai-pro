# HUB-IA-001 — Evidências (2026-11-14)
Consulta GitHub main: README de vitrine-ai-pro descreve separação APP/FLOW/AI/OPS, GitHub fonte de código e credenciais HML/PROD separadas. https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/README.md
Consulta V5 connector_health: ok=true; catálogo de projetos inclui roteia-ai-lab, centro-ia-roteia-pr122-hml, vitrine-api-lab-hml, ai-provider-lab e projetos Avello.
V5 project_status: roteia-ai-lab com Git e arquivo não rastreado scripts/via-code-02.js; centro-ia-roteia-pr122-hml com branch à frente 1 commit; vitrine-api-lab-hml com branch feature/api-lab-test-only; ai-provider-lab workspace sem repositório Git.
GitHub: PROJECT_STATE.md e DECISIONS.md criados nesta branch e atualizados para incluir Avello2.
NÃO VERIFICADO: API ativa, autenticação, modelos efetivos, vídeo, Codex E2E, custos reais e disponibilidade operacional Avello2.
Não foram realizadas chamadas pagas, deploy, merge, alterações em credenciais, HML ou produção.
Requisitos de prova para Avello2: endpoint e catálogo autorizados, capacidade efetiva (leitura/escrita conforme escopo), autenticação sem exposição de segredo, chamada real em ambiente autorizado, rastreamento e rollback.

## Novas verificações — 2026-11-14
- Ferramenta Vitrine_Avello2.avello_status: gpt_key_present=true; claude_key_present=true; calls_enabled=true; remote_execution=false; google_oauth_enabled=true; defaults gpt-6.1-sol e claude-opus-5.
- Ferramenta Vitrine_Avello2.avello_models(provider=gpt): catálogo com gpt-5.6-luna/sol/terra, gpt-6-astra/luna/sol, gpt-6.1-sol.
- Ferramenta Vitrine_Avello2.avello_models(provider=claude): catálogo com haiku/opus/sonnet. Estes são nomes informados pelo conector, não validação de identidade upstream.
- V5 project_runtime_config_status(roteia-ai-lab): ROTEIA_API_KEY configured=true, não prova autenticação funcional.
- V5 project_runtime_config_status(vitrine-avello-private): AVELLO_CALLS_ENABLED, AVELLO_GPT_API_KEY, AVELLO_CLAUDE_API_KEY, AVELLO_OAUTH_ENABLED e outras chaves com configured=true, sem leitura dos valores.
- V5 project_runtime_config_status(vitrine-api-lab-hml): retornou runtime_keys_not_configured; não extrapolar para disponibilidade global.
- GitHub main app/Services/Ai/AiRoutingService.php: https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Services/Ai/AiRoutingService.php
- GitHub main app/Services/Ai/AiMediaGenerationService.php: https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Services/Ai/AiMediaGenerationService.php
- GitHub main app/Http/Controllers/Api/CentroIaBrokerController.php: https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Http/Controllers/Api/CentroIaBrokerController.php
- Busca GitHub 'invision' em três repositórios, branch padrão: zero resultados.
- Nenhuma inferência executada, nenhuma chamada paga intencional, nenhum vídeo gerado, nenhuma alteração de runtime, nenhum deploy.
