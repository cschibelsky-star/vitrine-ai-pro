MASTER 1.4.1 — Centro IA Operacional Seed

Aplicação:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.4.1_PATCH_CENTRO_IA_OPERACIONAL_SEED.zip
php install_master_1_4_1_centro_ia_seed.php
php artisan optimize:clear
php artisan filament:cache-components
php artisan config:cache
php artisan route:cache

Teste:
- Dashboard IA com contadores > 0.
- Provedores: OpenAI, Gemini, HeyGen, Manual / Interno.
- Agentes IA com agentes-base.
- Alertas/Memória/Logs deixam de ficar vazios.
