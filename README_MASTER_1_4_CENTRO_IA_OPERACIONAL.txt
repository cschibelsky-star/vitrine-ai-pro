MASTER 1.4 — CENTRO IA OPERACIONAL

Aplicação:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.4_CENTRO_IA_OPERACIONAL.zip
php install_master_1_4_centro_ia.php
php artisan optimize:clear
php artisan filament:cache-components
php artisan config:cache
php artisan route:cache
php artisan route:list | grep centro

Teste esperado:
- admin/centro-ia aparece no route:list
- /admin abre sem erro 500
- Menu Centro IA > Dashboard IA abre
- Agentes IA/Provedores/Filas mostram botão Criar
