MASTER 2.0 OPERACIONAL

Inclui:
- Assinaturas SaaS vinculadas a empresas, planos e licenças.
- Log de webhooks Asaas.
- Serviço de ativação/suspensão/cancelamento de licenças por eventos Asaas.
- HeyGen Central: avatares, fila de vídeos e ledger de créditos.
- Endpoint de teste HeyGen.

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_2.0_OPERACIONAL.zip
php install_master_2_0_operacional.php
php artisan migrate --force
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan route:list | grep heygen
php artisan migrate:status | grep 2026_06_21_200000

Testes:
https://app.vitrineiapro.com.br/admin/centro-ia/heygen/testar

Painéis novos:
- Financeiro e Contratos > Assinaturas
- Centro de IA > Vídeos HeyGen

Observação:
A estrutura usa companies como base de clientes para evitar duplicação de tabela clients.
