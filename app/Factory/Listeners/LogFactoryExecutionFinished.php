<?php
declare(strict_types=1);
namespace App\Factory\Listeners;
use App\Factory\Events\FactoryExecutionFinished; use App\Factory\Models\FactoryExecutionLog; use Illuminate\Support\Str;
class LogFactoryExecutionFinished { public function handle(FactoryExecutionFinished $event): void { FactoryExecutionLog::query()->create(['uuid'=>(string)Str::uuid(),'factory_execution_id'=>$event->execution->id,'level'=>'info','event'=>'execution_finished','message'=>'Execução finalizada com sucesso.','payload'=>['execution_id'=>$event->execution->id,'status'=>$event->execution->status],'created_by'=>auth()->id()]); } }
