<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/TvSumareIndoorAdapter.php';
function check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$valid = [
    'source' => 'tvsumare', 'source_id' => 'news_1', 'type' => 'news',
    'title' => 'Boletim piloto', 'destination' => 'indoor_only',
    'editorial_status' => 'approved', 'indoor_consent' => true,
    'rights' => ['indoor_allowed' => true, 'valid_until' => '2099-01-01T00:00:00Z'],
    'media_path' => 'uploads/noticias/news_1.jpg',
    'slot' => 'central',
];
$ad = $valid;
$ad['source_id'] = 'ad_1';
$ad['type'] = 'ad';
$ad['destination'] = 'both';
$ad['media_path'] = 'uploads/anuncios/ad_1.mp4';
$ad['slot'] = 'lateral';
$items = TvSumareIndoorAdapter::preview([$valid, $valid, $ad], 'tvsumare_pilot');
check(count($items) === 2, 'Deduplicação e compatibilidade');
check($items[0]['import_state'] === 'pending_indoor_review', 'Revisão é obrigatória');
check($items[1]['slot'] === 'lateral', 'Anúncio lateral');
check(!isset($items[0]['media_url']), 'Não transformar caminho em URL pública');
foreach ([
    ['source' => 'other'],
    ['destination' => 'tvsumare_only'],
    ['editorial_status' => 'pending'],
    ['indoor_consent' => false],
    ['rights' => ['indoor_allowed' => false]],
    ['rights' => ['indoor_allowed' => true, 'valid_until' => '2020-01-01T00:00:00Z']],
    ['media_path' => '../private/file.mp4'],
    ['media_path' => 'https://example.invalid/file.mp4'],
    ['slot' => 'lateral'],
] as $bad) {
    check(TvSumareIndoorAdapter::preview([array_replace($valid, $bad)], 'tvsumare_pilot') === [], 'Negar registro inválido');
}
try {
    TvSumareIndoorAdapter::preview([$valid], '');
    throw new RuntimeException('Tenant vazio foi aceito');
} catch (InvalidArgumentException $expected) {
}
echo "OK: contrato TV Sumare -> Indoor compatível e fechado por padrão\n";
