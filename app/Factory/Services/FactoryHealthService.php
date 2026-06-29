<?php
declare(strict_types=1);
namespace App\Factory\Services;
use Illuminate\Support\Facades\DB; use Illuminate\Support\Facades\Schema;
class FactoryHealthService { public function check(): array { $tables=['factory_projects','factory_capabilities','factory_blueprints','factory_executions','factory_execution_logs']; $status=[]; foreach($tables as $t){$status[$t]=Schema::hasTable($t);} try{DB::connection()->getPdo();$db=['connected'=>true,'connection'=>DB::connection()->getName()];}catch(\Throwable $e){$db=['connected'=>false,'connection'=>DB::connection()->getName(),'error'=>$e->getMessage()];} return ['module'=>config('factory.name'),'version'=>config('factory.version'),'enabled'=>(bool)config('factory.enabled'),'database'=>$db,'tables'=>$status,'records'=>[],'status'=>($db['connected']&&!in_array(false,$status,true))?'healthy':'unhealthy','checked_at'=>now()->toISOString()]; } }
