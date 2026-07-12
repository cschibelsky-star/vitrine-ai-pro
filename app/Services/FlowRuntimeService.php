<?php

namespace App\Services;

use App\Models\FlowExecution;
use App\Models\FlowWorkflow;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class FlowRuntimeService
{
    public function __construct(
        private readonly FlowRuntimeGuardService $guards,
    ) {
    }

    public function start(FlowWorkflow $workflow, array $input, array $options = []): FlowExecution
    {
        if (! $workflow->is_active || $workflow->status !== 'active') {
            throw new RuntimeException('Workflow indisponível para execução.');
        }

        $companyId = $options['company_id'] ?? $workflow->company_id;

        if ($workflow->company_id !== null && (int) $workflow->company_id !== (int) $companyId) {
            throw new RuntimeException('Workflow não pertence à empresa informada.');
        }

        $executionUuid = (string) Str::uuid();
        $guardContext = $this->guards->prepare($workflow, $companyId, $executionUuid, $options);

        $execution = DB::transaction(function () use ($workflow, $input, $options, $companyId, $executionUuid, $guardContext): FlowExecution {
            return FlowExecution::create([
                'uuid' => $executionUuid,
                'company_id' => $companyId,
                'flow_workflow_id' => $workflow->getKey(),
                'workflow_uuid' => $workflow->uuid,
                'trace_id' => $options['trace_id'] ?? (string) Str::uuid(),
                'correlation_id' => $options['correlation_id'] ?? null,
                'status' => 'queued',
                'queue' => $options['queue'] ?? $workflow->queue ?? 'default',
                'priority' => $options['priority'] ?? $workflow->priority ?? 100,
                'provider' => $options['provider'] ?? $workflow->default_provider,
                'lock_owner' => $guardContext['lock']['owner'],
                'usage_reservation_uuid' => $guardContext['reservation']?->reservation_uuid,
                'input' => $input,
                'context' => [
                    'workflow_key' => $workflow->workflow_key,
                    'workflow_version' => $workflow->version,
                    'n8n_workflow_id' => $workflow->n8n_workflow_id,
                    'feature_flags' => $workflow->feature_flags ?? [],
                    'resolved_feature' => $guardContext['feature'],
                    'runtime_lock_name' => $guardContext['lock']['name'],
                    'usage_metric' => $options['usage_metric'] ?? null,
                    'usage_quantity' => $options['usage_quantity'] ?? null,
                    'metadata' => $options['metadata'] ?? [],
                ],
                'queued_at' => now(),
            ]);
        });

        try {
            $response = $this->client()->post($this->runtimeUrl(), [
                'event' => 'FLOW_EXECUTION_REQUESTED',
                'source' => 'vitrine-ai-pro',
                'execution' => [
                    'uuid' => $execution->uuid,
                    'workflow_uuid' => $execution->workflow_uuid,
                    'company_id' => $execution->company_id,
                    'trace_id' => $execution->trace_id,
                    'correlation_id' => $execution->correlation_id,
                    'queue' => $execution->queue,
                    'priority' => $execution->priority,
                    'provider' => $execution->provider,
                    'input' => $execution->input,
                    'context' => $execution->context,
                    'usage_reservation_uuid' => $execution->usage_reservation_uuid,
                ],
                'callback_url' => url('/api/flow/events/callback'),
                'telemetry_url' => url('/api/flow/telemetry'),
                'dlq_url' => url('/api/flow/dlq'),
                'usage_commit_url' => url('/api/flow/usage/commit'),
                'lock_release_url' => url('/api/flow/locks/release'),
            ]);

            if ($response->failed()) {
                throw new RuntimeException('Vitrine IA Flow retornou HTTP '.$response->status().'.');
            }

            $execution->update([
                'status' => 'dispatched',
                'output' => $response->json(),
                'attempts' => 1,
            ]);
        } catch (\Throwable $exception) {
            $context = $execution->context ?? [];
            $this->guards->release($context['runtime_lock_name'] ?? null, $execution->lock_owner);

            $execution->update([
                'status' => 'dispatch_failed',
                'failure_reason' => $exception->getMessage(),
                'attempts' => 1,
                'finished_at' => now(),
            ]);

            throw $exception;
        }

        return $execution->fresh();
    }

    private function client(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->timeout((int) config('vitrine_flow.timeout', 30));

        if ($token = config('vitrine_flow.token')) {
            $request = $request->withToken($token);
        }

        return $request;
    }

    private function runtimeUrl(): string
    {
        return rtrim((string) config('vitrine_flow.base_url'), '/')
            .'/'.ltrim((string) config('vitrine_flow.runtime_webhook', '/webhook/flow-runtime'), '/');
    }
}
