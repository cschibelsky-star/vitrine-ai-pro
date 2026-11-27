<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\Filament\AdminPanelProvider::class,

    // ⭐ O executor de IA dos agentes do Escritório Virtual.
    App\Providers\AgentExecutionServiceProvider::class,

    App\Factory\Providers\FactoryServiceProvider::class,
];
