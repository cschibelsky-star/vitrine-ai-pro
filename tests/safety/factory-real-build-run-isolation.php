<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/app/Factory/RealBuilder/Services/RealBuildPath.php';
use App\Factory\RealBuilder\Services\RealBuildPath;

$idA = 'a21d35a0-d19e-4ac8-8ea2-d3654a743211';
$idB = 'b21d35a0-d19e-4ac8-8ea2-d3654a743211';
$legacy = RealBuildPath::relative('sistema_escola', null);
$a = RealBuildPath::relative('sistema_escola', $idA);
$b = RealBuildPath::relative('sistema_escola', $idB);
if ($legacy !== 'sistema_escola' ||
    $a !== 'sistema_escola/runs/' . $idA ||
    $a === $b) {
    throw new RuntimeException('Run-scoped path isolation failed.');
}
foreach ([['../wrong', $idA], ['good', '../wrong'], ['good', ''], ['good', 'not-a-uuid']] as [$slug, $id]) {
    try {
        RealBuildPath::relative($slug, $id);
        throw new RuntimeException('Accepted unsafe identifier.');
    } catch (InvalidArgumentException $expected) {
    }
}
echo "PASS: legacy compatibility, distinct runs and unsafe IDs rejected\n";
