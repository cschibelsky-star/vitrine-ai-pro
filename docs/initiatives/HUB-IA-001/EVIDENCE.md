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

## Evidencia de codigo local — 2026-11-14
- Runtime local isolado: python -m unittest discover -s tests -v -> 5 testes, 0 falhas.
- Comando python hub.py status --provider roteia -> URL base https://api.roteia.ai/v1; credential_present=false no runtime temporario.
- Comando python hub.py status --provider invision -> URL base https://invision.dreamvision.com.br/v1; credential_present=false no runtime temporario.
- Documentacao oficial URLs: https://roteia.ai/docs/ e https://invision.dreamvision.com.br/docs.
- Artefato ZIP disponivel somente como anexo nesta conversa; nao afirmar que esta versionado, instalado ou publicado.
- Sem requests autenticados, sem inferencia, sem teste real Codex, sem video, sem merge, sem deploy.

## Evidência local V2 — 2026-11-15
- Arquivos criados: hub_ia_001/codex_preview.py, tests/test_codex_preview.py e README atualizado, adicionados ao pacote ZIP V2 na conversa.
- Comando: python -m unittest discover -s tests -v -> 10 testes aprovados, 0 falhas em runtime isolado.
- Comando: python codex_preview.py --model qwen/qwen3.8-max --format json -> argumentos para model_provider=hub_invision, base_url=https://invision.dreamvision.com.br/v1, wire_api=responses, env_key=INVISION_API_KEY, sem valores secretos.
- https://invision.dreamvision.com.br/docs confirma POST /v1/responses, GET /v1/models e opção aplicativo Codex; https://roteia.ai/docs confirma /v1/chat/completions. Endpoints documentados não comprovam disponibilidade/account compatibility.
- Não houve alteração de config.toml, nenhuma inferência paga, nem implantação, merge ou vídeo.

## Evidência recebida do usuário — Codex Invision 2026-11-15
Fonte: saída textual do PowerShell/Codex fornecida pelo usuário nesta conversa, não reproduzida por ferramenta remota do assistente.
- Python 3.12.10, Codex CLI 0.169.0.
- python -m unittest discover -s tests -v: 10 testes OK em 0.024s.
- python hub.py status --provider invision: base_url https://invision.dreamvision.com.br/v1; credential_present=true; codex_provider_verified=false no próprio utilitário. Para roteia: credential_present=false.
- Inspeção local config.toml pelo usuário: model_provider = Avello; seções model_providers.invision e model_providers.Avello presentes. Nenhuma alteração global comprovada.
- /status antes da inferência: model provider invision, Qwen3.8 Max, token usage 0.
- Resposta do Codex a 'Responda apenas com: OK': OK; interface exibiu Worked for 22s.
- /status depois: invision / Qwen3.8 Max, 25.7K tokens totais (25.6K input + 76 output), contexto 950K, limits not available.
- Interpretação: resposta pela sessão Invision comprovada por saída fornecida; modelo upstream, tokenização de cobrança, request_id e custo debitado NÃO VERIFICADOS. O utilitário local não foi comprovado como proxy da inferência.
- Registro preserva resumo em vez de identificador de sessão do usuário, tokens de autenticação ou segredos.
