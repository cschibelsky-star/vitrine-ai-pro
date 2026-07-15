<?php

declare(strict_types=1);

namespace App\Commercial\Factory\Services;

use Illuminate\Support\Facades\File;

class CommercialFactoryStatusService
{
    public function list(): array
    {
        $base = storage_path('app/factory/commercial-intake');

        if (! File::isDirectory($base)) {
            return [];
        }

        return collect(File::directories($base))
            ->map(function (string $dir): array {
                $reportPath = $dir.'/commercial_factory_report.json';
                $intakePath = $dir.'/commercial_intake.json';

                $report = File::exists($reportPath)
                    ? json_decode((string) File::get($reportPath), true)
                    : [];

                $intake = File::exists($intakePath)
                    ? json_decode((string) File::get($intakePath), true)
                    : [];

                return [
                    'project' => $this->scalar($report['project_slug'] ?? basename($dir)),
                    'client' => $this->extractClient($intake),
                    'product' => $this->extractProduct($intake),
                    'plan' => $this->extractPlan($intake),
                    'status' => $this->scalar($report['commercial_status'] ?? ($report['status'] ?? '-')),
                ];
            })
            ->sortByDesc('project')
            ->values()
            ->all();
    }

    protected function extractClient(array $intake): string
    {
        $client = $intake['client'] ?? '-';

        if (is_array($client)) {
            return $this->scalar($client['name'] ?? '-');
        }

        return $this->scalar($client);
    }

    protected function extractProduct(array $intake): string
    {
        $product = $intake['product'] ?? '-';

        if (is_array($product)) {
            return $this->scalar($product['name'] ?? '-');
        }

        return $this->scalar($product);
    }

    protected function extractPlan(array $intake): string
    {
        $plan = $intake['plan'] ?? '-';

        if (is_array($plan)) {
            return $this->scalar($plan['label'] ?? ($plan['key'] ?? '-'));
        }

        return $this->scalar($plan);
    }

    protected function scalar(mixed $value): string
    {
        if (is_null($value)) {
            return '-';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '-';
        }

        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return '-';
    }
}
