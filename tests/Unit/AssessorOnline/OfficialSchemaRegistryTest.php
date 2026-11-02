<?php
declare(strict_types=1);

namespace Tests\Unit\AssessorOnline;

use App\AssessorOnline\Audesp\OfficialSchemaRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class OfficialSchemaRegistryTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . '/audesp-schema-test-' . bin2hex(random_bytes(8));
        mkdir($this->dir, 0700);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $file) {
            unlink($file);
        }
        rmdir($this->dir);
        parent::tearDown();
    }

    public function test_missing_approval_fails_closed(): void
    {
        $this->expectExceptionMessage('schema_hash_not_approved');
        (new OfficialSchemaRegistry($this->dir, []))->load('edital');
    }

    public function test_unexpected_type_rejected(): void
    {
        $this->expectExceptionMessage('unknown_schema_type');
        (new OfficialSchemaRegistry($this->dir, []))->load('../outside');
    }

    public function test_official_file_must_match_approved_checksum(): void
    {
        file_put_contents($this->dir . '/edital.schema.json', '{"type":"object"}');
        $this->expectExceptionMessage('schema_hash_mismatch');
        (new OfficialSchemaRegistry($this->dir, ['edital' => str_repeat('a', 64)]))->load('edital');
    }

    public function test_approved_schema_is_loaded_without_semantic_assumptions(): void
    {
        // Artificial fixture: tests integrity only, NOT TCESP schema compatibility.
        $fixture = '{"type":"object"}';
        file_put_contents($this->dir . '/edital.schema.json', $fixture);
        $result = (new OfficialSchemaRegistry($this->dir, ['edital' => hash('sha256', $fixture)]))->load('edital');
        self::assertSame('object', $result['type']);
    }

    public function test_symlink_is_rejected(): void
    {
        $outside = tempnam(sys_get_temp_dir(), 'audesp-fixture-');
        try {
            file_put_contents($outside, '{"type":"object"}');
            symlink($outside, $this->dir . '/ata.schema.json');
            $this->expectExceptionMessage('schema_file_unavailable');
            (new OfficialSchemaRegistry($this->dir, ['ata' => hash_file('sha256', $outside)]))->load('ata');
        } finally {
            unlink($outside);
        }
    }

    public function test_invalid_json_rejected_even_when_hash_matches(): void
    {
        $fixture = '{broken';
        file_put_contents($this->dir . '/ajuste.schema.json', $fixture);
        $this->expectExceptionMessage('schema_json_invalid');
        (new OfficialSchemaRegistry($this->dir, ['ajuste' => hash('sha256', $fixture)]))->load('ajuste');
    }
}
