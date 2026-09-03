<?php

declare(strict_types=1);

namespace App\Marketing\Application;

interface MarketingAgentExecutor
{
    /**
     * @param array<string, mixed> $campaign
     * @param array<string, array<string, mixed>> $inputs
     * @return array<string, mixed>
     */
    public function execute(string $agentId, array $campaign, array $inputs): array;

    /** @return array<string, mixed> */
    public function metadataFor(string $agentId): array;
}
