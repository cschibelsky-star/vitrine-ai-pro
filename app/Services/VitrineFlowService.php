<?php

namespace App\Services;

use App\Models\FlowWorkflow;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class VitrineFlowService
{
    public function __construct(
        private readonly FlowRuntimeService $runtime,
    ) {
    }

    public function dispatchProvisioning(Payment $payment): array
    {
        $payment->loadMissing(['company', 'product', 'plan', 'contract']);

        if ((bool) config('vitrine_flow.prefer_runtime', true)) {
            $workflow = $this->resolveProvisioningWorkflow($payment);

            if ($workflow !== null) {
                $execution = $this->runtime->start(
                    $workflow,
                    $this->provisioningPayload($payment),
                    [
                        'company_id' => $payment->company?->getKey(),
                        'correlation_id' => (string) Str::uuid(),
                        'metadata' => [
                            'source' => 'payment.approved',
                            'payment_id' => $payment->getKey(),
                        ],
                    ],
                );

                return [
                    'accepted' => true,
                    'mode' => 'runtime',
                    'execution_uuid' => $execution->uuid,
                    'workflow_uuid' => $workflow->uuid,
                ];
            }

            if (! (bool) config('vitrine_flow.legacy_fallback', true)) {
                throw new RuntimeException('Workflow canônico de provisionamento não está registrado e ativo.');
            }
        }

        return $this->dispatchLegacyProvisioning($payment);
    }

    private function resolveProvisioningWorkflow(Payment $payment): ?FlowWorkflow
    {
        $workflowKey = (string) config('vitrine_flow.provision_workflow_key', 'provision_product');
        $companyId = $payment->company?->getKey();

        return FlowWorkflow::query()
            ->where('workflow_key', $workflowKey)
            ->where('status', 'active')
            ->where('is_active', true)
            ->where(function ($query) use ($companyId): void {
                if ($companyId !== null) {
                    $query->where('company_id', $companyId)
                        ->orWhereNull('company_id');

                    return;
                }

                $query->whereNull('company_id');
            })
            ->orderByRaw('CASE WHEN company_id IS NULL THEN 1 ELSE 0 END')
            ->orderByDesc('id')
            ->first();
    }

    private function dispatchLegacyProvisioning(Payment $payment): array
    {
        $response = $this->client()->post($this->provisionUrl(), $this->provisioningPayload($payment));

        if ($response->failed()) {
            throw new RuntimeException('Falha ao acionar Vitrine IA Flow: HTTP '.$response->status());
        }

        return array_merge(
            ['accepted' => true, 'mode' => 'legacy'],
            $response->json() ?? [],
        );
    }

    private function provisioningPayload(Payment $payment): array
    {
        return [
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
        ];
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
