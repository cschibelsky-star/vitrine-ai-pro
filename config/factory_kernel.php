<?php

declare(strict_types=1);

return [
    'enabled' => env('FACTORY_KERNEL_MCP_ENABLED', true),
    'url' => env('FACTORY_KERNEL_MCP_URL', 'http://vitrine_vps_mcp_connector:8765/mcp'),
    'timeout' => (int) env('FACTORY_KERNEL_MCP_TIMEOUT', 8),
    'protocol_version' => env('FACTORY_KERNEL_MCP_PROTOCOL_VERSION', '2025-03-26'),
];
