<?php

namespace Tests\Feature;

use App\Services\RoteiaLab\RoteiaLabFinanceReader;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class RoteiaLabFinanceReaderTest extends TestCase
{
    public function test_unconfigured_finance_reader_does_not_make_requests(): void
    {
        config()->set('roteia_lab.base_url', '');
        config()->set('roteia_lab.read_token', '');
        config()->set('roteia_lab.allowed_host', '');
        Http::preventStrayRequests();

        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertFalse($snapshot['connected']);
        $this->assertSame('not_configured', $snapshot['reason']);
        $this->assertNull($snapshot['summary']);
        Http::assertNothingSent();
    }

    public function test_unsafe_or_unexpected_hosts_are_blocked(): void
    {
        config()->set('roteia_lab.base_url', 'http://127.0.0.1:5000');
        config()->set('roteia_lab.allowed_host', '127.0.0.1');
        config()->set('roteia_lab.read_token', 'local-test-token');
        Http::preventStrayRequests();

        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertFalse($snapshot['connected']);
        $this->assertSame('invalid_endpoint', $snapshot['reason']);
        Http::assertNothingSent();
    }

    public function test_authenticated_read_only_summary_requires_all_three_valid_responses(): void
    {
        config()->set('roteia_lab.base_url', 'https://finance.example.test');
        config()->set('roteia_lab.allowed_host', 'finance.example.test');
        config()->set('roteia_lab.read_token', 'local-test-token');
        Http::fake([
            'finance.example.test/finance/summary' => Http::response(['saldo_disponivel' => null, 'consumo_conciliado' => null, 'consumo_registrado_local' => '0.35', 'reservas_pendentes' => '0.10', 'balance_source' => 'not_connected'], 200),
            'finance.example.test/finance/projects*' => Http::response(['data' => [['id' => 'via-apk', 'name' => 'VIA APK', 'status' => 'standby']]], 200),
            'finance.example.test/finance/transactions*' => Http::response(['data' => [['request_id' => 'example-id', 'project_id' => 'via-apk', 'status' => 'pending']]], 200),
        ]);
        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertTrue($snapshot['connected']);
        $this->assertNull($snapshot['summary']['saldo_disponivel']);
        $this->assertSame('0.35', $snapshot['summary']['consumo_registrado_local']);
        $this->assertSame('VIA APK', $snapshot['projects'][0]['name']);
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer local-test-token'));
    }

    public function test_failed_upstream_response_does_not_fabricate_financial_data(): void
    {
        config()->set('roteia_lab.base_url', 'https://finance.example.test');
        config()->set('roteia_lab.allowed_host', 'finance.example.test');
        config()->set('roteia_lab.read_token', 'local-test-token');
        Http::fake(['finance.example.test/*' => Http::response(['error' => 'unavailable'], 503)]);

        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertFalse($snapshot['connected']);
        $this->assertNull($snapshot['summary']);
        $this->assertSame([], $snapshot['projects']);
    }
    public function test_success_http_with_malformed_finance_payload_fails_closed(): void
    {
        config()->set('roteia_lab.base_url', 'https://finance.example.test');
        config()->set('roteia_lab.allowed_host', 'finance.example.test');
        config()->set('roteia_lab.read_token', 'local-test-token');
        Http::fake(['finance.example.test/*' => Http::response(['unrelated' => 'value'], 200)]);

        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertFalse($snapshot['connected']);
        $this->assertSame('invalid_response', $snapshot['reason']);
    }

    public function test_redirect_does_not_follow_or_display_data(): void
    {
        config()->set('roteia_lab.base_url', 'https://finance.example.test');
        config()->set('roteia_lab.allowed_host', 'finance.example.test');
        config()->set('roteia_lab.read_token', 'local-test-token');
        Http::fake(['finance.example.test/*' => Http::response('', 302, ['Location' => 'https://other.test/collect'])]);

        $snapshot = app(RoteiaLabFinanceReader::class)->snapshot();

        $this->assertFalse($snapshot['connected']);
        $this->assertSame('upstream_unavailable', $snapshot['reason']);
        Http::assertSentCount(3);
    }

}
