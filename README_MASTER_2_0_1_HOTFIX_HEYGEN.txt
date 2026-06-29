MASTER 2.0.1 HOTFIX HEYGEN

Correção:
- Corrige Resource HeygenVideoJobResource.
- Corrige páginas List/Create/Edit.
- Evita erro de edição no Filament.
- Corrige opções de Company com fallback entre nome/name/razao_social/company_name.
- Adiciona botão Assistir quando video_url existir.

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_2.0.1_HOTFIX_HEYGEN.zip
php install_master_2_0_1_hotfix_heygen.php
php artisan optimize:clear
php artisan filament:cache-components
php artisan route:clear
php artisan route:list | grep heygen

Teste:
https://app.vitrineiapro.com.br/admin/heygen-video-jobs
Clique em Editar no registro criado.
