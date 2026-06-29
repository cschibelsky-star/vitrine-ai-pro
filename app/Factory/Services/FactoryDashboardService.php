<?php
declare(strict_types=1);
namespace App\Factory\Services;
use App\Factory\Models\FactoryBlueprint; use App\Factory\Models\FactoryCapability; use App\Factory\Models\FactoryExecution; use App\Factory\Models\FactoryProject;
class FactoryDashboardService { public function stats(): array { return ['projects'=>FactoryProject::count(),'active_projects'=>FactoryProject::where('status','active')->count(),'capabilities'=>FactoryCapability::count(),'active_capabilities'=>FactoryCapability::where('status','active')->count(),'blueprints'=>FactoryBlueprint::count(),'active_blueprints'=>FactoryBlueprint::where('status','active')->count(),'executions'=>FactoryExecution::count(),'running_executions'=>FactoryExecution::where('status','running')->count(),'failed_executions'=>FactoryExecution::where('status','failed')->count()]; } public function executionsByStatus(): array { return FactoryExecution::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total','status')->toArray(); } }
