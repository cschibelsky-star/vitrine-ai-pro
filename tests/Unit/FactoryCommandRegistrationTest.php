<?php

namespace Tests\Unit;

use App\Factory\Console\Commands\FactoryProduceCommand;
use App\Factory\Console\Commands\FactoryProduceEnterpriseCommand;
use PHPUnit\Framework\TestCase;

class FactoryCommandRegistrationTest extends TestCase
{
    public function test_production_commands_have_unique_names(): void
    {
        $canonical = new FactoryProduceCommand();
        $enterprise = new FactoryProduceEnterpriseCommand();

        self::assertSame('factory:produce', $canonical->getName());
        self::assertSame('factory:produce-enterprise', $enterprise->getName());
        self::assertNotSame($canonical->getName(), $enterprise->getName());
    }

    public function test_final_master_defaults_to_safe_mode(): void
    {
        $definition = (new \App\Factory\Console\Commands\FactoryBuildAndInstallCommand())->getDefinition();

        self::assertTrue($definition->hasOption('install'));
        self::assertTrue($definition->hasOption('confirm-production'));
        self::assertFalse($definition->hasOption('dry-run'));
    }
}
