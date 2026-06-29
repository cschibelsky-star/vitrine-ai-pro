MASTER 1.5 — Centro IA Execução Real

Entrega:
- Serviço executor de IA.
- Execução OpenAI se api_key estiver cadastrada.
- Execução Gemini se api_key estiver cadastrada.
- Fallback simulado/manual quando não houver API Key.
- Registro em ai_executions.
- Registro em ai_consumptions.
- Registro em ai_alerts em caso de erro.
- Tela /admin/centro-ia/executar.

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.5_CENTRO_IA_EXECUCAO_REAL.zip
php install_master_1_5_centro_ia_execucao_real.php
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

Teste:
https://app.vitrineiapro.com.br/admin/centro-ia/executar
Selecionar Agente Comercial
Executar prompt
Verificar /admin/ai-executions e /admin/ai-consumptions
