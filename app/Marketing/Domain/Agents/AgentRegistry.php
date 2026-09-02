<?php

declare(strict_types=1);

namespace App\Marketing\Domain\Agents;

use InvalidArgumentException;
use RuntimeException;

final class AgentRegistry
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        return (array) config('marketing_agents.agents', []);
    }

    /** @return array<string, mixed> */
    public function get(string $agentId): array
    {
        $agent = $this->all()[$agentId] ?? null;

        if (! is_array($agent)) {
            throw new InvalidArgumentException("Marketing agent [{$agentId}] is not registered.");
        }

        return $agent;
    }

    public function isEnabled(string $agentId): bool
    {
        return (bool) ($this->get($agentId)['enabled'] ?? false);
    }

    /** @return list<string> */
    public function dependenciesOf(string $agentId): array
    {
        return array_values(array_map('strval', (array) ($this->get($agentId)['depends_on'] ?? [])));
    }

    public function assertValid(): void
    {
        $agents = $this->all();

        if (count($agents) !== 9) {
            throw new RuntimeException('Marketing Agents Core V1 requires exactly nine registered agents.');
        }

        foreach ($agents as $agentId => $agent) {
            if (! is_array($agent)) {
                throw new RuntimeException("Invalid registry entry [{$agentId}].");
            }

            if (($agent['may_publish'] ?? null) !== false || ($agent['may_spend'] ?? null) !== false) {
                throw new RuntimeException("Agent [{$agentId}] has a forbidden V1 permission.");
            }

            foreach ((array) ($agent['depends_on'] ?? []) as $dependency) {
                if (! array_key_exists((string) $dependency, $agents)) {
                    throw new RuntimeException("Agent [{$agentId}] has an unknown dependency [{$dependency}].");
                }
            }
        }

        $this->assertAcyclic($agents);
    }

    /** @param array<string, array<string, mixed>> $agents */
    private function assertAcyclic(array $agents): void
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $agentId) use (&$visit, &$visiting, &$visited, $agents): void {
            if (isset($visited[$agentId])) {
                return;
            }

            if (isset($visiting[$agentId])) {
                throw new RuntimeException("Cycle detected at marketing agent [{$agentId}].");
            }

            $visiting[$agentId] = true;

            foreach ((array) ($agents[$agentId]['depends_on'] ?? []) as $dependency) {
                $visit((string) $dependency);
            }

            unset($visiting[$agentId]);
            $visited[$agentId] = true;
        };

        foreach (array_keys($agents) as $agentId) {
            $visit((string) $agentId);
        }
    }
}
