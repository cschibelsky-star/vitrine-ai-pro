# Avaliação gratuita: Core / Licenciamento

## Escopo desta entrega

- Serviço interno de matrícula em plano com ciclo trial ativo, 30 dias e valor zero.
- Serialização concorrente por cliente; se houver qualquer licença prévia do cliente para o produto, recusar a tentativa em vez de duplicar ou sobrescrever contrato.
- Validação de acesso do trial sem concessão quando faltam datas ou no dia do vencimento.
- Testes unitários de janela temporal.

## Limites e gates

**Não há rota pública de ativação nesta entrega.** Antes de liberar cadastro externo, implementar autenticação do cliente, verificação de e-mail, consentimentos, antifraude/rate limiting e autorização por empresa/produto. Nunca expor este serviço sem esses controles.

O serviço não ativa ou suspende automaticamente permissões dos produtos: consumidores de licença devem consultar a validação ou um entitlement central antes de permitir acesso. Não assumir que status Trial concede acesso indefinido. Para registros antigos ou estados inválidos, falhar fechado.

A restrição a uma licença existente por produto é conservadora. Uma trilha imutável de histórico de avaliações e índice único no banco ficam para alteração de schema explicitamente autorizada.

Pendências antes de HML: testes de integração com banco, validação da coluna plan_id, adequação à autenticação e RBAC reais, provisionador HML e integração do gate de licenças ao produto consumidor; alertas de expiração; conversão sem perda de dados; InfinityPay. Sem migration/merge/deploy nesta entrega.
