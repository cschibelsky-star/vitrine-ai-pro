MASTER 1.7 — Execução Real OpenAI/Gemini

Entrega:
- Corrige AiExecutionService.
- Lê API Key da tabela ai_providers.
- Usa fallback do .env quando api_key estiver vazia.
- Executa OpenAI real.
- Executa Gemini real.
- Registra execução e consumo.
- Registra alerta em caso de erro.
- Adiciona endpoint de teste de provedor.

Aplicar:
cd /home1/cris1649/vitrine-ai-pro
unzip -o MASTER_1.7_EXECUCAO_REAL.zip
php install_master_1_7_execucao_real.php
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan route:list | grep provedores

Testes:
https://app.vitrineiapro.com.br/admin/centro-ia/provedores/1/testar
https://app.vitrineiapro.com.br/admin/centro-ia/provedores/2/testar

Depois:
https://app.vitrineiapro.com.br/admin/centro-ia/executar
Selecionar Agente Comercial e executar prompt real.
