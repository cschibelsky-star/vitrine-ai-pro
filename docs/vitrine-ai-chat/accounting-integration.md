# Integracao com a contabilizacao central — PR #127

Foi verificado no repositorio o modelo legado `App\\Models\\AiExecution`, que estende `App\\Shared\\AI\\Models\\AiExecution`, vinculado a tabela existente `ai_executions`. Sua coluna `metadata` e um array. Isso permite **planejar** uma trilha de auditoria por execucao, mas NAO prova a existencia de colunas monetarias ou de livro-razão com travamento transacional.

`ExecutionCostReconciler` transforma recibos de uso **verificados pelo provedor** num registro atribuivel a request_id, provider, modelo, scope e moeda. Sem recibo verificado retorna `pending_provider_reconciliation`; nao presume gasto zero.

## Condicoes para integracao
1. Ler o servico real que persiste `ai_executions` e confirmar os campos existentes antes de alterar registros.
2. Definir operacao transacional e idempotente por `request_id`, com restricao unica no banco e ledger persistente — em nova alteracao explicitamente autorizada, sem executar migracoes nesta etapa.
3. Reservar orcamento em storage partilhado, com contador de gasto ja consolidado na mesma transacao; o prototipo Redis de reservas no PR #127 nao fornece essa garantia.
4. Reconciliar custo e tokens de recibos reais da API; registrar moeda original e taxa de cambio apenas quando aplicavel.
5. Debitar reservas somente no fechamento confirmado; se recibo faltar, bloquear novas chamadas dentro do orcamento pendente.
6. Testar idempotencia, 402, cobranca nao comprovada, falha/retry, expiração, dois workers e queda de Redis.

**Nao aprovado para producao nem para chamadas pagas.** Nenhum segredo foi copiado ou lido.
