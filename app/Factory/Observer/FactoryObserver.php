<?php
declare(strict_types=1);

namespace App\Factory\Observer;

use InvalidArgumentException;
use RuntimeException;

/**
 * Read-only, explicit capability allowlist for VIA Agent Hub.
 * No HTTP, shell, database, filesystem write or deployment methods.
 * The adapter is not registered in a route/service provider by default.
 */
final class FactoryObserver
{
    public const CAPABILITIES = [
        'factory.health',
        'factory.project.inspect',
        'factory.build.inspect',
        'factory.qa.inspect',
    ];

    /** @param callable(string,array):array $reader Injected trusted read-only provider */
    public function __construct(private $reader)
    {
        if (!is_callable($reader)) {
            throw new InvalidArgumentException('Reader must be callable');
        }
    }

    /** @return array<string,mixed> */
    public function inspect(string $capability, array $parameters = []): array
    {
        if (!in_array($capability, self::CAPABILITIES, true)) {
            throw new RuntimeException('Capability denied');
        }
        $allowedParameters = match ($capability) {
            'factory.health' => [],
            'factory.project.inspect' => ['project_id'],
            'factory.build.inspect' => ['build_id'],
            'factory.qa.inspect' => ['build_id'],
        };
        foreach (array_keys($parameters) as $key) {
            if (!in_array($key, $allowedParameters, true)) {
                throw new InvalidArgumentException('Parameter denied');
            }
        }
        if ($allowedParameters !== []) {
            $key = $allowedParameters[0];
            if (!isset($parameters[$key]) || !is_string($parameters[$key]) ||
                !preg_match('/^[A-Za-z0-9_-]{1,80}$/D', $parameters[$key])) {
                throw new InvalidArgumentException('Invalid identifier');
            }
        }
        $result = ($this->reader)($capability, $parameters);
        if (!is_array($result)) {
            throw new RuntimeException('Provider returned invalid result');
        }
        return [
            'capability' => $capability,
            'mode' => 'READ_ONLY',
            'source' => 'injected_provider',
            'verified_live' => false,
            'data' => $result,
        ];
    }
}
