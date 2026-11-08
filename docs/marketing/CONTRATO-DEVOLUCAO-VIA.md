# Devolução de engenharia do Marketing IA para correção

Status: governança e integração de código nos PRs Marketing #162, VIA #37 e Factory #21, com origem no #20; sem homologação na VPS. Os nove agentes de criação e seus contratos com Core/Centro IA/Vitrine Social Mídia permanecem preservados.

## Regras interoperáveis

O handoff v1.0 mantém seus campos atuais. O gate PHP continua com `can_advance=false` e `evidence_verified=false`, retornando blocked ou pending_external_verification. Nunca emitir autorização pelo sucesso de validação estrutural.

A VIA traduz essa saída apenas por `adaptMarketingAssessment`, rejeitando flags contraditórias e tentativas de elevar privilégios. O avaliador externo exige adicionalmente review.github_login e configuração confiável do servidor: repository, prNumber, agentLogins e requiredChecks com checkId/workflowId/runId para cada check obrigatório. Uma URL ou o texto approved enviado pelo agente não autentica CI/revisão.

Os checks obrigatórios pertencem à política do supervisor, não à escolha do executor. A coleta real deve comprovar todos os workflows/jobs exigidos, revisão vigente do SHA exato e identidade independente do autor e do executor. Marcação source não substitui essa coleta. Os snapshots históricos do piloto não comprovam commits posteriores.

## Devolução

O registro confiável da tarefa fornece: task_id, repository, result_sha, etapa G0–G6, responsável por correção, tentativas e limites. O planejamento de devolução na VIA:

- Reprovação: needs_correction ao responsável pela etapa, ignorando next_owner proposto pelo executor.
- Identidade/commit divergente: blocked, sem redirecionar trabalho.
- Responsável ausente, tentativas ou orçamento excedido: escalated.
- Correção concluída: novo SHA, novos testes e revisão; continua pending_supervision.

canAdvance, dispatchAllowed e productionAllowed permanecem false. O planejador é puro: não grava missão, não altera fila e não executa agente. A integração operacional exige persistência auditável, controle concorrente de versão, idempotência/outbox e rejeição de eventos/pareceres antigos.

## Aceite e próxima etapa

Testes PHP cobrem review recusado apesar de next_owner, state needs_correction e evidência textual antiga após novo SHA. Testes VIA cobrem devolução planejada, limites, commit antigo, falha GitHub, tradução do contrato e os quatro critérios de segurança identificados na auditoria.

Após CI e revisão independente, apresentar roteiro HML para autorização: falha -> devolução persistida -> responsável corrige -> novo SHA -> novos checks/review -> reavaliação. Incluir timeout, replay, concorrência e indisponibilidade. Não confundir essa evidência futura com os testes offline atuais. Merge, homologação e produção não foram autorizados nesta etapa.


## Integração interna com persistência canônica (continuidade)

VIA #37 passa a oferecer supervisão interna por runObserverMission(mission, {supervision}), com repository/escopo/política carregados pelo servidor. Factory #20 fornece MissionHandoffRepository sobre factory_missions, o mesmo registro de FactoryMission; nenhuma tarefa criativa de MarketingCampaignTask é reutilizada para engenharia.

A reprovação é gravada atomicamente com revisão, responsável, auditoria, recibo e evento passivo correction_required. O próximo agente não é agendado. Retry/reinício não duplica a correção; submissão de novo SHA invalida o candidato anterior e exige nova prova/revisão. A identidade da missão e o escopo são conferidos antes da escrita, e gravação falha não produz relatório completed.

A prova integrada executa VIA + adapter PDO Factory em SQLite temporário, incluindo concorrência e revisão antiga, com dependência pinada por commit no CI. Não habilita endpoint, worker, banco real ou produção. O bridge é exclusivo de testes. A integração com host autenticado e Laravel/MySQL da VPS, autorização Core e execução real da correção ainda depende de revisão independente e homologação específica. Os nove agentes, Core, Centro IA e Vitrine Social Mídia não foram alterados.


## Base executável para validação do runtime

A integração Factory corrente está no PR #21, criado sobre a branch do #19 e acrescido do delta de governança do #20. O #20 isolado parte de main, que não contém Compose/runtime; ele é fonte de governança, não a linha de implantação. #21 preserva modelos/migrations/APIs existentes e bloqueia o executor legado quando existe handoff_supervision. O CI da VIA usa commit fixo dessa linha integrada. Nenhum merge da cadeia #12/#17/#19/#21 ou inclusão implícita de #18 foi autorizado.
