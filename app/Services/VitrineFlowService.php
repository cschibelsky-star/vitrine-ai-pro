<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class VitrineFlowService
{
    public function dispatchProvisioning(Payment $payment): array
    {
        $payment->loadMissing(['company', 'product', 'plan', 'contract']);

        $response = $this->client()->post($this->provisionUrl(), [
            'event' => 'payment.approved',
            'source' => 'vitrine-ai-pro',
            'occurred_at' => now()->toIso8601String(),
            'payment' => [
                'id' => $payment->getKey(),
                'status' => $payment->status,
                'amount' => (string) $payment->valor,
                'paid_at' => optional($payment->data_pagamento)->toIso8601String(),
                'external_reference' => $payment->referencia_externa,
                'payment_method' => $payment->forma_pagamento,
            ],
            'company' => [
                'id' => $payment->company?->getKey(),
                'name' => $payment->company?->nome ?? $payment->company?->name,
            ],
            'product' => [
                'id' => $payment->product?->getKey(),
                'name' => $payment->product?->nome ?? $payment->product?->name,
                'slug' => $payment->product?->slug,
            ],
            'plan' => [
                'id' => $payment->plan?->getKey(),
                'name' => $payment->plan?->nome ?? $payment->plan?->name,
                'slug' => $payment->plan?->slug,
            ],
            'contract_id' => $payment->contract?->getKey(),
            'callback_url' => url('/api/vitrine-flow/provision/callback'),
        ]);

        if ($response->failed()) {
            throw new RuntimeException('Falha ao acionar Vitrine IA Flow: HTTP '.$response->status());
        }

        return $response->json() ?? ['accepted' => true];
    }

    private function client(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout(config('vitrine_flow.timeout', 30));

        if ($token = config('vitrine_flow.token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function provisionUrl(): string
    {
        return rtrim((string) config('vitrine_flow.base_url'), '/')
            .'/'.ltrim((string) config('vitrine_flow.provision_webhook'), '/');
    }
}
