# Catálogo inicial de departamentos, papéis e habilidades (proposto, não provisionado)

## Separação e departamentos
| Espaço | Departamento | Papéis de agente | Habilidades prioritárias |
|---|---|---|---|
| DEV | Diretoria técnica / planejamento | coordenador de missão | `dev.intake`, `dev.plan`, `dev.decompose` |
| DEV | Arquitetura | arquiteto de software | `dev.architecture.review`, `dev.contracts` |
| DEV | Desenvolvimento | backend, frontend, mobile | `dev.debug`, `dev.implement`, `dev.refactor` |
| DEV | Qualidade e segurança | QA, revisor de PR, auditor | `dev.tests`, `dev.diff.review`, `dev.security.review` |
| DEV | Integrações | especialista API e dados | `dev.api.validate`, `dev.integration.test` |
| ECO | Diretoria operacional | gestor de missão comercial | `eco.intake`, `eco.prioritize` |
| ECO | Marketing IA | planejador, copywriter, criativo | `eco.marketing.plan`, `eco.copy`, `eco.design.brief` |
| ECO | Editorial / TV | pesquisador, editor, revisor | `eco.research`, `eco.editorial.verify`, `eco.video.script` |
| ECO | Produtos e atendimento | assistente de suporte, especialista produto | `eco.support.triage`, `eco.product.qa` |
| ECO | Automação e análise | analista de dados, workflow designer | `eco.analytics`, `eco.workflow.plan` |
| DEV/ECO (lógicas separadas) | Supervisão independente | VIA DEV, VIA ECO | `supervision.verify`, `supervision.approve`, `supervision.rework` |

## Contrato de uma Skill
Cada skill deve declarar: `skill_id`, `version`, `description`, `domain` DEV/ECO, entradas e saídas, ferramentas necessárias, permissões, dados que pode acessar, testes, métricas de aceite, risco, supervisor, política de custos e status (proposed/implemented/tested/approved/revoked).

**Nota:** "Skill" neste domínio é uma competência executável/versionada do Escritório, não necessariamente um arquivo de Skills do ChatGPT. Aproveitar Skills já instaladas quando compatíveis e autorizadas; não afirmar instalação automática.

## Perfis de agente — exemplos não implantados

### DEV — Agente de correção
- Papel: corrigir bug, respeitar branch/sandbox, produzir diff mínimo.
- Skills: `dev.debug`, `dev.tests`, `dev.diff.review`.
- Ferramentas: leitura de arquivos, patch e testes, escopadas à missão.
- Modelo: escolhido pelo HUB DEV somente entre candidatos comprovados com tool calling e orçamento.
- Aceite: testes relevantes verdes, diff auditável, supervisor independente, nenhuma regressão conhecida.

### ECO — Agente de campanha
- Papel: executar briefing editorial sob Marketing IA.
- Skills: `eco.marketing.plan`, `eco.copy`, `eco.design.brief`.
- Ferramentas: produção de rascunhos e evidências; **publicação não autorizada por padrão**.
- Aceite: aderência ao briefing, direitos/identidade visual, revisão e aprovação editorial.
- Nunca misturar dados de clientes/tenants.

## Classificação de modelos por habilidade
| Campo | Critério |
|---|---|
| `declared_capability` | afirmação do fornecedor, NÃO homologação |
| `tested_capability` | execução real e resultado observável |
| `approved_capability` | liberação por ambiente e versão |
| `quality_score` | amostra, rubric e baseline obrigatórios |
| `cost_per_accepted_task` | custo conciliado ou desconhecido |
| `tool_protocol` | Responses/Anthropic/outro; testes de tools/streaming |
| `last_verified_at` | timestamp da evidência |
| `risk_class` | baixo, médio, alto conforme ação/dados |

Sem benchmark, o modelo fica 'candidato' e não pode ser recomendado automaticamente para operações críticas.

## Primeiros candidatos à matriz
- Invision / Qwen3.8 Max: código gerado em teste relatado; não inferir qualidade universal.
- Invision / Invision Coding: teste TOOL-CODE-02 5/5 informado pelo usuário; logs independentes e custos pendentes.
- Invision / Qwen3.8 Flash Next: somente resposta textual curta relatada.
- Roteia / modelos: integração no Centro IA existe; inventário detalhado de habilidades e custo pendente.
- Avello2 / modelos: conector consultado em conversa; compatibilidade como executor DEV/ECO e custos pendentes.
