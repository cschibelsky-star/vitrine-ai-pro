# HUB-IA-001 — Estado do projeto
Atualizado: 2026-11-14
Estado global: PENDENTE (fase documental)
Missão: disponibilizar um hub de IA independente, com uso pessoal em Codex/outros clientes e futura integração ao ecossistema Vitrine IA Pro, sem acoplar os ambientes.

## Resultado concreto exigido
Entrega 1 (uso pessoal): selecionar um gateway/modelo, executar tarefa real no Codex, identificar endpoint/provedor efetivo e registrar evidência sem revelar segredos.
Entrega 2 (ecossistema): integrar ao Centro IA em HML separada, somente depois de avaliação técnica, aceite e autorização própria.
Mídia (imagem, voz, vídeo): capacidades independentes; geração de MP4 não deve ser inferida do catálogo do Codex.

## Situação confirmada
- Repositório cschibelsky-star/vitrine-ai-pro na branch main foi consultado; README.md descreve separação APP/FLOW/AI/OPS e separação de credenciais HML/PROD.
- Repositório cschibelsky-star/roteia-ai-lab possui README.md na branch main.
- Consulta V5 ao inventário identificou workspaces roteia-ai-lab, centro-ia-roteia-pr122-hml, vitrine-api-lab-hml e ai-provider-lab.
- O workspace roteia-ai-lab possui arquivo não rastreado scripts/via-code-02.js segundo a consulta de estado; preservar.
- O workspace centro-ia-roteia-pr122-hml estava um commit à frente da referência remota, segundo a consulta; preservar.
- Nenhum endpoint Invision/Roteia foi testado nesta missão; nenhuma credencial foi inspecionada.

## Estado por componente
- Governança documental: EM IMPLEMENTAÇÃO; verificar arquivos e commit.
- Inventário técnico detalhado de APIs/modelos: PENDENTE.
- Conector independente de uso pessoal: NÃO IMPLEMENTADO NESTA MISSÃO.
- Execução E2E real no Codex: NÃO VERIFICADA.
- Integração Centro IA: NÃO INICIADA NESTA MISSÃO.
- Deploy e produção: NÃO AUTORIZADOS.

## Próxima ação única
Auditar configurações e contratos reais dos conectores existentes (somente leitura), montar matriz capacidades/IDs/custos/endpoint por gateway e definir adaptador mínimo do Codex. Não criar PR ou repositório novo apenas para documentar.

## Bloqueios
Acesso à configuração local ativa do Codex e aos contratos autenticados das contas não foi verificado. Chamadas pagas exigem orçamento explícito. Não afirmar sucesso E2E.

## Critério de pronto
Prova reproduzível de tarefa Codex usando o gateway escolhido e modelo efetivo, com isolamento pessoal/empresarial, custo verificável quando aplicável, modo de reinstalação e evidência sanitizada. Teste verde ou PR não satisfaz isoladamente este critério.

## Inclusão de escopo — Avello2 (2026-11-14)
O Avello2 é componente obrigatório do Hub, além de Roteia e Invision. O objetivo contempla uso operacional no ambiente pessoal e possibilidade de integração ao ecossistema Vitrine, com contextos, credenciais, permissões, limites e auditorias separados.
Inclusão no escopo NÃO equivale a autorização de implantação em produção, habilitação de ferramentas mutáveis ou validação de disponibilidade. Auditar capacidades reais, autenticação, endpoints, compatibilidade com Codex/ChatGPT e contratos do Avello2 antes de usar.
Próxima ação inclui inventário Avello2, com os demais gateways e conectores existentes.

## Auditoria operacional confirmada — 2026-11-14
- Avello2 conectado nesta conversa: avello_status retornou calls_enabled=true, gpt_key_present=true, claude_key_present=true, remote_execution=false, google_oauth_enabled=true. avello_models retornou catálogos GPT e Claude. Ainda NÃO foi feita chamada de inferência, nem demonstrada integração ao Codex.
- V5 project_runtime_config_status: roteia-ai-lab possui ROTEIA_API_KEY configurada (somente presença), e vitrine-avello-private possui chaves e flags de Avello configuradas (somente presença). No vitrine-api-lab-hml, o status das runtime keys retornou runtime_keys_not_configured; isso não comprova falha do laboratório completo.
- Código main app/Services/Ai/AiRoutingService.php prioriza Roteia para texto, mídia e revisão com fallback; app/Services/Ai/AiMediaGenerationService.php já possui adaptador Roteia por endpoint configurado; app/Http/Controllers/Api/CentroIaBrokerController.php possui fluxo separado de mídia dinâmica/vídeo. Preservar e reutilizar, não duplicar no Hub pessoal.
- Busca de código GitHub 'invision' nos repositórios consultados não retornou arquivos na branch principal; ausência de resultado NÃO prova inexistência em branches ou no Codex local.
- Workspaces vitrine-avello-private e vitrine-avello-oauth-source têm alterações locais significativas; PROIBIDO reset/overwrite sem preservação e escopo próprio.
- Arquivos de documentação e branch não são conector operacional. Gate: PENDENTE quanto a uso real no Codex e vídeo.

## Entrega local de codigo — 2026-11-14
- Foi produzido pacote portatil HUB-IA-001-cliente-independente.zip em artefato da conversa (nao versionado neste repositorio). Contem hub.py, README.md e testes Python.
- Implementado status local (sem rede), leitura GET /models para Roteia/Invision e chamada chat bloqueada por padrao ate flag --allow-paid. Credenciais via variaveis de ambiente; nao gravadas em arquivo. Nenhuma chamada paga nesta execucao.
- Cinco testes unitarios locais passaram. Isto NAO comprova autenticacao real, compatibilidade do seletor Codex ou disponibilizacao em producao.
- Proximo passo: validar com a instalacao local Codex e contas reais em contexto autorizado; checar custo antes de inferencia. Avello2 permanece MCP separado.

## Entrega local V2 — 2026-11-15
- Criado pacote HUB-IA-001-cliente-codex-v2.zip nesta conversa, contendo hub.py, codex_preview.py, testes e README. Este artefato NÃO está instalado no computador do usuário nem versionado como código no GitHub.
- codex_preview.py gera argumentos temporários -c para Codex sem editar config.toml; provedor Invision com wire_api=responses, baseada em contrato API público. A Roteia permanece apenas em consulta HTTP até comprovação de compatibilidade de Responses/tool calling no Codex.
- 10 testes locais aprovados; nenhum teste de inferência autenticada, nenhuma execução do CLI Codex e nenhuma alteração do computador do usuário.
- Estado: PARCIAL. Gate funcional Codex: PENDENTE.
- Próximo passo único: executar diagnóstico da configuração efetiva do Codex no dispositivo do usuário por meio autorizado, preservar configuração, e validar uma tarefa real com orçamento específico antes de qualquer chamada paga.

## Validação no Windows informada pelo usuário — 2026-11-15
- Usuário executou localmente 10 testes Python (10/10, OK), confirmou Codex CLI 0.169.0 e executou hub.py status: invision credential_present=true; roteia credential_present=false no processo local. Presença de variável não valida cobrança/autenticação isoladamente.
- Configuração local consultada pelo usuário mostrou model_provider padrão Avello e model_providers.invision cadastrado. Sessão Codex com provider=invision e modelo exibido Qwen3.8 Max retornou texto OK. Status subsequente: 25.7K total (25.6K entrada, 76 saída), contexto exibido 950K, limites não disponíveis. Isto comprova resposta real pela rota selecionada, mas NÃO prova ID upstream, custo cobrado ou que o pacote Hub V2 intermediou a chamada (Codex usou provedor já configurado).
- Gate 1 (conectividade Codex -> Invision, sessão relatada): APROVADO apenas para o escopo de resposta textual. Gate código/ferramentas: PENDENTE. Gate Hub independente integrado: PENDENTE. Gate Roteia, Avello2 no Hub, multimídia, integração empresarial: PENDENTE.
- Não foi feito benchmark de programação, nem conciliado consumo financeiro. Evitar nova inferência paga sem teto de gastos e conta/limite verificados.
- Próximo passo único: validar custo e autorização de gasto para CODE01; executar teste de programação em workspace pessoal isolado (sem escrita) e registrar evidências, sem modificar configuração global.
