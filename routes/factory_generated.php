<?php

declare(strict_types=1);

$factoryRouteFiles = glob(__DIR__ . '/api_factory_*.php') ?: [];

sort($factoryRouteFiles);

foreach ($factoryRouteFiles as $factoryRouteFile) {
    require $factoryRouteFile;
}
