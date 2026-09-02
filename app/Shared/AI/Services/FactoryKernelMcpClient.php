<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class FactoryKernelMcpClient
{
    private ?string $sessionId = null;

    public function manifest(): array
    {
        return $this->callTool('factory_kernel_manifest');
    }

    public function decide(string $capability): array
    {
        return $this->callTool('factory_kernel_decide', ['capability' => $capability]);
    }

    public function available(): bool
    {
        if (! (bool) config('factory_kernel.enabled', true)) {
            return false;
        }

        try {
            $manifest = $this->manifest();
            return ($manifest['kernel_version'] ?? null) !== null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function callTool(string $name, array $arguments = []): array
    {
        if (! (bool) config('factory_kernel.enabled', true)) {
            throw new RuntimeException('Factory Kernel MCP está desabilitado.');
        }

        $this->initialize();

        $response = $this->request()->withHeaders([
            'Mcp-Session-Id' => (string) $this->sessionId,
        ])->post($this->url(), [
            'jsonrpc' => '2.0',
            'id' => uniqid('kernel_', true),
            'method' => 'tools/call',
            'params' => [
                'name' => $name,
                'arguments' => (object) $arguments,
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Falha ao consultar Factory Kernel MCP: HTTP '.$response->status().'.');
        }

        $payload = $this->decodePayload($response->body());
        if (isset($payload['error'])) {
            throw new RuntimeException((string) data_get($payload, 'error.message', 'Erro retornado pelo Factory Kernel MCP.'));
        }

        $structured = data_get($payload, 'result.structuredContent');
        if (is_array($structured)) {
            return $structured;
        }

        $content = (array) data_get($payload, 'result.content', []);
        foreach ($content as $item) {
            if (($item['type'] ?? null) !== 'text') {
                continue;
            }
            $decoded = json_decode((string) ($item['text'] ?? ''), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Factory Kernel MCP respondeu sem conteúdo estruturado utilizável.');
    }

    private function initialize(): void
    {
        if ($this->sessionId !== null) {
            return;
        }

        $response = $this->request()->post($this->url(), [
            'jsonrpc' => '2.0',
            'id' => uniqid('init_', true),
            'method' => 'initialize',
            'params' => [
                'protocolVersion' => (string) config('factory_kernel.protocol_version', '2025-03-26'),
                'capabilities' => (object) [],
                'clientInfo' => [
                    'name' => 'vitrine-core-factory-kernel-client',
                    'version' => '1.0.0',
                ],
            ],
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Não foi possível inicializar sessão MCP do Factory Kernel: HTTP '.$response->status().'.');
        }

        $this->decodePayload($response->body());
        $sessionId = $response->header('Mcp-Session-Id');
        if (! is_string($sessionId) || trim($sessionId) === '') {
            throw new RuntimeException('Factory Kernel MCP não retornou identificador de sessão.');
        }

        $this->sessionId = trim($sessionId);

        $notify = $this->request()->withHeaders([
            'Mcp-Session-Id' => $this->sessionId,
        ])->post($this->url(), [
            'jsonrpc' => '2.0',
            'method' => 'notifications/initialized',
        ]);

        if (! $notify->successful()) {
            throw new RuntimeException('Factory Kernel MCP rejeitou a confirmação de inicialização: HTTP '.$notify->status().'.');
        }
    }

    private function request(): PendingRequest
    {
        return Http::accept('application/json, text/event-stream')
            ->asJson()
            ->timeout(max(2, (int) config('factory_kernel.timeout', 8)))
            ->withHeaders([
                'MCP-Protocol-Version' => (string) config('factory_kernel.protocol_version', '2025-03-26'),
            ]);
    }

    private function decodePayload(string $body): array
    {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        foreach (preg_split('/\R/', $body) ?: [] as $line) {
            if (! str_starts_with($line, 'data:')) {
                continue;
            }
            $candidate = trim(substr($line, 5));
            if ($candidate === '' || $candidate === '[DONE]') {
                continue;
            }
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new RuntimeException('Resposta MCP inválida ou não suportada.');
    }

    private function url(): string
    {
        $url = trim((string) config('factory_kernel.url'));
        if ($url === '') {
            throw new RuntimeException('URL do Factory Kernel MCP não configurada.');
        }
        return $url;
    }
}
