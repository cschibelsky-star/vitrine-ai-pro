<?php

declare(strict_types=1);

// Read-only JSON structural check. Does not authenticate CI or grant publication rights.
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Marketing\Governance\AutonomousHandoffGate;

$path = $argv[1] ?? '';
if ($path === '' || ! is_file($path)) {
    fwrite(STDERR, "Usage: php tools/marketing/verify-autonomous-handoff.php path/to/handoff.json\n");
    exit(2);
}

try {
    $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    if (! is_array($data) || array_is_list($data)) {
        throw new RuntimeException('handoff must be a JSON object');
    }
    $result = (new AutonomousHandoffGate())->evaluate($data);
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n";
    exit($result['decision'] === 'blocked' ? 1 : 0);
} catch (Throwable $e) {
    fwrite(STDERR, "Invalid handoff: ".$e->getMessage()."\n");
    exit(2);
}
