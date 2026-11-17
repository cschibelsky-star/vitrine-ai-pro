# VIA — Registro Mestre de Governança
Atualizado em 2026-11-16. **Documento inicial; inventário e baselines ainda pendentes de verificação integral.**

## Escopo e fontes de verdade
| Componente | Repositório | Responsabilidade |
|---|---|---|
| VIA Web | [cschibelsky-star/via](https://github.com/cschibelsky-star/via) | Interface Web, APIs, widget e coordenação de voz |
| VIA APK | [cschibelsky-star/JARVIS](https://github.com/cschibelsky-star/JARVIS) | Android, builds e voz nativa |
| Integrações | [cschibelsky-star/vitrine-ai-pro](https://github.com/cschibelsky-star/vitrine-ai-pro) | Core, Factory, Cockpit e supervisão |

**Regra:** governança centralizada; código e pipelines permanecem em seus repositórios. GitHub comprova código, mas não implantação nem funcionamento em runtime.

## Registro inicial (situações históricas, a revalidar)
| Referência | Componente | Evidência histórica | Gate atual |
|---|---|---|---|
| [JARVIS PR #19](https://github.com/cschibelsky-star/JARVIS/pull/19) | APK 121 | Build 1.1.21-hml-voice-candidate; 27 testes automatizados reportados | Homologação física e baseline a reconciliar |
| [JARVIS PR #20](https://github.com/cschibelsky-star/JARVIS/pull/20) | Endpoint JARVIS | Divergência entre URL Android e roteamento HML; correção proposta | DNS, TLS, API e sessões pendentes |
| [via PR #54](https://github.com/cschibelsky-star/via/pull/54) | Voz Web/API | Logs da APK 121: 149 eventos, 6 watchdogs, 5 roteia_timeout_2500ms; 23 testes reportados | Merge, deploy e eficácia não confirmados |
| [via issue #41](https://github.com/cschibelsky-star/via/issues/41) | Voz Web/Android | Planejamento arquitetural | Acompanhar dependências |

Os dados acima foram reunidos da auditoria anterior; não substituem nova leitura de commits, PRs, CI ou runtime.

## Esquema obrigatório por mudança
- ID; título; componente; repositório; branch; commit SHA; PR/issue; responsável.
- Objetivo e comportamento esperado; endpoints e ambientes (HML/produção); dependências.
- Versão Android (versionCode/versionName), artefato e hash, quando aplicável.
- Evidências de CI, logs redigidos, testes físicos, data/hora e critérios de aceite.
- Estado do código (draft/aberto/merged), estado do deploy (não implantado/HML/produção), estado da homologação (pendente/aprovado/reprovado).
- Risco, rollback, bloqueios e próximo passo.

## Fluxo e critérios de aprovação
1. Inventariar branches, diretórios, PRs, artefatos e dependências dos três repositórios, com paginação completa.
2. Fixar baselines por SHA para Web, APK e integrações; distinguir branch, release, HML e produção.
3. Identificar duplicidades e alterações sobrepostas, sem apagar histórico.
4. Validar o caminho APK 121 → API VIA → JARVIS, incluindo URL efetivamente utilizada, DNS/TLS, saúde HTTP, voz, memória e isolamento de sessões.
5. Exigir evidências de teste e aprovação explícita antes de merge/deploy; registrar resultado e rollback.

**Proibições nesta fase:** não excluir branches, mover código, fazer merge, alterar DNS, reiniciar serviços ou implantar builds sem autorização específica.

## Pendências prioritárias
- Completar inventário e contagens exaustivas; a contagem preliminar de 428 branches e 167 PRs retornados não é total validado.
- Localizar e classificar eventual PR #143 de homologação de voz no repositório correto.
- Reconciliar logs físicos da APK 121 com o PR #54 e a configuração efetivamente servida.
- Confirmar SHA e ambiente de cada baseline antes de qualquer integração.

## Histórico de decisões
- 2026-11-16: manter Web, APK e integrações em repositórios separados e estabelecer registro mestre único.
- 2026-11-16: documento inicial criado em branch isolada; **não representa aprovação para merge ou implantação**.

## Execução M1 — reconciliação documental verificada em 2026-11-17

**Gate M1: parcialmente concluído; aprovação funcional pendente.** Consultados diretamente PRs JARVIS #19/#20, via #54 e os handoffs de ambas as branches. Não foram realizados testes físicos novos nem leitura do runtime.

### Identidade e rastreabilidade APK 121
- JARVIS PR #19: draft aberto, head `91d2260db658a0ee75f815183186adc38470a18a`; base `release/via-session-candidate120-20261110`.
- Artefato privado `VIA-121-HML-VOICE-CANDIDATE` ID 14137274181; versionCode 121, versionName `1.1.21-hml-voice-candidate`; applicationId `com.vitrineaipro.jarvis.hml`.
- SHA-256 APK informado no PR #19: `71d4506fe9639b52995af119475486bf1f1e2203cca73131a16ccfd0a0ee005f`; assinatura canônica SHA-256: `c395a66fa9430b4b5eccee3388cde8133eb7040ec2b2abf3d731cb97f95e59b3`.
- CI build assinado [43654726770](https://github.com/cschibelsky-star/JARVIS/actions/runs/43654726770) e 27 testes relatados como aprovados; testes físicos não estão demonstrados nesse handoff, apesar de existirem logs de uso real na camada Web.

### Evidência real de voz e limites
- via PR #54: draft aberto, head `863a4fd4caa89e1def47413c3958409839220af8`; base `reconcile/via-voice-halfduplex-20261105`.
- Quatro sessões e 149 eventos; seis watchdogs, cinco `roteia_timeout_2500ms`. A correção de contagem classifica 11 timeouts (5 provider + 6 watchdog), **não** resolve os cinco timeouts reais do provedor.
- CI [43764487531](https://github.com/cschibelsky-star/via/actions/runs/43764487531): 23 testes de voz aprovados em ponte/transporte simulados. Nenhum merge/deploy; eficácia no Samsung não demonstrada.
- Problemas explicitamente não resolvidos: latência total, Calendar 400, data/contexto e timeout real Roteia. Não atribuir essas correções à APK 121 ou PR #54.

### Contrato de endpoint / M2
- JARVIS PR #20: draft aberto, head `58415ad8455cd4b816c7a2ea4fe88146582b5087`, base `main`; propõe `https://jarvis-hml.vitrineaipro.com.br` como endpoint padrão do Agent Core Android.
- APK 121 preserva `https://via.vitrineiapro.com.br/` e `/api/voice` conforme handoff; distinguir API Web de endpoint JARVIS. Não afirmar que o endpoint proposto pelo #20 já esteja embutido na APK 121.
- DNS, TLS, health, roteamento real, sessão e bundle servido em HML ainda não comprovados nesta execução.

### Próximas ações obrigatórias
1. Localizar o registro de instalação física e os logs originais sanitizados, vincular timestamps/build e distinguir evidência histórica de teste pós-correção.
2. Inspecionar commits efetivamente servidos em HML, configuração proxy/DNS/TLS e endpoint usado em runtime, sem alterar serviços.
3. Revisar compatibilidade dos PRs #19/#20/#54 com baselines distintos antes de preparar candidato integrado; preservar rollback e dados.
4. Após autorização específica para implantação HML, homologar fluxo de voz e sessões com a mesma APK 121, sem reinstalação desnecessária.

**Sem merge, deploy, DNS, reinício, exclusão ou alteração da APK.**
