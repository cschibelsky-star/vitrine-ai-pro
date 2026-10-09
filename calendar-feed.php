<?php
declare(strict_types=1);
require_once __DIR__.'/app/event-store.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('Access-Control-Allow-Origin: *');
if ($_SERVER['REQUEST_METHOD'] !== 'GET') { http_response_code(405); header('Allow: GET'); echo '{"error":"Somente leitura"}'; exit; }
try {
    $events=array_map('conheca_public_event',conheca_published_events(conheca_read_events()));
    if (isset($_GET['id'])) {
        $events=array_values(array_filter($events,static fn($e)=>(string)($e['id'] ?? '')===(string)$_GET['id']));
        if (!$events) { http_response_code(404); echo '{"error":"Evento indisponível"}'; exit; }
        echo json_encode($events[0],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
    } else echo json_encode($events,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch (Throwable $e) { http_response_code(503); echo '{"error":"Agenda temporariamente indisponível"}'; }
