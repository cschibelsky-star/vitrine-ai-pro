<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoteiaImageSmokeCommandTest extends TestCase
{
    public function test_default_is_dry_run_without_api_call(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $exit = Artisan::call('ai:roteia-image-smoke', ['--test-id' => 'ci-dryrun-001']);
        $this->assertSame(0, $exit);
        $this->assertStringContainsString('"mode":"dry-run"', Artisan::output());
        Http::assertNothingSent();
    }

    public function test_charge_requires_explicit_opt_in(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $exit = Artisan::call('ai:roteia-image-smoke', [
            '--execute' => true,
            '--test-id' => 'ci-noauth-001',
        ]);
        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_budget_guard_rejects_reference_price_above_limit(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $exit = Artisan::call('ai:roteia-image-smoke', [
            '--execute' => true,
            '--confirm-charge' => 'EXECUTAR',
            '--max-brl' => '0.10',
            '--test-id' => 'ci-budget-001',
        ]);
        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    public function test_model_allowlist_rejects_unknown_model(): void
    {
        Http::preventStrayRequests();
        Http::fake();
        $exit = Artisan::call('ai:roteia-image-smoke', [
            '--model' => 'unknown/high-price',
            '--test-id' => 'ci-model-001',
        ]);
        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }
}
