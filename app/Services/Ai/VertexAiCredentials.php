<?php

namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class VertexAiCredentials
{
    private const TOKEN_URI = 'https://oauth2.googleapis.com/token';

    public function accessToken(): string
    {
        $token = trim((string) env('GOOGLE_VERTEX_ACCESS_TOKEN', ''));
        if ($token !== '') {
            return $token;
        }

        $path = trim((string) env('GOOGLE_APPLICATION_CREDENTIALS', ''));
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            throw new RuntimeException('Credenciais Vertex ausentes ou indisponíveis.');
        }

        // Only service-account JSON is supported. Never follow URLs from credential files.
        $credentials = json_decode((string) file_get_contents($path), true);
        if (! is_array($credentials)
            || ($credentials['type'] ?? null) !== 'service_account'
            || ! is_string($credentials['client_email'] ?? null)
            || ! is_string($credentials['private_key'] ?? null)
            || ! filter_var($credentials['client_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Vertex exige arquivo JSON de conta de serviço válido.');
        }

        $key = @openssl_pkey_get_private($credentials['private_key']);
        $details = $key ? openssl_pkey_get_details($key) : false;
        if (! $details || $details['type'] !== OPENSSL_KEYTYPE_RSA || $details['bits'] < 2048) {
            throw new RuntimeException('Chave de conta de serviço Vertex inválida.');
        }

        $issuedAt = time();
        $header = $this->encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $claims = $this->encode(json_encode([
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/cloud-platform',
            'aud' => self::TOKEN_URI,
            'iat' => $issuedAt,
            'exp' => $issuedAt + 3600,
        ], JSON_THROW_ON_ERROR));
        $unsigned = $header.'.'.$claims;
        if (! openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
            throw new RuntimeException('Falha ao preparar autenticação Vertex.');
        }

        try {
            $response = Http::asForm()->acceptJson()->timeout(30)
                ->withOptions(['allow_redirects' => false])
                ->post(self::TOKEN_URI, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $unsigned.'.'.$this->encode($signature),
                ]);
        } catch (Throwable) {
            // Do not persist JWTs, tokens, private keys or raw provider exceptions.
            throw new RuntimeException('Falha de comunicação na autenticação Vertex.');
        }

        $token = $response->json('access_token');
        if (! $response->successful() || ! is_string($token) || trim($token) === ''
            || (int) $response->json('expires_in', 0) < 1) {
            throw new RuntimeException('Autenticação Vertex recusada ou resposta inválida.');
        }

        return $token;
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
