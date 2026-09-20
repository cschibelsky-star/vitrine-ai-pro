<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Heygen\HeygenService;
use Illuminate\Http\Request;

class HeygenCallbackController extends Controller
{
    public function handle(Request $request, HeygenService $service)
    {
        $job = $service->handleCallback($request->all());

        return response()->json([
            'ok' => true,
            'job_found' => (bool) $job,
            'job_id' => $job?->id,
            'status' => $job?->status,
        ]);
    }

    public function handleSigned(Request $request, HeygenService $service)
    {
        $secret = trim((string) config('centro_ia.heygen_webhook_secret'));

        if ($secret === '') {
            return response()->json([
                'ok' => false,
                'error' => 'heygen_webhook_secret_not_configured',
            ], 503);
        }

        $signature = (string) (
            $request->header('signature')
            ?: $request->header('x-heygen-signature')
            ?: $request->header('x-heygen-signature-256')
        );

        $signature = strtolower(trim($signature));
        if (str_starts_with($signature, 'sha256=')) {
            $signature = substr($signature, 7);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if ($signature === '' || !hash_equals($expected, $signature)) {
            return response()->json([
                'ok' => false,
                'error' => 'invalid_signature',
            ], 401);
        }

        $job = $service->handleCallback($request->all());

        return response()->json([
            'ok' => true,
            'job_found' => (bool) $job,
            'job_id' => $job?->id,
            'status' => $job?->status,
        ]);
    }
}
