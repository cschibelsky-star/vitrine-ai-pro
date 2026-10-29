<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/TvSumareIndoorAdapter.php';

function assertSameCount(int $count, array $items, string $label): void {
    if (count($items) !== $count) {
        throw new RuntimeException($label . ': esperado ' . $count . ', encontrado ' . count($items));
    }
}
$valid = [
    'source_id' => 'n-001',
    'type' => 'news',
    'title' => 'Reportagem de teste',
    'editorial_status' => 'approved',
    'indoor_consent' => true,
    'rights' => ['indoor_allowed' => true, 'valid_until' => '2099-01-01T00:00:00Z'],
    'media_url' => 'https://example.invalid/news.jpg',
    'slot' => 'central',
];
$pending = $valid;
$pending['source_id'] = 'n-002';
$pending['editorial_status'] = 'pending';
$noConsent = $valid;
$noConsent['source_id'] = 'n-003';
$noConsent['indoor_consent'] = false;
$expired = $valid;
$expired['source_id'] = 'n-004';
$expired['rights']['valid_until'] = '2000-01-01T00:00:00Z';
$unsafeUrl = $valid;
$unsafeUrl['source_id'] = 'n-005';
$unsafeUrl['media_url'] = 'file:///etc/passwd';
$ad = $valid;
$ad['source_id'] = 'ad-001';
$ad['type'] = 'ad';
$ad['slot'] = 'lateral';
$data = TvSumareIndoorAdapter::preview(
    [$valid, $valid, $pending, $noConsent, $expired, $unsafeUrl, $ad], 'tvsumare-pilot'
);
assertSameCount(2, $data, 'Filtro de elegibilidade, duplicados e direitos');
if ($data[0]['import_state'] !== 'pending_indoor_review') {
    throw new RuntimeException('Nunca publicar automaticamente');
}
if ($data[1]['slot'] !== 'lateral') {
    throw new RuntimeException('Área comercial lateral');
}
try {
    TvSumareIndoorAdapter::preview([$valid], '');
    throw new RuntimeException('Tenant vazio aceito');
} catch (InvalidArgumentException $expected) {
}
echo "OK: contrato de leitura e revisão Indoor\n";
