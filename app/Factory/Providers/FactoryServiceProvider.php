<?php
declare(strict_types=1);
namespace App\Factory\Providers;
use App\Factory\Console\Commands\FactoryHealthCommand;
use App\Factory\Console\Commands\FactoryInstallCommand;
use App\Factory\Console\Commands\FactorySyncCommand;
use App\Factory\Console\Commands\FactoryBuildAndInstallCommand;
use App\Factory\Console\Commands\FactoryEnterpriseInstallCommand;
use App\Factory\Console\Commands\FactoryEnterpriseBuildCommand;
use App\Factory\Console\Commands\FactoryFinishProjectCommand;
use App\Factory\Console\Commands\FactoryRealInstallCommand;
use App\Factory\Console\Commands\FactoryRealBuildCommand;
use App\Factory\Console\Commands\FactoryInstallFinalCommand;
use App\Factory\Console\Commands\FactoryFinalizeRequestCommand;
use App\Factory\Console\Commands\FactoryArchitectRequestCommand;
use App\Factory\Console\Commands\FactoryInstallSystemCommand;
use App\Factory\Console\Commands\FactoryProduceRequestCommand;
use App\Factory\Console\Commands\FactoryProduceEnterpriseCommand;
use App\Factory\Console\Commands\FactorySmartQa2Command;
use App\Factory\Console\Commands\FactoryEvolutionCommand;
use App\Factory\Console\Commands\FactoryHistoryCommand;
use App\Factory\Console\Commands\FactoryDocsCommand;
use App\Factory\Console\Commands\FactoryProductCommand;
use App\Factory\Console\Commands\FactoryWorkflowCommand;
use App\Factory\Console\Commands\FactoryDecisionCommand;
use App\Factory\Console\Commands\FactoryReleaseStatusCommand;
use App\Factory\Console\Commands\FactoryPluginsCommand;
use App\Factory\Console\Commands\FactoryUpdateCommand;
use App\Factory\Console\Commands\FactoryArchitectureDesignCommand;
use App\Factory\Console\Commands\FactoryComponentsForDomainCommand;
use App\Factory\Console\Commands\FactoryDomainKnowledgeCommand;
use App\Factory\Console\Commands\FactoryExecutiveDashboardCommand;
use App\Factory\Console\Commands\FactoryWidgetsModuleCommand;
use App\Factory\Console\Commands\FactoryDashboardModuleCommand;
use App\Factory\Console\Commands\FactorySmartQaCommand;
use App\Factory\Console\Commands\FactoryPatternsCommand;
use App\Factory\Console\Commands\FactoryLearnModuleCommand;
use App\Factory\Console\Commands\FactoryAiPlanCommand;
use App\Factory\Console\Commands\FactoryAiBlueprintCommand;
use App\Factory\Console\Commands\FactoryMakeSystemCommand;
use App\Factory\Console\Commands\FactoryMakeBlueprintCommand;
use App\Factory\Console\Commands\FactoryQaModuleCommand;
use App\Factory\Console\Commands\FactoryDetectCompatibilityCommand;
use App\Factory\Console\Commands\FactoryMakeModuleCommand;
use App\Factory\Console\Commands\FactoryInstallModuleCommand;
use App\Factory\Console\Commands\FactoryEngineTestCommand;
use App\Factory\Events\FactoryExecutionFailed;
use App\Factory\Events\FactoryExecutionFinished;
use App\Factory\Events\FactoryExecutionStarted;
use App\Factory\Listeners\LogFactoryExecutionFailed;
use App\Factory\Listeners\LogFactoryExecutionFinished;
use App\Factory\Listeners\LogFactoryExecutionStarted;
use App\Factory\Models\FactoryBlueprint;
use App\Factory\Models\FactoryCapability;
use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryExecutionLog;
use App\Factory\Models\FactoryProject;
use App\Factory\Observers\FactoryBlueprintObserver;
use App\Factory\Observers\FactoryCapabilityObserver;
use App\Factory\Observers\FactoryExecutionObserver;
use App\Factory\Observers\FactoryProjectObserver;
use App\Factory\Policies\FactoryBlueprintPolicy;
use App\Factory\Policies\FactoryCapabilityPolicy;
use App\Factory\Policies\FactoryExecutionLogPolicy;
use App\Factory\Policies\FactoryExecutionPolicy;
use App\Factory\Policies\FactoryProjectPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
class FactoryServiceProvider extends ServiceProvider
{
    public function register(): void { if (file_exists(base_path('config/factory.php'))) { $this->mergeConfigFrom(base_path('config/factory.php'), 'factory'); } }
    public function boot(): void { if (! config('factory.enabled', true)) return; $this->loadRoutesFrom(base_path('routes/factory.php')); $this->loadMigrationsFrom(database_path('migrations')); $this->registerCommands(); $this->registerPolicies(); $this->registerGates(); $this->registerObservers(); $this->registerEvents(); }
    protected function registerCommands(): void { if ($this->app->runningInConsole()) { $this->commands([FactoryInstallCommand::class, FactoryHealthCommand::class, FactorySyncCommand::class, FactoryBuildAndInstallCommand::class, FactoryEnterpriseInstallCommand::class, FactoryEnterpriseBuildCommand::class, FactoryFinishProjectCommand::class, FactoryRealInstallCommand::class, FactoryRealBuildCommand::class, FactoryInstallFinalCommand::class, FactoryFinalizeRequestCommand::class, FactoryArchitectRequestCommand::class, FactoryInstallSystemCommand::class, FactoryProduceRequestCommand::class, FactoryProduceEnterpriseCommand::class, FactorySmartQa2Command::class, FactoryEvolutionCommand::class, FactoryHistoryCommand::class, FactoryDocsCommand::class, FactoryProductCommand::class, FactoryWorkflowCommand::class, FactoryDecisionCommand::class, FactoryReleaseStatusCommand::class, FactoryPluginsCommand::class, FactoryUpdateCommand::class, FactoryArchitectureDesignCommand::class, FactoryComponentsForDomainCommand::class, FactoryDomainKnowledgeCommand::class, FactoryExecutiveDashboardCommand::class, FactoryWidgetsModuleCommand::class, FactoryDashboardModuleCommand::class, FactorySmartQaCommand::class, FactoryPatternsCommand::class, FactoryLearnModuleCommand::class, FactoryAiPlanCommand::class, FactoryAiBlueprintCommand::class, FactoryMakeSystemCommand::class, FactoryMakeBlueprintCommand::class, FactoryQaModuleCommand::class, FactoryDetectCompatibilityCommand::class, FactoryMakeModuleCommand::class, FactoryInstallModuleCommand::class, FactoryEngineTestCommand::class]); } }
    protected function registerPolicies(): void { Gate::policy(FactoryProject::class, FactoryProjectPolicy::class); Gate::policy(FactoryCapability::class, FactoryCapabilityPolicy::class); Gate::policy(FactoryBlueprint::class, FactoryBlueprintPolicy::class); Gate::policy(FactoryExecution::class, FactoryExecutionPolicy::class); Gate::policy(FactoryExecutionLog::class, FactoryExecutionLogPolicy::class); }
    protected function registerGates(): void { foreach (config('factory.permissions', []) as $permission) { Gate::define($permission, fn ($user): bool => $this->userHasFactoryPermission($user, $permission)); } }
    protected function registerObservers(): void { FactoryProject::observe(FactoryProjectObserver::class); FactoryCapability::observe(FactoryCapabilityObserver::class); FactoryBlueprint::observe(FactoryBlueprintObserver::class); FactoryExecution::observe(FactoryExecutionObserver::class); }
    protected function registerEvents(): void { Event::listen(FactoryExecutionStarted::class, LogFactoryExecutionStarted::class); Event::listen(FactoryExecutionFinished::class, LogFactoryExecutionFinished::class); Event::listen(FactoryExecutionFailed::class, LogFactoryExecutionFailed::class); }
    protected function userHasFactoryPermission(mixed $user, string $permission): bool { if (! $user) return false; if (method_exists($user, 'hasPermissionTo')) return (bool) $user->hasPermissionTo($permission); return true; }
}
