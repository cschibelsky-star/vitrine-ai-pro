<?php
declare(strict_types=1);

namespace App\AssessorOnline\Audesp;

use RuntimeException;

/**
 * Reads only locally staged, integrity-approved official schema JSON.
 * Does not download, validate document payloads, or authorize submission.
 */
final class OfficialSchemaRegistry
{
    private const TYPES = ['edital', 'licitacao', 'ata', 'ajuste', 'empenho'];

    /**
     * @param array<string, string> $approvedHashes SHA-256 pinned after independent verification.
     */
    public function __construct(
        private readonly string $directory,
        private readonly array $approvedHashes
    ) {}

    /** @return array<string, mixed> */
    public function load(string $type): array
    {
        if (!in_array($type, self::TYPES, true)) {
            throw new RuntimeException('unknown_schema_type');
        }
        $expected = $this->approvedHashes[$type] ?? null;
        if (!is_string($expected) || preg_match('/^[a-f0-9]{64}$/D', $expected) !== 1) {
            throw new RuntimeException('schema_hash_not_approved');
        }
        $base = realpath($this->directory);
        if ($base === false || !is_dir($base)) {
            throw new RuntimeException('schema_directory_missing');
        }
        $candidate = $base . DIRECTORY_SEPARATOR . $type . '.schema.json';
        if (is_link($candidate) || !is_file($candidate) || !is_readable($candidate)) {
            throw new RuntimeException('schema_file_unavailable');
        }
        $resolved = realpath($candidate);
        if ($resolved === false || dirname($resolved) !== $base) {
            throw new RuntimeException('schema_path_not_allowed');
        }
        $bytes = file_get_contents($resolved);
        if ($bytes === false || $bytes === '' || strlen($bytes) > 5_000_000) {
            throw new RuntimeException('schema_file_invalid_size');
        }
        if (!hash_equals($expected, hash('sha256', $bytes))) {
            throw new RuntimeException('schema_hash_mismatch');
        }
        try {
            $parsed = json_decode($bytes, true, 128, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('schema_json_invalid', previous: $exception);
        }
        if (!is_array($parsed) || array_is_list($parsed) || $parsed === []) {
            throw new RuntimeException('schema_root_invalid');
        }
        return $parsed;
    }
}
