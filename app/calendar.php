<?php
declare(strict_types=1);

function conheca_calendar_timezone(): DateTimeZone {
  return new DateTimeZone('America/Sao_Paulo');
}

function conheca_calendar_today(?DateTimeImmutable $now = null): string {
  return ($now ?? new DateTimeImmutable('now', conheca_calendar_timezone()))
    ->setTimezone(conheca_calendar_timezone())->format('Y-m-d');
}

function conheca_published_events(array $events, ?DateTimeImmutable $now = null): array {
  $today = conheca_calendar_today($now);
  // Publication is the existing editorial approval gate. Date-only end dates
  // are inclusive; an event without an end date lasts through its start day.
  $published = array_values(array_filter($events, static function(array $event) use ($today): bool {
    if (($event['status'] ?? 'candidate') !== 'published') return false;
    $end = trim((string)($event['end_date'] ?? ''));
    $start = trim((string)($event['start_date'] ?? ''));
    $lastDay = $end !== '' ? $end : $start;
    return $start !== '' && ($lastDay === '' || $lastDay >= $today);
  }));
  usort($published, static function(array $a, array $b): int {
    return strcmp((string)($a['start_date'] ?? '9999-12-31'), (string)($b['start_date'] ?? '9999-12-31'));
  });
  return $published;
}

function conheca_event_date_label(array $event): string {
  $date = trim((string)($event['start_date'] ?? ''));
  if ($date === '') return 'DATA A CONFIRMAR';
  $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date, conheca_calendar_timezone());
  if ($parsed === false || $parsed->format('Y-m-d') !== $date) return strtoupper($date);
  $months = [1=>'JAN',2=>'FEV',3=>'MAR',4=>'ABR',5=>'MAI',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SET',10=>'OUT',11=>'NOV',12=>'DEZ'];
  return $parsed->format('d') . ' ' . $months[(int)$parsed->format('n')];
}
