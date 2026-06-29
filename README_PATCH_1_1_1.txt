Vitrine AI Pro Master Start 1.1.1 - Patch de Produção Segura

1) Backup:
cd /home1/cris1649
zip -r backup-master-start-1-1-antes.zip vitrine-ai-pro app.vitrineiapro.com.br

2) Envie este ZIP para /home1/cris1649/vitrine-ai-pro

3) Extraia dentro do projeto:
cd /home1/cris1649/vitrine-ai-pro
unzip -o master-start-1-1-1-patch.zip

4) Execute:
php artisan migrate --force
php artisan db:seed --class=PlanSeeder --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

5) Teste em:
https://app.vitrineiapro.com.br/admin
