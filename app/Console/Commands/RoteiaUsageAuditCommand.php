<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class RoteiaUsageAuditCommand extends Command
{
    protected $signature = 'ai:roteia-audit {csv : Caminho local do CSV exportado da Roteia}';
    protected $description = 'Cruza cobranças Roteia com registros e arquivos, somente leitura, sem gerar mídia';

    public function handle(): int
    {
        $file = fopen((string) $this->argument('csv'), 'r');
        if ($file === false) {
            $this->error('CSV indisponível.');
            return self::FAILURE;
        }
        try {
            $header = fgetcsv($file);
            if (! is_array($header)) {
                throw new \RuntimeException('CSV vazio.');
            }
            $header[0] = preg_replace('/^\\xEF\\xBB\\xBF/', '', $header[0]);
            foreach (['request_id', 'model', 'cost_brl'] as $column) {
                if (! in_array($column, $header, true)) {
                    throw new \RuntimeException('Coluna obrigatória ausente: '.$column);
                }
            }
            $mediaAvailable = Schema::hasTable('ai_media_generations');
            $ledgerAvailable = Schema::hasTable('ai_consumptions')
                && Schema::hasColumn('ai_consumptions', 'request_id');
            $this->line('request_id,cost_brl,ledger_matches,media_matches,delivery');
            while (($values = fgetcsv($file)) !== false) {
                if (count($values) !== count($header)) {
                    throw new \RuntimeException('Linha CSV inválida.');
                }
                $row = array_combine($header, $values);
                // Audit every exported row: model names do not reliably identify media types.
                $id = $row['request_id'];
                if (trim($id) === '' || strlen($id) > 255 || preg_match('/[\x00-\x1F\x7F]/', $id)) {
                    throw new \RuntimeException('ID de requisição inválido.');
                }
                $ledgerCount = $ledgerAvailable
                    ? DB::table('ai_consumptions')->where('request_id', $id)->count()
                    : null;
                $media = $mediaAvailable
                    ? DB::table('ai_media_generations')
                        ->where(function ($query) use ($id): void {
                            $query->where('operation_id', $id)
                                ->orWhere('metadata->provider_request_id', $id);
                        })->get(['id', 'asset_path', 'metadata'])
                    : collect();
                $delivery = 'unverified';
                foreach ($media as $generation) {
                    $metadata = json_decode((string) $generation->metadata, true) ?: [];
                    $disk = $metadata['storage_disk'] ?? config('filesystems.default', 'local');
                    if ($generation->asset_path && Storage::disk($disk)->exists($generation->asset_path)) {
                        $stream = Storage::disk($disk)->readStream($generation->asset_path);
                        if (is_resource($stream)) {
                            try {
                                $binary = stream_get_contents($stream, 20 * 1024 * 1024 + 1);
                                if (is_string($binary) && strlen($binary) <= 20 * 1024 * 1024
                                    && @getimagesizefromstring($binary) !== false) {
                                    $delivery = 'file_verified';
                                }
                            } finally {
                                fclose($stream);
                            }
                        }
                    }
                }
                $csvLine = fopen('php://temp', 'r+');
                fputcsv($csvLine, [$id, $row['cost_brl'], $ledgerCount ?? 'unavailable', $media->count(), $delivery], ',', '"', '');
                rewind($csvLine);
                $this->line(rtrim(stream_get_contents($csvLine), "\r\n"));
                fclose($csvLine);
            }
            $this->info('Auditoria somente leitura. Ausência de vínculo não comprova perda ou ausência de cobrança.');
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Auditoria interrompida; nenhuma geração ou alteração executada.');
            return self::FAILURE;
        } finally {
            fclose($file);
        }
    }
}
