Master Start 1.6 — Dashboard Executivo e Indicadores

Objetivo:
Transformar a tela inicial do Centro Operacional Master em painel de decisão.

Inclui:
- Dashboard Executivo
- Indicadores de leads, clientes, contratos, licenças e módulos
- Indicadores financeiros: pagas, abertas, vencidas e total lançado
- Resumo do funil comercial
- Produtos com maior interesse
- Alertas operacionais
- Últimos leads
- Últimas cobranças

Instalação:
1. Fazer backup do Master
2. Extrair este patch na pasta /home1/cris1649/vitrine-ai-pro
3. Rodar:
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache

Observação:
Este patch não cria novas tabelas. Ele usa os dados já existentes das versões 1.1.1 a 1.5.2.
