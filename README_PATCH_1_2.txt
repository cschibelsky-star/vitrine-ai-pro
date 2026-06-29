Vitrine AI Pro Master Start 1.2 - Comercial Avancado

Confirmado no servidor:
- Resource atual: LeadResource.php
- Pasta atual: app/Filament/Resources/LeadResource

Aplicacao:
cd /home1/cris1649
zip -r backup-master-start-1-2-antes.zip vitrine-ai-pro app.vitrineiapro.com.br
cd /home1/cris1649/vitrine-ai-pro
unzip -o master-start-1-2-comercial-avancado-patch.zip
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
