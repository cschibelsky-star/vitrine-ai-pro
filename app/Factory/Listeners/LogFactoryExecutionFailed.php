<?php
declare(strict_types=1);
namespace App\Factory\Listeners;
use App\Factory\Events\FactoryExecutionFailed; use App\Factory\Models\FactoryExecutionLog; use Illuminate\Support\Str;
class LogFactoryExecutionFailed { public function handle(FactoryExecutionFailed $event): void { FactoryExecutionLog::query()->create(['uuid'=>(string)Str::uuid(),'factory_execution_id'=>$event->execution->id,'level'=>'error','event'=>'execution_failed','message'=>$event->message,'payload'=>['execution_id'=>$event->execution->id,'status'=>$event->execution->status,'error_message'=>$event->execution->error_message],'created_by'=>auth()->id()]); } }
