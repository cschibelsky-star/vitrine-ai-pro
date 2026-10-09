<?php
declare(strict_types=1);
require_once __DIR__ . '/../app/calendar.php';

$checks = 0;
function check($actual, $expected, string $message): void {
  global $checks;
  $checks++;
  if ($actual !== $expected) throw new RuntimeException($message . ': ' . var_export($actual, true));
}
function event(string $id, string $start, string $end = '', string $status = 'published'): array {
  return ['id'=>$id, 'start_date'=>$start, 'end_date'=>$end, 'status'=>$status,
    'source_url'=>'https://cultura.sumare.sp.gov.br/', 'start_time'=>'23:00'];
}
$events = [
  event('future', '2026-10-11'),
  event('starts-today', '2026-10-09'),
  event('ends-today', '2026-10-08', '2026-10-09'),
  event('overnight', '2026-10-09', '2026-10-10'),
  event('ended', '2026-10-07', '2026-10-08'),
  event('candidate', '2026-10-09', '', 'candidate'),
  event('rejected', '2026-10-09', '', 'rejected'),
  event('cancelled', '2026-10-09', '', 'cancelled'),
  event('missing-date', ''),
];
$expectedToday = ['ends-today', 'starts-today', 'overnight', 'future'];
foreach (['UTC', 'America/Sao_Paulo', 'Asia/Tokyo', 'America/Los_Angeles'] as $serverTimezone) {
  date_default_timezone_set($serverTimezone);
  foreach (['2026-10-09T03:00:00Z', '2026-10-10T00:00:00Z', '2026-10-10T02:59:59Z'] as $instant) {
    $now = new DateTimeImmutable($instant);
    check(conheca_calendar_today($now), '2026-10-09', "Local date at $instant ($serverTimezone)");
    check(array_column(conheca_published_events($events, $now), 'id'), $expectedToday, "Inclusive final day at $instant ($serverTimezone)");
  }
  $midnight = new DateTimeImmutable('2026-10-10T03:00:00Z');
  check(array_column(conheca_published_events($events, $midnight), 'id'), ['overnight', 'future'], 'Local midnight expires yesterday only');
  check(array_column(conheca_published_events($events, new DateTimeImmutable('2026-10-11T02:59:59Z')), 'id'), ['overnight', 'future'], 'Overnight event survives its end day');
  check(array_column(conheca_published_events($events, new DateTimeImmutable('2026-10-11T03:00:00Z')), 'id'), ['future'], 'Overnight event expires after its end day');
  check(conheca_event_date_label(event('label', '2026-10-09')), '09 OUT', 'Label uses local calendar date');
  check(conheca_event_date_label(event('epoch', '1970-01-01')), '01 JAN', 'Epoch date is valid');
  check(conheca_event_date_label(event('missing', '')), 'DATA A CONFIRMAR', 'Missing date label');
  check(conheca_event_date_label(event('invalid', 'invalid')), 'INVALID', 'Invalid date fallback');
  check(date_default_timezone_get(), $serverTimezone, 'Calendar does not change global timezone');
}
$missingStatus = event('missing-status', '2026-10-09');
unset($missingStatus['status']);
check(conheca_published_events([$missingStatus], new DateTimeImmutable('2026-10-09T12:00:00Z')), [], 'Missing status remains unpublished');
check(conheca_published_events([], new DateTimeImmutable('2026-10-09T12:00:00Z')), [], 'Empty agenda');
check(conheca_published_events([$events[1]], new DateTimeImmutable('2026-10-09T12:00:00Z'))[0], $events[1], 'Editorial fields and source preserved');
echo "OK: $checks calendar checks\n";
