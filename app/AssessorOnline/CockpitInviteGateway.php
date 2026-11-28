<?php
declare(strict_types=1);

namespace App\AssessorOnline;

use Illuminate\Support\Facades\Http;
use RuntimeException;

final class CockpitInviteGateway
{
    public function enabled(): bool
    {
        return config('assessor-invite.via_cockpit') === true;
    }

    public function configured(): bool
    {
        return strlen((string) config('assessor-invite.secret')) >= 32;
    }

    public function send(string $email, string $token): void
    {
        $timestamp = (string) time();
        $signature = hash_hmac('sha256', "assessor-invite-v1\n{$timestamp}\n{$email}\n{$token}",
            (string) config('assessor-invite.secret'));

        $response = Http::acceptJson()->withOptions(['allow_redirects' => false])
            ->connectTimeout(4)->timeout(18)
            ->withHeaders(['X-Assessor-Invite-Timestamp' => $timestamp, 'X-Assessor-Invite-Signature' => $signature])
            ->post('https://hml.vitrineiapro.com.br/cockpit/internal/assessor-invite', [
                'email' => $email,
                'token' => $token,
            ]);

        if ($response->status() !== 200 || $response->json('ok') !== true) {
            throw new RuntimeException('Cockpit invitation delivery could not be confirmed.');
        }
    }
}
