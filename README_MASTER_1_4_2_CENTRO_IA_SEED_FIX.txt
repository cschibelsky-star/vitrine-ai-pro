MASTER 1.4.2 — Centro IA Seed Fix

Correção:
- Inclui slug obrigatório em provedores, agentes e registros iniciais.
- Compatível com a estrutura real das migrations:
  ai_providers: name, slug, provider_type, status, api_key, config, notes
  ai_agents: ai_provider_id, name, slug, type, product_scope, version, model_name, status, is_internal, description, config

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.4.2_PATCH_CENTRO_IA_SEED_FIX.zip
php install_master_1_4_2_centro_ia_seed_fix.php
php artisan optimize:clear
php artisan filament:cache-components
php artisan config:cache
php artisan route:cache

Teste:
- Dashboard IA com contadores acima de zero
- Provedores: OpenAI, Gemini, HeyGen, Manual / Interno
- Agentes IA: 7 agentes base
- Filas, Alertas, Memória, Logs/Execuções com registros iniciais
