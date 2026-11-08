<?php
declare(strict_types=1);

namespace Illuminate\Console {
    class Command {
        public const SUCCESS = 0;
        public const FAILURE = 1;
        public mixed $output = null;
        private array $options = [];
        public array $messages = [];
        public function configureOptions(array $options): void { $this->options = $options; }
        public function option(string $key): mixed { return $this->options[$key] ?? null; }
        public function line(string $message): void { $this->messages[] = $message; }
        public function warn(string $message): void { $this->messages[] = $message; }
        public function error(string $message): void { $this->messages[] = $message; }
        public function info(string $message): void { $this->messages[] = $message; }
    }
}
namespace Illuminate\Support\Facades {
    class Artisan {
        public static array $calls = [];
        public static array $responses = [];
        public static function call(string $command, array $args = [], mixed $output = null): int {
            self::$calls[] = ['command' => $command, 'args' => $args];
            return self::$responses[$command] ?? 0;
        }
    }
}
namespace {
    use App\Factory\Console\Commands\FactoryInstallCommand;
    use App\Factory\Quality\Services\FactoryCoreBootstrapGuard;
    use Illuminate\Support\Facades\Artisan;

    $GLOBALS['test_environment'] = 'hml';
    $GLOBALS['test_bootstrap_enabled'] = false;

    function app(): object {
        return new class {
            public function environment(): string { return $GLOBALS['test_environment']; }
        };
    }
    function config(string $key, mixed $default = null): mixed {
        return $key === 'factory.core_bootstrap_enabled'
            ? $GLOBALS['test_bootstrap_enabled'] : $default;
    }

    require_once dirname(__DIR__, 2) . '/app/Factory/Quality/Services/FactoryCoreBootstrapGuard.php';
    require_once dirname(__DIR__, 2) . '/app/Factory/Console/Commands/FactoryInstallCommand.php';

    function assertCase(bool $condition, string $label): void {
        if (! $condition) {
            throw new \RuntimeException('FAIL: ' . $label);
        }
    }

    function runCommand(array $options = [], string $environment = 'hml', bool $enabled = false, array $failures = []): array {
        $GLOBALS['test_environment'] = $environment;
        $GLOBALS['test_bootstrap_enabled'] = $enabled;
        Artisan::$calls = [];
        Artisan::$responses = $failures;
        $command = new FactoryInstallCommand();
        $command->configureOptions($options);
        $exit = $command->handle(new FactoryCoreBootstrapGuard());
        return [$exit, Artisan::$calls, $command->messages];
    }

    $approved = [
        'apply' => true,
        'confirm' => 'FACTORY_CORE_BOOTSTRAP',
        'seed' => false,
        'no-sync' => false,
    ];

    [$exit, $calls, $messages] = runCommand();
    assertCase($exit === 0 && count($calls) === 0, 'default must never call Artisan');
    assertCase(count($messages) > 0 && str_contains(implode(' ', $messages), 'PLAN ONLY'), 'default plan must be explicit');

    [$exit, $calls] = runCommand(['seed' => true]);
    assertCase($exit === 0 && !$calls, 'seed option without apply must not execute');

    foreach ([
        ['no server opt-in', $approved, 'hml', false],
        ['production blocked', $approved, 'production', true],
        ['staging typo blocked', $approved, 'unknown', true],
        ['missing confirmation', ['apply' => true, 'confirm' => ''], 'hml', true],
        ['incorrect confirmation', ['apply' => true, 'confirm' => 'APPROVE'], 'hml', true],
    ] as [$label, $options, $env, $serverEnabled]) {
        [$exit, $calls] = runCommand($options, $env, $serverEnabled);
        assertCase($exit === 1 && !$calls, $label);
    }

    [$exit, $calls] = runCommand($approved, 'hml', true);
    assertCase($exit === 0, 'approved HML bootstrap returns success');
    assertCase(array_column($calls, 'command') === ['migrate', 'factory:sync', 'factory:health'],
        'approved commands execute in exact order');
    assertCase(($calls[0]['args']['--force'] ?? null) === true, 'migration force arg retained');

    [$exit, $calls] = runCommand(array_replace($approved, ['seed' => true]), 'testing', true);
    assertCase($exit === 0 && array_column($calls, 'command') === [
        'migrate', 'factory:sync', 'db:seed', 'factory:health',
    ], 'seed sequence');
    assertCase(($calls[2]['args']['--class'] ?? '') === 'Database\\Seeders\\FactoryCoreSeeder',
        'seed targets intended class');

    [$exit, $calls] = runCommand(array_replace($approved, ['no-sync' => true]), 'hml', true);
    assertCase($exit === 0 && array_column($calls, 'command') === ['migrate', 'factory:health'],
        'no-sync sequence');

    foreach (['migrate', 'factory:sync', 'db:seed', 'factory:health'] as $failing) {
        [$exit, $calls] = runCommand(array_replace($approved, ['seed' => true]), 'hml', true, [$failing => 1]);
        $expected = ['migrate', 'factory:sync', 'db:seed', 'factory:health'];
        $expected = array_slice($expected, 0, array_search($failing, $expected, true) + 1);
        assertCase($exit === 1 && array_column($calls, 'command') === $expected,
            'fail-fast when ' . $failing . ' fails');
    }
    echo "PASS: actual FactoryInstallCommand plan-only, opt-in, order, seed, no-sync and fail-fast\n";
}
