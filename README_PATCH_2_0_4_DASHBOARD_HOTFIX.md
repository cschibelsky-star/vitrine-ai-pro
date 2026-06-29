# Vitrine AI Pro Master — Patch 2.0.4 Dashboard Hotfix

Este patch corrige o erro 500 causado pelo Dashboard anterior que tentou consultar uma coluna inexistente `amount`.

## O que este patch faz

- Restaura `app/Filament/Pages/Dashboard.php` para uma versão segura baseada no projeto real enviado.
- Mantém os widgets existentes do sistema:
  - MasterWelcomeWidget
  - ExecutiveStatsWidget
  - FinancialStatsWidget
  - PipelineStatusWidget
  - ProductInterestWidget
  - OperationalAlertsWidget
  - RecentLeadsWidget
  - RecentPaymentsWidget
- Não cria consultas novas no banco.
- Não usa `sum('amount')`.

## Instalação

Na raiz do projeto:

```bash
cd /home1/cris1649/vitrine-ai-pro
unzip -o vitrine-ai-pro-master-enterprise-2.0.4-dashboard-hotfix.zip
bash install_patch_2_0_4_dashboard_hotfix.sh
```

Depois abra `/admin/dashboard` e use Ctrl+F5.

## Observação técnica

Este é um hotfix de estabilização. Depois de confirmar que o painel voltou, a próxima etapa deve modernizar o visual sem alterar consultas de banco, usando os widgets e models reais já existentes no projeto.
