Master Start 1.5.2 — Correção Cobranças / Select sem label

Correção aplicada:
- Corrige erro 500 em /admin/payments/create.
- O erro ocorria quando o Select de Contrato / Proposta recebia algum registro com título nulo.
- O PaymentResource agora monta labels seguros para contratos, usando fallback com Cliente / Produto / Plano.
- Também aplica fallback nos selects de Cliente, Produto e Plano para impedir labels nulos.

Após extrair:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
Financeiro e Contratos > Cobranças > Criar
Selecionar Contrato / Proposta
Validar preenchimento automático de Cliente, Produto, Plano, Descrição e Valor.
