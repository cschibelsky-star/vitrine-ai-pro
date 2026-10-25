# Homologacao de reservas de custo

Componente experimental: `AtomicBudgetReservations`. Nao ligado ao executor.

A reserva impede que duas chamadas simultaneas consumam o mesmo teto **de reservas ativas**, sob a mesma chave de escopo. O cache deve ser compartilhado entre os workers e oferecer lock distribuido. Usar Redis homologado (nao array cache, nao cache local em multiplos containers). `requestId` suporta idempotencia enquanto o registro esta ativo.

## Restricoes obrigatorias antes de producao
- O estado atual nao contabiliza custo ja consumido e so rastreia reservas ativas. Portanto **nao representa saldo real**, nao substitui ledger persistente nem controle financeiro diario.
- Em caso de falha de lock, recusar a requisicao. Nao liberar novas chamadas como fallback.
- Concluir o ledger transacional persistente por projeto/usuario/provedor, reconcilia-lo com custo real, aplicar limite de janela e incorporar o saldo reportado pelo provedor.
- Ao executar, usar try/finally para liberar a reserva ou confirmar a despesa, com recuperacao de requisicoes interrompidas.
- Para concorrencia real: rodar dois processos independentes contra o mesmo Redis de HML, cada um tentando reservar 600 centavos sob teto 1000; exatamente um deve receber `reserved`.
- Testar lock nao disponivel, timeout, repeticao de requestId, expiracao, moeda separada por scope, retries, falha 402 e concorrencia entre containers.
- Nao habilitar chamadas pagas ou deploy sem esse aceite.

## Status
Codigo inicial registrado no PR #127. **Teste de concorrencia real e integracao ao executor nao realizados**.
