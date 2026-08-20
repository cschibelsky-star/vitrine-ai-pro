<?php

namespace App\Core\AgentHub\Contracts;

enum MissionStatus: string
{
    case CREATED = 'CREATED';
    case PLANNING = 'PLANNING';
    case RUNNING = 'RUNNING';
    case BLOCKED = 'BLOCKED';
    case COMPLETED = 'COMPLETED';
    case FAILED = 'FAILED';
}
