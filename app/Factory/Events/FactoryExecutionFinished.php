<?php
declare(strict_types=1);
namespace App\Factory\Events;
use App\Factory\Models\FactoryExecution; use Illuminate\Foundation\Events\Dispatchable; use Illuminate\Queue\SerializesModels;
class FactoryExecutionFinished { use Dispatchable, SerializesModels; public function __construct(public FactoryExecution $execution) {} }
