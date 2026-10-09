<?php

declare(strict_types=1);

namespace App\Services\Deploy;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class OperationalEvidence
{
    private function overlap(array $left, array $right): bool
    {
        foreach ($left as $a) {
            foreach ($right as $b) {
                if (!is_string($a) || !is_string($b) || $a === '' || $b === '' || $a === $b
                    || str_starts_with($a.'/', rtrim($b, '/').'/') || str_starts_with($b.'/', rtrim($a, '/').'/')) {
                    return true;
                }
            }
        }
        return false;
    }

    public function collect(string $id, string $sha): array
    {
        $project = collect(config('homologation_projects.projects', []))->firstWhere('id', $id);
        if (!$project || !preg_match('/^[a-f0-9]{40}$/D', $sha)) {
            throw new RuntimeException('unknown_project_or_sha');
        }
        $record = (new SignedEvidence(config('publication.evidence_directory'), config('publication.collector_public_key')))->read($id);
        if (($record['repository'] ?? '') !== $project['repository'] || ($record['hml_url'] ?? '') !== $project['hml_url']
            || ($record['production_url'] ?? '') !== $project['production_url'] || ($record['executor'] ?? '') !== $project['executor']) {
            throw new RuntimeException('registry_identity_mismatch');
        }
        $repo = $project['repository'];
        $token = config('publication.github_token');
        if (!$token) {
            throw new RuntimeException('github_credentials_missing');
        }
        $github = Http::withToken($token)->acceptJson()->timeout(15)->baseUrl('https://api.github.com/repos/'.$repo);
        $head = $github->get('/commits/'.rawurlencode($project['candidate_ref']))->throw()->json('sha');
        $runs = $github->get('/actions/runs', ['head_sha' => $sha, 'per_page' => 100])->throw()->json('workflow_runs', []);
        $ci = $project['required_workflows'] !== [];
        foreach ($project['required_workflows'] as $path) {
            $matching = array_values(array_filter($runs, static fn (array $run): bool => ($run['path'] ?? '') === $path
                && ($run['head_sha'] ?? '') === $sha && ($run['head_repository']['full_name'] ?? '') === $repo));
            usort($matching, static fn (array $a, array $b): int => $b['id'] <=> $a['id']);
            $latest = $matching[0] ?? [];
            $passed = ($latest['status'] ?? '') === 'completed' && ($latest['conclusion'] ?? '') === 'success';
            $requiredSteps = $project['required_steps'][$path] ?? [];
            if (!$passed || $requiredSteps === []) {
                $ci = false;
                continue;
            }
            $jobs = $github->get('/actions/runs/'.$latest['id'].'/jobs', ['per_page' => 100])->throw()->json();
            $jobList = $jobs['jobs'] ?? [];
            $passed = count($jobList) > 0 && count($jobList) === ($jobs['total_count'] ?? 0);
            $steps = [];
            foreach ($jobList as $job) {
                $passed = $passed && ($job['status'] ?? '') === 'completed' && ($job['conclusion'] ?? '') === 'success';
                foreach ($job['steps'] ?? [] as $step) {
                    if (($step['status'] ?? '') === 'completed' && ($step['conclusion'] ?? '') === 'success') {
                        $steps[] = $step['name'];
                    }
                }
            }
            $ci = $ci && $passed && array_diff($requiredSteps, $steps) === [];
        }
        $host = parse_url($project['hml_url'], PHP_URL_HOST);
        $dns = filter_var(gethostbyname($host), FILTER_VALIDATE_IP) !== false;
        // HTTPS peer verification stays enabled; redirects cannot disguise the production host.
        $response = Http::timeout(15)->withoutRedirecting()->get($project['hml_url']);
        $health = $response->successful();
        $hml = $record['hml'];
        $prod = $record['production'];
        $isolatedRuntime = ($hml['container_id'] ?? '') !== '' && ($prod['container_id'] ?? '') !== ''
            && $hml['container_id'] !== $prod['container_id']
            && ($hml['running'] ?? false) === true && ($prod['running'] ?? false) === true;
        $isolatedData = true;
        foreach (['data_sources', 'secret_sources'] as $field) {
            $a = $hml[$field] ?? [];
            $b = $prod[$field] ?? [];
            $isolatedData = $isolatedData && is_array($a) && is_array($b) && count($a) > 0 && count($b) > 0 && !$this->overlap($a, $b);
        }
        // A shared network could expose production data even with separate mounts.
        // Until a measured network policy adapter exists, shared/unknown networks block.
        $isolatedData = $isolatedData && count($hml['networks'] ?? []) > 0 && count($prod['networks'] ?? []) > 0
            && array_intersect($hml['networks'], $prod['networks']) === [];
        $backup = $record['backup'] ?? [];
        $backupReady = ($backup['archive_valid'] ?? false) === true
            && preg_match('/^[a-f0-9]{64}$/D', $backup['sha256'] ?? '') === 1
            && ($backup['observed_at'] ?? 0) >= time() - 900;
        $checks = [
            'hml_url' => $project['hml_url'], 'production_url' => $project['production_url'],
            'hml_dns_ok' => $dns, 'hml_tls_ok' => $health, 'hml_health_ok' => $health,
            'isolated_runtime' => $isolatedRuntime, 'isolated_data' => $isolatedData,
            'ci_green' => $ci, 'tests_passed' => $ci,
            'backup_verified' => $backupReady,
            'rollback_ready' => $backupReady && ($record['rollback']['backup_sha256'] ?? '') === ($backup['sha256'] ?? '')
                && preg_match('/^[a-f0-9]{64}$/D', $record['rollback']['script_sha256'] ?? '') === 1,
            'tested_sha' => ($record['hml']['image_revision'] ?? '') === $sha && ($record['hml']['checkout_sha'] ?? '') === $sha
                && ($record['hml']['checkout_clean'] ?? false) === true && ($record['hml']['repository'] ?? '') === $repo ? $sha : '',
            'target_sha' => $head,
        ];
        return ['checks' => $checks, 'digest' => $record['digest'], 'executor' => $record['executor']];
    }
}
