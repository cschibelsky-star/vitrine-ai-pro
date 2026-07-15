# Classificação complementar de Models — 2026-07-15

## CORE

- `Setting`: configuração institucional global; sem dependência de produto.
- `Subscription`: assinatura vinculada a Company, Plan e License.
- `SupportTicket`: suporte transversal vinculado a Company, User, Product, Module e Contract.
- `Module`: catálogo de módulos ligado a Product, PlanModule e CompanyModule.

## SHARED / AI / Media

- `HeygenAvatar`: catálogo de avatares utilizado pelos jobs de vídeo.
- `HeygenVideoJob`: execução compartilhada de vídeo IA ligada a Company, AiAgent, AiProvider e avatar.
- `HeygenCreditLedger`: razão de consumo de créditos ligada a Company e ao job de vídeo.

## Lote físico executado

Somente `Setting` foi movido neste lote, por não possuir relacionamentos e apresentar menor risco de regressão.

Namespace canônico:

`App\Core\Settings\Models\Setting`

Compatibilidade preservada:

`App\Models\Setting`

## Itens preservados

- migrations;
- tabela `settings`;
- dados existentes;
- imports antigos;
- Models com relacionamentos ainda não movidos.
