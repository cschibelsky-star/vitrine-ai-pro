<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\VitrineFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class VitrineFlowProvisionController extends Controller
{
    public function dispatch(Request $request, VitrineFlowService $flow): JsonResponse
    {
        $validated = $request->validate([
            'payment_id' => ['required', 'integer', 'exists:payments,id'],
        ]);

        $payment = Payment::findOrFail($validated['payment_id']);

        if (! in_array(strtolower((string) $payment->status), ['pago', 'paid', 'aprovado', 'approved'], true)) {
            return response()->json([
                'ok' => false,
                'message' => 'O pagamento ainda não está aprovado.',
            ], 422);
        }

        try {
            $result = $flow->dispatchProvisioning($payment);

            Log::info('Vitrine IA Flow acionado', [
                'payment_id' => $payment->getKey(),
                'result' => $result,
            ]);

            return response()->json([
                'ok' => true,
                'message' => 'Provisionamento enviado para a Vitrine IA Flow.',
                'flow' => $result,
            ], 202);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => 'Não foi possível acionar a Vitrine IA Flow.',
            ], 502);
        }
    }

    public function callback(Request $request): JsonResponse
    {
        $expectedToken = (string) config('vitrine_flow.callback_token');
        $receivedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json(['message' => 'Não autorizado.'], 401);
        }

        $payload = $request->validate([
            'execution_id' => ['required', 'string', 'max:255'],
            'payment_id' => ['nullable', 'integer'],
            'status' => ['required', 'string', 'max:60'],
            'step' => ['nullable', 'string', 'max:120'],
            'progress' => ['nullable', 'integer', 'between:0,100'],
            'message' => ['nullable', 'string', 'max:2000'],
            'data' => ['nullable', 'array'],
        ]);

        Log::info('Retorno da Vitrine IA Flow', $payload);

        return response()->json([
            'ok' => true,
            'received_at' => now()->toIso8601String(),
        ]);
    }
}
