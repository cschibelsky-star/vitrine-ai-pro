Master Start 1.6.2 — Correção Dashboard Alertas / Colunas Variáveis

Problema corrigido:
- Dashboard continuava exibindo erro 500 após a correção dos widgets de tabela.
- Causa identificada no log:
  SQLSTATE[42S22]: Column not found: Unknown column 'expires_at'

Correção:
- OperationalAlertsWidget agora detecta automaticamente a coluna de vencimento disponível em licenses:
  expires_at, data_vencimento, vencimento, end_date, expires_date, valid_until.
- FinancialStatsWidget agora detecta automaticamente a coluna de valor em payments:
  amount, valor, value.
- RecentPaymentsWidget agora detecta automaticamente coluna de valor e vencimento.
- O dashboard não deve quebrar caso alguma coluna não exista.

Instalação:
1. Fazer backup do Master.
2. Extrair este patch em /home1/cris1649/vitrine-ai-pro.
3. Rodar:
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
