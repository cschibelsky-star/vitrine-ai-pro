<?php

namespace App\Services\RoteiaLab;

use Illuminate\Support\Facades\Http;

final class RoteiaLabFinanceReader
{
    public function snapshot(): array
    {
        $url = rtrim((string) config('roteia_lab.base_url', ''), '/');
        $token = (string) config('roteia_lab.read_token', '');
        $allowed = strtolower((string) config('roteia_lab.allowed_host', ''));

        if ($url === '' || $token === '' || $allowed === '') {
            return ['connected' => false, 'reason' => 'not_configured', 'summary' => null, 'projects' => [], 'transactions' => []];
        }

        $parts = parse_url($url);
        $host = strtolower((string) ($parts['host'] ?? ''));
        if (($parts['scheme'] ?? '') !== 'https' || $host !== $allowed
            || filter_var($host, FILTER_VALIDATE_IP)
            || in_array($host, ['localhost', 'localhost.localdomain'], true)
            || isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])) {
            return ['connected' => false, 'reason' => 'invalid_endpoint', 'summary' => null, 'projects' => [], 'transactions' => []];
        }

        try {
            $http = Http::withToken($token)
                ->acceptJson()
                ->timeout((int) config('roteia_lab.timeout', 5))
                ->withoutRedirecting();
            $summary = $http->get($url . '/finance/summary');
            $projects = $http->get($url . '/finance/projects', ['limit' => 20]);
            $transactions = $http->get($url . '/finance/transactions', ['limit' => 20]);

            if (!$summary->successful() || !$projects->successful() || !$transactions->successful()) {
                return ['connected' => false, 'reason' => 'upstream_unavailable', 'summary' => null, 'projects' => [], 'transactions' => []];
            }

            $summaryData = $summary->json();
            $projectsData = $projects->json();
            $transactionsData = $transactions->json();
            if (!is_array($summaryData) || !is_array($projectsData) || !is_array($transactionsData)
                || !array_key_exists('saldo_disponivel', $summaryData)
                || !array_key_exists('consumo_conciliado', $summaryData)
                || !array_key_exists('reservas_pendentes', $summaryData)
                || ($summaryData['balance_source'] ?? null) !== 'not_connected'
                || !is_array($projectsData['data'] ?? null)
                || !array_is_list($projectsData['data'])
                || !is_array($transactionsData['data'] ?? null)
                || !array_is_list($transactionsData['data'])) {
                return ['connected' => false, 'reason' => 'invalid_response', 'summary' => null, 'projects' => [], 'transactions' => []];
            }

            // A API do laboratorio e a fonte de verdade. O Cockpit nao altera os registros.
            return [
                'connected' => true,
                'reason' => null,
                'summary' => $summaryData,
                'projects' => array_slice(array_filter($projectsData['data'], 'is_array'), 0, 20),
                'transactions' => array_slice(array_filter($transactionsData['data'], 'is_array'), 0, 20),
            ];
        } catch (\Throwable $e) {
            report($e);
            return ['connected' => false, 'reason' => 'connection_failed', 'summary' => null, 'projects' => [], 'transactions' => []];
        }
    }
}
