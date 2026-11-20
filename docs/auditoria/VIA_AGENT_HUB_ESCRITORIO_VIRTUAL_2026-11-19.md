# Auditoria cruzada — VIA Agent Hub × Escritório Virtual × Factory
Data: 2026-11-19
Status: auditoria estática parcial; sem execução em VPS ou produção.

## Limites da verificação
Repositórios consultados: cschibelsky-star/vitrine-ai-pro, VitrineAI-Factory e VitrineAI-FACTORY-ENTERPRISE-X. Nenhum repositório identificado inequivocamente como Escritório Virtual ou VIA Agent Hub na busca de nomes. Não inferir inexistência de módulos em outras branches ou repositórios privados.

## Evidências verificadas
1. app/Factory/Production/Services/FactoryProductionOrchestrator.php: pipeline release, decision, workflow, product manifest, documentation, QA e history; o campo next_stage declara pendência de conexão Builder/Installer. Classificação: ADAPT; não homologado.
2. app/Marketing/Domain/Agents/AgentRegistry.php: registro de agentes, validação de dependências, detecção de ciclos, proibição de publicar e gastar na V1. Classificação: EXTRACT_PATTERN, não reutilizar acoplamento marketing.
3. config/marketing_agents.php: nove agentes, um desabilitado, approval_mode assisted. Classificação: ADAPT, sem afirmar runtime universal.
4. app/Factory/Policies/Concerns/ChecksFactoryPermissions.php: método allowed() retorna true se o usuário existe mas não implementa hasPermissionTo. RISCO ALTO potencial de fail-open. Investigar implementação de User, policies e testes antes de corrigir; adicionar teste negativo e adotar fail-closed quando não houver sistema de autorização compatível.
5. VitrineAI-Factory/factory-core/database/migrations/2026_07_03_000001_create_factory_core_tables.php: tabelas factory_agents, factory_missions, factory_capabilities, factory_engines, produtos e blueprints. Modelagem existente não comprova migração aplicada ou execução operacional.
6. VitrineAI-Factory/factory-core/BUILD_0_1_STATUS.md: documentação da build inicial confirma schema/models/seeder/Products Resource; demais resources constavam como próximos passos.

## Arquitetura alvo
Escritório Virtual = camada organizacional (departamentos, gestores, funcionários digitais, KPIs).
VIA Agent Hub = runtime multiagente compartilhado (missões, tarefas, políticas, evidências, aprovação, auditoria).
Vitrine IA Flow = automação e eventos, conforme integração a validar.
Factory = domínio de produção, exposto por provider read-only inicialmente.

## Matriz de decisão
| Componente | Origem | Decisão | Condição |
| --- | --- | --- | --- |
| FactoryProductionOrchestrator | vitrine-ai-pro | ADAPT | mapear operações e efeitos colaterais; não expor produce() no Observer |
| AgentRegistry marketing | vitrine-ai-pro | EXTRACT_PATTERN | remover dependência de contagem fixa e marketing |
| Factory policies | vitrine-ai-pro | HARDEN | testes de negação e revisão fail-open |
| factory_agents/missions | VitrineAI-Factory | MAP | contrato e migrações; não acoplar tabelas diretamente |
| Escritório Virtual | não localizado inequivocamente | DISCOVER | identificar repositório/caminho canônico |
| VIA Agent Hub runtime | protótipo local relatado | VERIFY | confirmar testes, persistência e integração real |

## Plano de execução
P0: identificar fonte canônica do Escritório Virtual e escopo da governança Master; confirmar ownership.
P0: auditar ChecksFactoryPermissions com testes negativos; corrigir somente em PR separado após entender compatibilidade.
P1: definir contratos versionados Mission, Agent, Capability, ToolInvocation e Evidence.
P1: implementar Factory Observer read-only, sem SSH arbitrário, deploy ou escrita.
P1: testes de autorização default-deny, redaction e audit trail.
P2: conectar Escritório Virtual como cliente do Agent Hub; não duplicar orquestradores.
P2: benchmark missão real com evidências e aprovação humana.

## Critérios de aceite
- Contratos versionados e testes passando.
- Toda chamada de ferramenta auditada e autorizada.
- Nenhum write/deploy habilitado no piloto Observer.
- Origem e estado de cada capacidade demonstrados por código + execução + evidência.
- Nenhuma publicação em produção sem aprovação.

## Referências
- https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Factory/Production/Services/FactoryProductionOrchestrator.php
- https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Marketing/Domain/Agents/AgentRegistry.php
- https://github.com/cschibelsky-star/vitrine-ai-pro/blob/main/app/Factory/Policies/Concerns/ChecksFactoryPermissions.php
- https://github.com/cschibelsky-star/VitrineAI-Factory/blob/main/factory-core/BUILD_0_1_STATUS.md
