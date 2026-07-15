# Classificação do inventário arquitetural — 2026-07-15

Gerado por `php artisan architecture:inventory` em 2026-07-15T14:48:23.020823Z.

## Totais

- **Models:** 32 — Commercial 1, Core 9, Legacy 3, Unclassified 19
- **Services:** 34 — Commercial 2, Core 1, Factory 23, Legacy 3, Unclassified 5
- **Arquivos Filament Resources:** 124 — Commercial 4, Core 36, Legacy 15, Unclassified 69
- **Filament Pages:** 9 — Factory 2, Unclassified 7
- **Commands:** 4 — Commercial 2, Factory 1, Unclassified 1

## Models

| Model | Camada | Decisão inicial |
|---|---|---|
| `Agendamento` | Unclassified | revisão manual antes de mover |
| `AiAgent` | Unclassified | revisão manual antes de mover |
| `AiAlert` | Unclassified | revisão manual antes de mover |
| `AiConsumption` | Unclassified | revisão manual antes de mover |
| `AiExecution` | Unclassified | revisão manual antes de mover |
| `AiMemory` | Unclassified | revisão manual antes de mover |
| `AiProvider` | Unclassified | revisão manual antes de mover |
| `AiQueue` | Unclassified | revisão manual antes de mover |
| `Animal` | Legacy | quarentena; não apagar |
| `Cliente` | Unclassified | revisão manual antes de mover |
| `Company` | Core | manter no CORE; mover em lote posterior com aliases |
| `CompanyModule` | Core | manter no CORE; mover em lote posterior com aliases |
| `Contract` | Core | manter no CORE; mover em lote posterior com aliases |
| `Financeiro` | Unclassified | revisão manual antes de mover |
| `Fornecedor` | Unclassified | revisão manual antes de mover |
| `HeygenAvatar` | Unclassified | revisão manual antes de mover |
| `HeygenCreditLedger` | Unclassified | revisão manual antes de mover |
| `HeygenVideoJob` | Unclassified | revisão manual antes de mover |
| `Lead` | Commercial | migrar para Commercial em lote próprio |
| `License` | Core | manter no CORE; mover em lote posterior com aliases |
| `Log` | Unclassified | revisão manual antes de mover |
| `Module` | Unclassified | revisão manual antes de mover |
| `Payment` | Core | manter no CORE; mover em lote posterior com aliases |
| `Plan` | Core | manter no CORE; mover em lote posterior com aliases |
| `PlanModule` | Core | manter no CORE; mover em lote posterior com aliases |
| `Product` | Core | manter no CORE; mover em lote posterior com aliases |
| `Prontuario` | Legacy | quarentena; não apagar |
| `Setting` | Unclassified | revisão manual antes de mover |
| `Subscription` | Unclassified | revisão manual antes de mover |
| `SupportTicket` | Unclassified | revisão manual antes de mover |
| `User` | Core | manter no CORE; mover em lote posterior com aliases |
| `Vacina` | Legacy | quarentena; não apagar |

## Services

| Service | Camada | Decisão inicial |
|---|---|---|
| `CommercialFactory/Services/CommercialFactoryIntakeService.php` | Commercial | mover neste lote com wrapper de compatibilidade |
| `CommercialFactory/Services/CommercialFactoryStatusService.php` | Commercial | mover neste lote com wrapper de compatibilidade |
| `Factory/Builder/Services/ModuleQaService.php` | Factory | manter |
| `Factory/Core/Services/FactoryUpdateService.php` | Factory | manter |
| `Factory/Dashboard/Services/WidgetIntelligenceService.php` | Factory | manter |
| `Factory/EnterpriseMaturity/Services/EnterpriseNameService.php` | Factory | manter |
| `Factory/FinalMaster/Services/FactoryFinalMasterService.php` | Factory | manter |
| `Factory/Finalization/Services/AiArchitectFinalService.php` | Factory | manter |
| `Factory/Finalization/Services/FinalizationProductionService.php` | Factory | manter |
| `Factory/Finalization/Services/SmartFinalInstallerService.php` | Factory | manter |
| `Factory/History/Services/BuildHistoryService.php` | Factory | manter |
| `Factory/Learning/Services/ModuleLearningService.php` | Factory | manter |
| `Factory/Learning/Services/SmartQaService.php` | Factory | manter |
| `Factory/Production/Services/ProductionStatusService.php` | Factory | manter |
| `Factory/QA/Services/SmartQa2Service.php` | Factory | manter |
| `Factory/RealBuilder/Services/FinishProjectService.php` | Factory | manter |
| `Factory/RealBuilder/Services/RealBuilderNameService.php` | Factory | manter |
| `Factory/Services/FactoryBlueprintService.php` | Factory | manter |
| `Factory/Services/FactoryCapabilityService.php` | Factory | manter |
| `Factory/Services/FactoryDashboardService.php` | Factory | manter |
| `Factory/Services/FactoryExecutionService.php` | Factory | manter |
| `Factory/Services/FactoryHealthService.php` | Factory | manter |
| `Factory/Services/FactoryLogService.php` | Factory | manter |
| `Factory/Services/FactoryProjectService.php` | Factory | manter |
| `Factory/Services/FactorySyncService.php` | Factory | manter |
| `Services/AgendamentoService.php` | Unclassified | revisão manual |
| `Services/Ai/AiExecutionService.php` | Unclassified | candidato a Shared/AI; não mover neste lote |
| `Services/AnimalService.php` | Legacy | quarentena; não apagar |
| `Services/Asaas/AsaasLicenseService.php` | Core | lote posterior |
| `Services/ClienteService.php` | Unclassified | revisão manual |
| `Services/FinanceiroService.php` | Unclassified | revisão manual |
| `Services/Heygen/HeygenService.php` | Unclassified | candidato a Shared/AI/Media; não mover neste lote |
| `Services/ProntuarioService.php` | Legacy | quarentena; não apagar |
| `Services/VacinaService.php` | Legacy | quarentena; não apagar |

## Primeiro lote de baixo risco

- Migrar a implementação de `CommercialProductResolver`, `CommercialFactoryIntakeService` e `CommercialFactoryStatusService` para `App\Commercial\Factory\Services`.
- Manter wrappers compatíveis no namespace antigo `App\CommercialFactory\Services`.
- Atualizar somente os dois Commands comerciais para consumir o namespace canônico.
- Não mover Models, Resources Filament, migrations ou código Legacy neste lote.
