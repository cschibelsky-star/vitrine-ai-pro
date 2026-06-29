Vitrine AI Pro Master Start 1.3 - Integração Landing Page + Comercial

Este patch cria:
- app/Http/Controllers/Api/LeadCaptureController.php
- routes/api.php

E atualiza:
- bootstrap/app.php

Correções incluídas:
- HTTP status correto: 201 Created
- API carregada no Laravel 12 via bootstrap/app.php
- Rota POST /api/leads
- Throttle básico: 60 requisições por minuto por IP

Aplicação:
1) Backup:
cd /home1/cris1649
zip -r backup-master-start-1-3-antes.zip vitrine-ai-pro app.vitrineiapro.com.br

2) Enviar este ZIP para:
/home1/cris1649/vitrine-ai-pro

3) Extrair:
cd /home1/cris1649/vitrine-ai-pro
unzip -o master-start-1-3-api-leads-patch.zip

4) Limpar cache:
php artisan optimize:clear
php artisan config:cache
php artisan route:cache

5) Testar:
curl -X POST https://app.vitrineiapro.com.br/api/leads \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "empresa": "Cliente Teste API",
    "contato": "Cristian Teste",
    "telefone": "19999999999",
    "email": "teste@vitrineaipro.com.br",
    "produto_interesse": "TV Digital Enterprise",
    "plano_sugerido": "Enterprise",
    "origem_lead": "Site",
    "observacoes": "Teste de captura automática da landing page."
  }'
