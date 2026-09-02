<?php

namespace Tests\Unit\Marketing;

use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SchemaFilesTest extends TestCase
{
    public static function schemaFiles(): array
    {
        return array_map(
            static fn (string $path): array => [$path],
            glob(dirname(__DIR__, 3).'/resources/schemas/marketing/*.schema.json') ?: [],
        );
    }

    #[DataProvider('schemaFiles')]
    public function test_marketing_schema_is_valid_json(string $path): void
    {
        $decoded = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('https://json-schema.org/draft/2020-12/schema', $decoded['$schema']);
        $this->assertSame('object', $decoded['type']);
        $this->assertIsArray($decoded['required']);
    }

    public function test_all_ten_schema_files_exist(): void
    {
        $files = glob(dirname(__DIR__, 3).'/resources/schemas/marketing/*.schema.json') ?: [];

        $this->assertCount(10, $files);
    }
}
