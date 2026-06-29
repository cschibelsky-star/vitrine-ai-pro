Master Start 1.6.1 — Correção Dashboard Widgets

Problema corrigido:
- O dashboard carregava parcialmente e exibia erro 500.
- Causa: widgets de tabela com consultas agrupadas em Lead não retornavam ID.
- Filament TableWidget exige chave de registro; sem ID, gerava:
  TableWidget::getTableRecordKey(): Return value must be of type string, null returned.

Arquivos corrigidos:
- app/Filament/Widgets/ProductInterestWidget.php
- app/Filament/Widgets/PipelineStatusWidget.php

Correção:
- As consultas agrupadas agora retornam MIN(id) as id.
- Labels nulos recebem fallback com COALESCE.
- O dashboard deve carregar sem erro nos blocos de Funil Comercial e Produtos com Maior Interesse.

Instalação:
1. Fazer backup do Master.
2. Extrair este patch em /home1/cris1649/vitrine-ai-pro.
3. Rodar:
   php artisan optimize:clear
   php artisan config:cache
   php artisan route:cache
