<?php
declare(strict_types=1);
return [
    'name' => env('FACTORY_NAME', 'Factory Core'),
    'version' => '1.0.0',
    'enabled' => env('FACTORY_ENABLED', true),
    'project_name' => env('FACTORY_PROJECT_NAME', 'Vitrine AI Pro Master'),
    'route_prefix' => env('FACTORY_ROUTE_PREFIX', 'factory'),
    'middleware' => ['web', 'auth'],
    'pagination' => ['default' => 25, 'options' => [10, 25, 50, 100]],
    'execution' => ['default_status' => 'pending', 'timeout_seconds' => env('FACTORY_EXECUTION_TIMEOUT', 300), 'max_attempts' => env('FACTORY_EXECUTION_MAX_ATTEMPTS', 3)],
    'statuses' => [
        'projects' => ['draft' => 'Rascunho', 'active' => 'Ativo', 'paused' => 'Pausado', 'archived' => 'Arquivado'],
        'capabilities' => ['active' => 'Ativa', 'inactive' => 'Inativa', 'deprecated' => 'Descontinuada'],
        'blueprints' => ['draft' => 'Rascunho', 'active' => 'Ativo', 'archived' => 'Arquivado'],
        'executions' => ['pending' => 'Pendente', 'running' => 'Em execução', 'finished' => 'Finalizada', 'failed' => 'Falhou', 'cancelled' => 'Cancelada'],
        'logs' => ['debug' => 'Debug', 'info' => 'Info', 'warning' => 'Atenção', 'error' => 'Erro'],
    ],
    'permissions' => ['factory.access','factory.manage','factory.projects.view','factory.projects.manage','factory.capabilities.view','factory.capabilities.manage','factory.blueprints.view','factory.blueprints.manage','factory.executions.view','factory.executions.manage','factory.logs.view','factory.logs.manage'],
    'filament' => ['navigation_group' => 'Factory Core', 'dashboard_slug' => 'factory-dashboard'],
];
