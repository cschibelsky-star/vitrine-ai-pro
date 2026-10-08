<?php

namespace Tests\Feature;

use App\Console\Commands\RoteiaUsageAuditCommand;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class RoteiaUsageAuditTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake();
        Storage::fake('local');
        Schema::create('ai_consumptions', function (Blueprint $table): void {
            $table->id();
            $table->string('request_id');
        });
        Schema::create('ai_media_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('operation_id')->nullable();
            $table->string('asset_path')->nullable();
            $table->json('metadata')->nullable();
        });
    }

    private function audit(array $rows): CommandTester
    {
        $file = tmpfile();
        fputcsv($file, ['request_id', 'model', 'cost_brl']);
        foreach ($rows as $row) {
            fputcsv($file, $row);
        }
        fflush($file);
        $command = new RoteiaUsageAuditCommand;
        $command->setLaravel($this->app);
        $tester = new CommandTester($command);
        try {
            $tester->execute(['csv' => stream_get_meta_data($file)['uri']]);
        } finally {
            fclose($file);
        }
        Http::assertNothingSent();
        return $tester;
    }

    public function test_flux_unknown_models_and_opaque_ids_are_reconciled_without_writes(): void
    {
        $ids = ['billing-id', 'req_flux/abc:123', 'opaque,"quoted"', '670978bd-b070-4178-9f0a-2c6e261970a9'];
        $models = ['vendor/flux-pro', 'new-model', 'seedream', 'image-model'];
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII=');
        Storage::disk('local')->put('existing.png', $png);
        $rows = [];
        foreach ($ids as $index => $id) {
            DB::table('ai_consumptions')->insert(['request_id' => $id]);
            DB::table('ai_media_generations')->insert([
                'operation_id' => $index % 2 === 0 ? $id : 'other-'.$index,
                'asset_path' => 'existing.png',
                'metadata' => json_encode(['provider_request_id' => $id, 'storage_disk' => 'local']),
            ]);
            $rows[] = [$id, $models[$index], '0.50'];
        }
        $before = DB::table('ai_media_generations')->get()->toJson();
        $rows[] = ['unmatched-id', 'future-model', '0.25'];
        $tester = $this->audit($rows);
        $this->assertSame(0, $tester->getStatusCode(), $tester->getDisplay());
        $lines = explode("\n", trim($tester->getDisplay()));
        foreach ($ids as $index => $id) {
            $this->assertSame([$id, '0.50', '1', '1', 'file_verified'], str_getcsv($lines[$index + 1]));
        }
        $this->assertContains('unmatched-id,0.25,0,0,unverified', $lines);
        $this->assertSame($before, DB::table('ai_media_generations')->get()->toJson());
        $this->assertSame(4, DB::table('ai_consumptions')->count());
        $this->assertSame(['existing.png'], Storage::disk('local')->allFiles());
        $this->assertSame($png, Storage::disk('local')->get('existing.png'));
    }

    public function test_invalid_ids_are_rejected(): void
    {
        foreach (['', '   ', str_repeat('x', 256), "req\n123", "req\0bad"] as $id) {
            $tester = $this->audit([[$id, 'vendor/flux-pro', '0.50']]);
            $this->assertSame(1, $tester->getStatusCode());
        }
    }
}
