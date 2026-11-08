# Marketing IA — handoff de governança supervisionada

Atualização: 08/11/2026. Estado: drafts de integração; sem homologação VPS, merge ou produção. GitHub permanece a fonte de verdade e CI verde não concede avanço.

## Linha de trabalho e fonte canônica

| Componente | Referência | Papel |
| --- | --- | --- |
| Marketing IA | vitrine-ai-pro #162 | contratos, gates estruturais e regressões; nove agentes preservados |
| VIA | via #37 | evidência externa, plano, supervisão interna e testes interprodutos |
| Factory governança | VitrineAI-Factory #20, f4766181cfad4bf024c890af8d7d7b2f65896742 | origem dos nove arquivos de governança/adapter |
| Factory executável | #12 -> #17 -> #19, eb82fa4a9d523c3e21104498ee6a4e9c052c526f | Compose/runtime e modelos/etapas/API existentes |
| Factory integrada | #21, base feat/via-stage-supervision-20261107 | runtime canônico com governança; evita promover #20 isoladamente |

A main da Factory não contém docker-compose.factory.yml. O erro do Baseline no #20 revelou a diferença entre a linha documental e a executável; o arquivo não foi recriado. #21 nasce de #19 e aplica a governança já existente, sem merge nem alteração das branches anteriores. EnginePolicy #18 é uma revisão independente pendente.

## Comportamento implementado em branches

- Marketing: AutonomousHandoffGate continua can_advance=false/evidence_verified=false. CLI retorna 1=bloqueado, 2=inválido, 3=pendência externa.
- VIA: política de workflows/identidades fornecida pelo servidor; evidência imutável ligada ao handoff; SHA/review antigos ou origem fabricada bloqueiam.
- Factory: MissionHandoffRepository grava no FactoryMission existente status/revisão, auditoria, recibo idempotente e outbox passiva. Correção exige responsável e novo SHA; limites escalam.
- Orquestrador VIA: entrada interna persiste o parecer e consulta o bloqueio do próximo agente. O servidor HTTP padrão não ativa essa entrada.
- #21: FactoryMissionRunner.run/requestCorrection recusam missões com handoff_supervision antes de executar/reabrir etapas ou sobrescrever o estado. Missões legadas sem marcador preservam seus gates.

Os nove agentes criativos permanecem marketing_director, product_market_strategist, campaign_planner, copy_content, creative_director, video_producer, social_distribution, qa_brand_guardian e performance_analyst. O último continua condicionado a métricas. Engenharia não usa MarketingCampaignTask como missão. Core/Centro IA/Social, publicadores e credenciais não foram alterados.

## Evidências e limites

Marketing #162 tinha 32 testes/100 assertions aprovados no run 42504287495. VIA #37 tinha 98 testes de handoff/PDO + 4 agent-hub, sem skips, no run 42504444112. Factory #20 tinha 16 casos do gate + 16 de PDO aprovados no run 42504062933; seu Baseline run 42504062881 falhou por ausência de Compose e permanece como evidência histórica.

A linha #21 deve ser avaliada pelo head exato nos workflows Agent Excellence Gate Tests, Factory Baseline CI e Factory Application CI. Estes últimos executam build Docker, smoke Laravel, suíte real e preservação de dados/arquivo criptografado após restart/recriação no runner descartável. A VIA passa a usar o adapter da linha integrada em commit pinado. Resultados atuais e links por SHA constam nas descrições dos PRs; não reutilizar um CI antigo para um head novo.

Provas em CI não são homologação VPS, Core ACL, MySQL/InnoDB, vínculo real a FactoryAgent ou correção automática de código. A outbox é passiva, e canAdvance/dispatchAllowed permanecem false. Não houve banco real/migration aplicada, publicação social, consumo de IA, deploy ou ativação de executor.

## Próximos responsáveis e gates

1. QA/revisor independente: conferir os heads, reproduzir aceite e revisar o bloqueio do runner e a fronteira de confiança.
2. Arquiteto: aprovar a sequência explícita #12/#17/#19/#21 e a relação com #18, sem promover #20 isoladamente.
3. Segurança/Core: verificar host, ACL, escopos e vínculo de responsáveis autenticados.
4. DevOps: preparar roteiro HML isolado com MySQL, backup/rollback, domínio/TLS e Cockpit, para autorização específica antes de executar.
5. Proprietário: autorizar separadamente homologação, merge, implantação e eventual execução de agentes.
