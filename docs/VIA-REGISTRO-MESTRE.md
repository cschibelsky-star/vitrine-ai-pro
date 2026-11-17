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
