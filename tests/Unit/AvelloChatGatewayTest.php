<?php

namespace Tests\Unit;

use App\Services\Ai\AvelloChatGateway;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AvelloChatGatewayTest extends TestCase
{
    public function test_disabled_adapter_never_makes_http_call(): void
    {
        config(['avello.enabled' => false]);
        Http::fake();

        $this->expectExceptionMessage('avello_disabled');
        try {
            app(AvelloChatGateway::class)->complete('gemini-3.1-flash-lite', 'Teste.');
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_calls_allowed_model_and_extracts_usage(): void
    {
        config(['avello' => [
            'enabled' => true,
            'key' => 'fake-test-key',
            'base_url' => 'https://avello.online/v1',
            'models' => ['gemini-3.1-flash-lite'],
        ]]);
        Http::fake(['avello.online/v1/chat/completions' => Http::response([
            'choices' => [['message' => ['content' => 'CONEXAO_OK']]],
            'usage' => ['prompt_tokens' => 10, 'completion_tokens' => 5],
        ])]);

        $result = app(AvelloChatGateway::class)->complete('gemini-3.1-flash-lite', 'Teste.');
        $this->assertSame('CONEXAO_OK', $result['text']);
        $this->assertSame(10, $result['usage']['prompt_tokens']);
        Http::assertSentCount(1);
    }
}
