<?php

declare(strict_types=1);

namespace App\Marketing\Application;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

final class SchemaContractValidator
{
    /** @param array<string, mixed> $payload */
    public function assertValid(string $schemaName, array $payload): void
    {
        $path = base_path("resources/schemas/marketing/{$schemaName}.schema.json");

        if (! is_file($path)) {
            throw new RuntimeException("Marketing schema [{$schemaName}] was not found.");
        }

        try {
            $schema = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException("Marketing schema [{$schemaName}] is invalid.", 0, $exception);
        }

        foreach ((array) ($schema['required'] ?? []) as $required) {
            if (! array_key_exists((string) $required, $payload)) {
                throw new InvalidArgumentException("Required field [{$required}] is missing from [{$schemaName}].");
            }
        }

        if (($schema['additionalProperties'] ?? true) === false) {
            $unknown = array_diff(array_keys($payload), array_keys((array) ($schema['properties'] ?? [])));

            if ($unknown !== []) {
                throw new InvalidArgumentException(
                    "Unknown field [".reset($unknown)."] in [{$schemaName}].",
                );
            }
        }

        foreach ((array) ($schema['properties'] ?? []) as $field => $rules) {
            if (! array_key_exists($field, $payload) || ! is_array($rules)) {
                continue;
            }

            $value = $payload[$field];
            $this->assertType($schemaName, (string) $field, $value, $rules);

            if (array_key_exists('const', $rules) && $value !== $rules['const']) {
                throw new InvalidArgumentException("Field [{$field}] violates its constant in [{$schemaName}].");
            }

            if (isset($rules['enum']) && ! in_array($value, (array) $rules['enum'], true)) {
                throw new InvalidArgumentException("Field [{$field}] is outside the enum in [{$schemaName}].");
            }

            if (is_string($value) && isset($rules['minLength']) && mb_strlen($value) < (int) $rules['minLength']) {
                throw new InvalidArgumentException("Field [{$field}] is too short in [{$schemaName}].");
            }

            if (is_int($value) && isset($rules['minimum']) && $value < (int) $rules['minimum']) {
                throw new InvalidArgumentException("Field [{$field}] is below minimum in [{$schemaName}].");
            }
        }
    }

    /** @param array<string, mixed> $rules */
    private function assertType(string $schemaName, string $field, mixed $value, array $rules): void
    {
        $valid = match ($rules['type'] ?? null) {
            'object' => is_array($value) && ! array_is_list($value),
            'array' => is_array($value),
            'string' => is_string($value),
            'integer' => is_int($value),
            'boolean' => is_bool($value),
            null => true,
            default => true,
        };

        if (! $valid) {
            throw new InvalidArgumentException("Field [{$field}] has an invalid type in [{$schemaName}].");
        }
    }
}
