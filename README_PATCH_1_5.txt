Master Start 1.5 — Financeiro, Contratos e Cobrança

Objetivo:
- Criar controle de contratos/propostas por cliente.
- Evoluir Financeiro para Cobranças com implantação, mensalidade e módulos extras.
- Preparar campos para integração futura com Asaas.

Arquivos principais:
- app/Models/Contract.php
- app/Models/Payment.php
- app/Filament/Resources/ContractResource.php
- app/Filament/Resources/PaymentResource.php
- database/migrations/2026_06_20_000201_create_contracts_table.php
- database/migrations/2026_06_20_000202_add_financial_fields_to_payments_table.php

Menus esperados:
- Financeiro e Contratos > Contratos / Propostas
- Financeiro e Contratos > Cobranças

Fluxo operacional:
1. Criar Contrato / Proposta para o cliente.
2. Informar produto, plano, implantação, mensalidade e módulos extras.
3. Criar Cobranças vinculadas ao cliente e, se necessário, ao contrato.
4. Controlar status: Aberto, Pago, Atrasado, Cancelado ou Suspenso.

Observação:
A versão 1.5 não integra Asaas automaticamente ainda. Ela prepara os campos link_pagamento, referencia_externa e asaas_id para a próxima etapa.
