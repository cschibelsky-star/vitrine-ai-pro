<?php

namespace Tests\Unit\Marketing;

use PHPUnit\Framework\TestCase;

class MarketingMigrationContractTest extends TestCase
{
    public function test_all_marketing_tables_are_scoped_by_company(): void
    {
        $files = glob(dirname(__DIR__, 3).'/database/migrations/*_marketing_*_table.php') ?: [];

        $this->assertCount(8, $files);

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            $this->assertStringContainsString(
                "foreignId('company_id')",
                $contents,
                basename($file).' must be tenant scoped.',
            );
        }
    }
}
