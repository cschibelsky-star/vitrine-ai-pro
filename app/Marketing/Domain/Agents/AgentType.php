<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

enum AgentType: string
{
    case Orchestrator = 'orchestrator';
    case Specialist = 'specialist';
    case Validator = 'validator';
    case Analyst = 'analyst';
}
