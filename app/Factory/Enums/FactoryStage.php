<?php

declare(strict_types=1);

namespace App\Factory\Enums;

enum FactoryStage: string
{
    case IntakeReceived = 'intake_received';
    case SimulationCompleted = 'simulation_completed';
    case BuildQueued = 'build_queued';
    case BuildCompleted = 'build_completed';
    case Provisioning = 'provisioning';
    case Installed = 'installed';
    case HealthCheckPassed = 'health_check_passed';
    case ReadyForHomologation = 'ready_for_homologation';
    case Homologated = 'homologated';
    case Failed = 'failed';
}
