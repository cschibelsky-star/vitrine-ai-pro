<?php
declare(strict_types=1);
namespace App\Factory\Listeners;
use App\Factory\Events\FactoryExecutionStarted; use App\Factory\Models\FactoryExecutionLog; use Illuminate\Support\Str;
class LogFactoryExecutionStarted { public function handle(FactoryExecutionStarted $event): void { FactoryExecutionLog::query()->create(['uuid'=>(string)Str::uuid(),'factory_execution_id'=>$event->execution->id,'level'=>'info','event'=>'execution_started','message'=>'Execução iniciada.','payload'=>['execution_id'=>$event->execution->id,'status'=>$event->execution->status],'created_by'=>auth()->id()]); } }
