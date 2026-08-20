<?php

namespace App\Core\AgentHub\Contracts;

enum AutonomyProfile: string
{
    case OBSERVER = 'OBSERVER';
    case SANDBOX_OPERATOR = 'SANDBOX_OPERATOR';
    case HOMOLOGATION_OPERATOR = 'HOMOLOGATION_OPERATOR';
    case SUPERVISED_PRODUCTION = 'SUPERVISED_PRODUCTION';
}
