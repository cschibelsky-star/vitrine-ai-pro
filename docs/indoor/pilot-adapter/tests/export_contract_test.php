<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/TvSumareIndoorAdapter.php';
function check(bool $ok, string $name): void {
    if (!$ok) throw new RuntimeException($name);
}
$source = [
    'source'=>'tvsumare', 'source_id'=>'news_1', 'type'=>'news',
    'title'=>'Noticia piloto', 'destination'=>'indoor_only',
    'editorial_status'=>'approved', 'indoor_consent'=>true,
    'rights'=>['indoor_allowed'=>true, 'valid_until'=>'2099-01-01T00:00:00Z'],
    'media_path'=>'uploads/noticias/news_1.jpg', 'slot'=>'central',
    'import_state'=>'pending_indoor_review',
];
$ad=$source;
$ad['source_id']='ad_1';
$ad['type']='ad';
$ad['destination']='both';
$ad['media_path']='uploads/anuncios/ad_1.mp4';
$ad['slot']='lateral';
$items=TvSumareIndoorAdapter::preview([$source,$ad,$source],'tvsumare_pilot');
check(count($items)===2,'Exportar noticia e anuncio sem duplicacao');
check($items[0]['import_state']==='pending_indoor_review','Exigir revisao humana');
check($items[1]['slot']==='lateral','Preservar publicidade lateral');
check(!isset($items[0]['media_url']),'Nao gerar URL antes de distribuir');
$cases=[
 ['destination'=>'tvsumare_only'],
 ['indoor_consent'=>false],
 ['editorial_status'=>'pending'],
 ['rights'=>['indoor_allowed'=>false]],
 ['rights'=>['indoor_allowed'=>true,'valid_until'=>'2000-01-01T00:00:00Z']],
 ['media_path'=>'../../etc/passwd'],
 ['media_path'=>'https://example.invalid/a.mp4'],
 ['slot'=>'lateral'],
];
foreach($cases as $case) {
    check(TvSumareIndoorAdapter::preview([array_replace($source,$case)],'tvsumare_pilot')===[],'Bloqueio de registro inelegivel');
}
try {
    TvSumareIndoorAdapter::preview([$source], '');
    throw new RuntimeException('Tenant vazio aceito');
} catch (InvalidArgumentException $expected) {}
echo "OK: contrato de integracao TV Sumare -> Vitrine Indoor IA\n";
