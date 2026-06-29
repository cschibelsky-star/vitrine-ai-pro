MASTER 1.6 — Asaas Webhook Operacional

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.6_ASAAS_WEBHOOK_OPERACIONAL.zip
php install_master_1_6_asaas_webhook.php
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan route:list | grep asaas

URL para cadastrar no Asaas:
https://app.vitrineiapro.com.br/api/asaas/webhook

Eventos:
PAYMENT_CREATED
PAYMENT_RECEIVED
PAYMENT_CONFIRMED
PAYMENT_OVERDUE
PAYMENT_DELETED
