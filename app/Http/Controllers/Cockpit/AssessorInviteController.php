<?php
declare(strict_types=1);

namespace App\Http\Controllers\Cockpit;

use App\Services\CockpitWebmailService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

final class AssessorInviteController
{
    public function __invoke(Request $request, CockpitWebmailService $mail): JsonResponse
    {
        $secret = (string) config('assessor-invite.secret');
        $recipient = strtolower(trim((string) config('assessor-invite.recipient')));
        $origin = (string) config('assessor-invite.origin');
        abort_unless(config('app.env') === 'homologation' && strlen($secret) >= 32
            && filter_var($recipient, FILTER_VALIDATE_EMAIL)
            && preg_match('~\Ahttps://[a-z0-9-]+\.hml\.vitrineiapro\.com\.br\z~D', $origin), 404);

        $email = $request->input('email');
        $token = $request->input('token');
        $timestamp = (string) $request->header('X-Assessor-Invite-Timestamp', '');
        $signature = (string) $request->header('X-Assessor-Invite-Signature', '');
        abort_unless(is_string($email) && hash_equals($recipient, $email)
            && is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token)
            && strlen($timestamp) === 10 && ctype_digit($timestamp)
            && abs(time() - (int) $timestamp) <= 30
            && preg_match('/\A[a-f0-9]{64}\z/', $signature), 401);

        $expected = hash_hmac('sha256', "assessor-invite-v1\n{$timestamp}\n{$email}\n{$token}", $secret);
        abort_unless(hash_equals($expected, $signature), 401);
        abort_unless(Cache::add('assessor-hml-invite:'.hash('sha256', $token), true, 120), 409);

        $url = $origin.'/assessor/redefinir-senha/'.rawurlencode($token).'?email='.rawurlencode($recipient);
        try {
            $mail->send('vendas', $recipient, 'Assessor Online - Convite de primeiro acesso',
                "Ative seu acesso ao Assessor Online definindo sua senha: {$url}\nO link é de uso único.");
        } catch (\Throwable) {
            return response()->json(['ok' => false], 502)->header('Cache-Control', 'no-store');
        }

        return response()->json(['ok' => true])->header('Cache-Control', 'no-store');
    }
}
