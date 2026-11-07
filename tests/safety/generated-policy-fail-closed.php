<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RealCodeGenerator.php';

$generator = (new ReflectionClass(\App\Factory\RealBuilder\Services\RealCodeGenerator::class))->newInstanceWithoutConstructor();
$source = (new ReflectionClass($generator))->getMethod('policyCode')->invoke($generator, 'Cliente');

if (substr_count($source, 'return false;') !== 7 || str_contains($source, 'return true;')) {
    fwrite(STDERR, "FAIL: generated policy does not deny all seven abilities by default\n");
    exit(1);
}

if (!str_contains($source, 'class ClientePolicy') || !str_contains($source, 'Cliente $record')) {
    fwrite(STDERR, "FAIL: generated policy has invalid model binding\n");
    exit(1);
}

$tmp = tempnam(sys_get_temp_dir(), 'factory-policy-');
file_put_contents($tmp, $source);
exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($tmp) . ' 2>&1', $output, $code);
unlink($tmp);

if ($code !== 0) {
    fwrite(STDERR, "FAIL: generated policy syntax: " . implode("\n", $output) . "\n");
    exit(1);
}

echo "PASS: generated policy has valid PHP and seven fail-closed abilities\n";
