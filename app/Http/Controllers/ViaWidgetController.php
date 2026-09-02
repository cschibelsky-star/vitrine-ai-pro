<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Contract;
use App\Models\Lead;
use App\Models\License;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Shared\AI\Models\ViaConversation;
use App\Shared\AI\Models\ViaMessage;
use App\Shared\AI\Services\ViaConversationIntentRouter;
use App\Shared\AI\Services\ViaObserverMissionOrchestrator;
use App\Shared\AI\Services\ViaSentinelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ViaWidgetController extends Controller
{
    public function status(ViaSentinelService $sentinel): JsonResponse
    {
        return response()->json([
            'ok' => true,
            ...$sentinel->status(),
        ]);
    }

    public function chat(Request $request, ViaConversationIntentRouter $intentRouter, ViaObserverMissionOrchestrator $orchestrator, ViaSentinelService $sentinel): JsonResponse
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:4000'],
            'history' => ['nullable', 'array', 'max:20'],
            'history.*.role' => ['nullable', 'string', 'max:20'],
            'history.*.content' => ['nullable', 'string', 'max:4000'],
            'context' => ['nullable', 'array'],
            'sessionId' => ['nullable', 'string', 'max:120'],
        ]);

        $message = trim((string) $data['message']);
        $context = $this->enrichOperationalContext((array) ($data['context'] ?? []));
        $data['context'] = $context;
        $request->merge(['context' => $context]);
        $conversation = $this->resolveConversation($request, $data, $context, $message);
        $conversation->messages()->create([
            'role' => 'user',
            'content' => $message,
            'metadata' => ['context' => $context],
        ]);
        $conversation->forceFill(['last_activity_at' => now()])->save();

        $localResponse = $intentRouter->resolve($message, $request);
        if ($localResponse !== null) {
            $intent = (string) ($localResponse['intent'] ?? 'local_context');
            $localMeta = (array) ($localResponse['metadata'] ?? []);

            if ($intent === 'prepare_factory_analysis' && (bool) ($localMeta['context_available'] ?? false)) {
                try {
                    $operational = (array) ($context['operational'] ?? []);
                    $screen = (array) ($context['screen'] ?? []);
                    $fields = array_values(array_filter((array) ($screen['fields'] ?? []), 'is_array'));
                    $fieldLines = [];
                    foreach (array_slice($fields, 0, 24) as $field) {
                        $label = trim((string) ($field['label'] ?? 'campo'));
                        $value = trim((string) ($field['value'] ?? ''));
                        if ($value !== '') {
                            $fieldLines[] = $label . ': ' . $value;
                        }
                    }

                    $screenText = trim((string) ($screen['text'] ?? ''));
                    $contextSummary = trim((string) ($operational['summary'] ?? ''));
                    if ($contextSummary === '') {
                        $contextSummary = $fieldLines !== [] ? implode("\n", $fieldLines) : mb_substr($screenText, 0, 5000);
                    }

                    /** @var \App\Shared\AI\Services\FactoryKernelMcpClient $kernel */
                    $kernel = app(\App\Shared\AI\Services\FactoryKernelMcpClient::class);

                    // Factory Kernel is an advisory source, not a hard dependency for discovery-first analysis.
                    // Read-only analysis must continue with Observer evidence when the Kernel is unavailable
                    // or rejects authentication. Mutable actions remain governed by the orchestrator.
                    $decision = [];
                    $kernelAvailable = true;
                    $kernelError = null;
                    try {
                        $decision = $kernel->decide($contextSummary !== '' ? $contextSummary : $message);
                    } catch (\Throwable $kernelException) {
                        report($kernelException);
                        $kernelAvailable = false;
                        $kernelError = 'factory_kernel_unavailable';
                    }
                    $screenContext = trim($contextSummary);
                    $mission = 'Prepare uma análise discovery-first usando prioritariamente a demanda e os dados da tela atual. '
                        . 'Não execute arquitetura, build, instalação, deploy ou alteração de estado. '
                        . 'Identifique o que deve ser reutilizado ou evoluído, riscos, lacunas de evidência e o próximo checkpoint de aprovação.';

                    if ($screenContext !== '') {
                        $mission .= "\n\nCONTEXTO_DA_TELA_ATUAL:\n" . mb_substr($screenContext, 0, 5000);
                    }

                    if ($kernelAvailable) {
                        $kernelDecision = json_encode($decision, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';
                        $mission .= "\n\nDECISAO_PREVIA_DO_FACTORY_KERNEL:\n" . mb_substr($kernelDecision, 0, 3000);
                    } else {
                        $mission .= "\n\nFACTORY_KERNEL_STATUS:\nFonte complementar indisponível nesta execução. Continue a análise discovery-first com as evidências read-only disponíveis; não trate a indisponibilidade do Kernel como bloqueio da análise.";
                    }

                    $analysis = $orchestrator->execute([
                        'domain' => 'factory',
                        'target_project_id' => (string) ($context['project'] ?? 'vitrine-ia-pro-core'),
                        'mission' => $mission,
                    ]);

                    $answer = trim((string) ($analysis['report'] ?? ''));
                    if ($answer === '') {
                        $answer = 'A análise estruturada foi executada em modo OBSERVER, mas o relatório textual veio vazio. Nenhuma alteração foi executada.';
                    }

                    $meta = $localMeta + [
                        'source' => 'via_widget_factory_analysis',
                        'intent' => $intent,
                        'kernel_decision' => $decision['decision'] ?? null,
                        'kernel_available' => $kernelAvailable,
                        'kernel_error' => $kernelError,
                        'structured_runtime_valid' => (bool) data_get($analysis, 'structured_runtime.valid', false),
                        'execution_ready' => (bool) data_get($analysis, 'structured_runtime.execution_ready', false),
                    ];
                    $this->persistAssistant($conversation, $answer, $meta);

                    return response()->json([
                        'ok' => true,
                        'answer' => $answer,
                        'model' => data_get($analysis, 'result.analysis.model'),
                        'sessionId' => (string) $conversation->id,
                        'persisted' => true,
                        'source' => 'via_widget_factory_analysis',
                        'intent' => $intent,
                        'mode' => 'OBSERVER',
                    ]);
                } catch (\Throwable $e) {
                    report($e);
                }
            }

            $answer = (string) ($localResponse['content'] ?? '');
            $meta = $localMeta + [
                'source' => 'via_local_context',
                'intent' => $intent,
            ];

            $this->persistAssistant($conversation, $answer, $meta);

            return response()->json([
                'ok' => true,
                'answer' => $answer,
                'model' => null,
                'sessionId' => (string) $conversation->id,
                'persisted' => true,
                'source' => 'via_local_context',
                'intent' => $intent,
                'mode' => 'OBSERVER',
            ]);
        }

        $contextText = strtolower(json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        $sentinelIntent = preg_match('/\b(aten[cç][aã]o|alerta|alertas|problema|problemas|risco|riscos|advert[eê]ncia|advert[eê]ncias|sentinel|monitoramento|supervis[aã]o)\b/ui', $message) === 1;

        if ($sentinelIntent) {
            $brief = $sentinel->advisorySummary();

            $this->persistAssistant($conversation, (string) $brief['answer'], ['source' => 'via_sentinel']);

            return response()->json([
                'ok' => true,
                'answer' => $brief['answer'],
                'model' => null,
                'sessionId' => (string) $conversation->id,
                'persisted' => true,
                'source' => 'via_sentinel',
                'mode' => 'OBSERVER',
                'sentinel_state' => $brief['state'],
                'signals' => $brief['signals'],
            ]);
        }

        $factoryIntent = preg_match('/\b(factory|fabrica|fábrica|construa|construir|crie|criar|cadastro|modulo|módulo|projeto|deploy|homologacao|homologação)\b/ui', $message) === 1
            || str_contains($contextText, 'factory');

        if ($factoryIntent) {
            try {
                $analysis = $orchestrator->execute([
                    'domain' => 'factory',
                    'target_project_id' => (string) ($context['project'] ?? ''),
                    'mission' => $message,
                    'profile' => 'balanced',
                ]);

                $answer = trim((string) ($analysis['report'] ?? ''));
                if ($answer !== '') {
                    $this->persistAssistant($conversation, $answer, ['source' => 'via_agent_hub']);

                    return response()->json([
                        'ok' => true,
                        'answer' => $answer,
                        'model' => data_get($analysis, 'result.analysis.model'),
                        'sessionId' => (string) $conversation->id,
                        'persisted' => true,
                        'source' => 'via_agent_hub',
                        'mode' => data_get($analysis, 'pipeline.mode', 'OBSERVER'),
                    ]);
                }
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $baseUrl = rtrim((string) env('VIA_SERVICE_URL', 'http://via_hml_v04:3000'), '/');

        try {
            $response = Http::timeout(90)->acceptJson()->post($baseUrl . '/api/via', $data);
            $payload = $response->json();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'error' => 'via_service_unreachable',
                'message' => 'O serviço central da VIA não respondeu.',
            ], 502);
        }

        if (! $response->successful() || ! is_array($payload)) {
            $rawError = is_array($payload) ? trim((string) ($payload['error'] ?? '')) : '';
            $publicMessage = 'O serviço central da VIA não conseguiu concluir esta solicitação agora.';

            if ($rawError !== '' && ! preg_match('/unauthenticated|unauthorized|invalid.*key|api[_ -]?key|token/i', $rawError)) {
                $publicMessage = mb_substr($rawError, 0, 500);
            }

            return response()->json([
                'ok' => false,
                'error' => 'via_service_failed',
                'message' => $publicMessage,
            ], 502);
        }

        $answer = (string) ($payload['answer'] ?? '');
        $this->persistAssistant($conversation, $answer, ['source' => 'via_service', 'model' => $payload['model'] ?? null]);

        return response()->json([
            'ok' => true,
            'answer' => $answer,
            'model' => $payload['model'] ?? null,
            'sessionId' => (string) $conversation->id,
            'persisted' => true,
        ]);
    }

    public function context(Request $request): JsonResponse
    {
        $data = $request->validate([
            'context' => ['required', 'array'],
        ]);

        $context = $this->enrichOperationalContext((array) $data['context']);
        $signals = (array) data_get($context, 'operational.signals', []);
        $rank = ['info' => 1, 'attention' => 2, 'alert' => 3, 'critical' => 4];
        $highest = 'normal';
        $highestRank = 0;

        foreach ($signals as $signal) {
            $severity = strtolower((string) ($signal['severity'] ?? 'info'));
            $severityRank = $rank[$severity] ?? 0;
            if ($severityRank > $highestRank) {
                $highest = $severity;
                $highestRank = $severityRank;
            }
        }

        return response()->json([
            'ok' => true,
            'context' => $context,
            'state' => $highest,
            'signal_count' => count($signals),
            'signals' => $signals,
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $sessionId = trim((string) $request->query('sessionId', ''));
        $query = ViaConversation::query()->where('user_id', $request->user()?->id);

        if ($sessionId !== '' && ctype_digit($sessionId)) {
            $query->whereKey((int) $sessionId);
        } else {
            $query->orderByDesc('last_activity_at')->orderByDesc('id');
        }

        $conversation = $query->first();

        if (! $conversation) {
            return response()->json([
                'ok' => true,
                'sessionId' => null,
                'messages' => [],
            ]);
        }

        $messages = ViaMessage::query()
            ->where('via_conversation_id', $conversation->id)
            ->orderBy('id')
            ->limit(50)
            ->get(['role', 'content', 'metadata', 'created_at']);

        return response()->json([
            'ok' => true,
            'sessionId' => (string) $conversation->id,
            'messages' => $messages,
        ]);
    }

    private function enrichOperationalContext(array $context): array
    {
        $resource = strtolower(trim((string) ($context['resource'] ?? '')));
        $contextLabel = strtolower(trim(implode(' ', array_filter([
            (string) ($context['module'] ?? ''),
            (string) ($context['heading'] ?? ''),
            (string) ($context['title'] ?? ''),
            (string) ($context['page'] ?? ''),
        ]))));

        if ($resource === '' || $resource === 'unknown') {
            if (str_contains($contextLabel, 'command center') || str_contains($contextLabel, 'dashboard') || str_contains($contextLabel, 'centro operacional')) {
                $resource = 'dashboard';
            }
        }

        $recordId = trim((string) ($context['record_id'] ?? ''));
        $operational = [
            'resource' => $resource ?: null,
            'record_id' => $recordId !== '' ? $recordId : null,
            'summary' => null,
            'signals' => [],
        ];

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['companies', 'company'], true)) {
            $company = Company::query()
                ->withCount(['licenses', 'payments', 'contracts', 'companyModules'])
                ->find((int) $recordId);

            if ($company) {
                $activeLicenses = $company->licenses()->where('status', 'Ativa')->count();
                $attentionLicenses = $company->licenses()
                    ->whereIn('status', ['Trial', 'Homologação', 'Suspensa'])
                    ->count();

                $operational['entity'] = [
                    'type' => 'company',
                    'id' => $company->id,
                    'name' => $company->nome,
                    'status' => $company->status,
                    'deployment_status' => $company->status_implantacao,
                    'environment' => $company->ambiente,
                    'instance_type' => $company->tipo_instancia,
                    'primary_product' => $company->produto_principal,
                    'primary_domain' => $company->dominio_principal,
                ];
                $operational['counts'] = [
                    'licenses' => $company->licenses_count,
                    'active_licenses' => $activeLicenses,
                    'licenses_requiring_attention' => $attentionLicenses,
                    'payments' => $company->payments_count,
                    'contracts' => $company->contracts_count,
                    'modules' => $company->company_modules_count,
                ];
                $operational['summary'] = sprintf(
                    'Cliente %s; status %s; implantação %s; %d licença(s), %d ativa(s), %d exigindo atenção.',
                    (string) $company->nome,
                    (string) $company->status,
                    (string) $company->status_implantacao,
                    (int) $company->licenses_count,
                    $activeLicenses,
                    $attentionLicenses,
                );

                if ($company->status === 'Suspenso' || $company->status_implantacao === 'Suspenso') {
                    $operational['signals'][] = ['severity' => 'alert', 'code' => 'company_suspended', 'message' => 'Cliente ou implantação está suspenso.'];
                }
                if ($company->status === 'Ativo' && blank($company->dominio_principal)) {
                    $operational['signals'][] = ['severity' => 'attention', 'code' => 'active_without_domain', 'message' => 'Cliente ativo sem domínio principal informado.'];
                }
                if ($attentionLicenses > 0) {
                    $operational['signals'][] = ['severity' => 'attention', 'code' => 'licenses_attention', 'message' => $attentionLicenses . ' licença(s) exigem atenção operacional.'];
                }
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['licenses', 'license'], true)) {
            $license = License::query()->with(['company:id,nome,status', 'product:id,nome', 'plan:id,nome'])->find((int) $recordId);

            if ($license) {
                $daysToExpiry = $license->vencimento ? now()->startOfDay()->diffInDays($license->vencimento->startOfDay(), false) : null;
                $operational['entity'] = [
                    'type' => 'license',
                    'id' => $license->id,
                    'company' => $license->company?->nome,
                    'product' => $license->product?->nome,
                    'plan' => $license->plan?->nome ?: $license->plano,
                    'status' => $license->status,
                    'start_date' => $license->inicio?->toDateString(),
                    'expiry_date' => $license->vencimento?->toDateString(),
                    'days_to_expiry' => $daysToExpiry,
                    'value' => $license->valor,
                ];
                $operational['summary'] = sprintf(
                    'Licença de %s para %s; plano %s; status %s; vencimento %s.',
                    (string) ($license->product?->nome ?? 'produto não identificado'),
                    (string) ($license->company?->nome ?? 'cliente não identificado'),
                    (string) ($license->plan?->nome ?? $license->plano ?? 'não informado'),
                    (string) $license->status,
                    (string) ($license->vencimento?->format('d/m/Y') ?? 'não informado'),
                );

                if (in_array($license->status, ['Suspensa', 'Cancelada'], true)) {
                    $operational['signals'][] = ['severity' => 'alert', 'code' => 'license_inactive', 'message' => 'Licença está ' . strtolower((string) $license->status) . '.'];
                }
                if ($daysToExpiry !== null && $daysToExpiry < 0) {
                    $operational['signals'][] = ['severity' => 'critical', 'code' => 'license_expired', 'message' => 'Licença vencida há ' . abs($daysToExpiry) . ' dia(s).'];
                } elseif ($daysToExpiry !== null && $daysToExpiry <= 15) {
                    $operational['signals'][] = ['severity' => 'attention', 'code' => 'license_expiring', 'message' => 'Licença vence em ' . $daysToExpiry . ' dia(s).'];
                }
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['products', 'product'], true)) {
            $product = Product::query()->withCount(['licenses', 'plans', 'modules', 'contracts'])->find((int) $recordId);
            if ($product) {
                $activeLicenses = $product->licenses()->where('status', 'Ativa')->count();
                $operational['entity'] = ['type' => 'product', 'id' => $product->id, 'name' => $product->nome, 'category' => $product->categoria, 'status' => $product->status];
                $operational['counts'] = ['licenses' => $product->licenses_count, 'active_licenses' => $activeLicenses, 'plans' => $product->plans_count, 'modules' => $product->modules_count, 'contracts' => $product->contracts_count];
                $operational['summary'] = sprintf('Produto %s; categoria %s; status %s; %d licença(s), %d ativa(s), %d plano(s).', (string) $product->nome, (string) $product->categoria, (string) $product->status, (int) $product->licenses_count, $activeLicenses, (int) $product->plans_count);
                if ($product->status !== 'Ativo') $operational['signals'][] = ['severity' => 'attention', 'code' => 'product_inactive', 'message' => 'Produto está inativo.'];
                if ($product->status === 'Ativo' && (int) $product->plans_count === 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'product_without_plan', 'message' => 'Produto ativo sem plano cadastrado.'];
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['plans', 'plan'], true)) {
            $plan = Plan::query()->with('product:id,nome,status')->withCount(['licenses', 'contracts'])->find((int) $recordId);
            if ($plan) {
                $operational['entity'] = ['type' => 'plan', 'id' => $plan->id, 'name' => $plan->nome, 'product' => $plan->product?->nome, 'status' => $plan->status, 'billing_cycle' => $plan->ciclo_cobranca, 'monthly_value' => $plan->valor_mensal, 'setup_value' => $plan->valor_implantacao];
                $operational['counts'] = ['licenses' => $plan->licenses_count, 'contracts' => $plan->contracts_count];
                $operational['summary'] = sprintf('Plano %s do produto %s; status %s; ciclo %s; %d licença(s).', (string) $plan->nome, (string) ($plan->product?->nome ?? 'não identificado'), (string) $plan->status, (string) $plan->ciclo_cobranca, (int) $plan->licenses_count);
                if ($plan->status !== 'Ativo') $operational['signals'][] = ['severity' => 'attention', 'code' => 'plan_inactive', 'message' => 'Plano está inativo.'];
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['payments', 'payment'], true)) {
            $payment = Payment::query()->with(['company:id,nome,status', 'product:id,nome', 'plan:id,nome', 'contract:id,numero,titulo'])->find((int) $recordId);
            if ($payment) {
                $daysToDue = $payment->vencimento ? now()->startOfDay()->diffInDays($payment->vencimento->startOfDay(), false) : null;
                $operational['entity'] = ['type' => 'payment', 'id' => $payment->id, 'company' => $payment->company?->nome, 'product' => $payment->product?->nome, 'plan' => $payment->plan?->nome, 'contract' => $payment->contract?->numero ?: $payment->contract?->titulo, 'billing_type' => $payment->tipo_cobranca, 'description' => $payment->descricao, 'value' => $payment->valor, 'status' => $payment->status, 'due_date' => $payment->vencimento?->toDateString(), 'days_to_due' => $daysToDue, 'paid_at' => $payment->data_pagamento?->toDateString()];
                $operational['summary'] = sprintf('Cobrança de %s para %s; status %s; vencimento %s.', (string) $payment->valor, (string) ($payment->company?->nome ?? 'cliente não identificado'), (string) $payment->status, (string) ($payment->vencimento?->format('d/m/Y') ?? 'não informado'));
                if ($payment->status === 'Atrasado' || ($daysToDue !== null && $daysToDue < 0 && ! in_array($payment->status, ['Pago', 'Cancelado'], true))) $operational['signals'][] = ['severity' => 'critical', 'code' => 'payment_overdue', 'message' => 'Cobrança vencida e ainda não liquidada.'];
                elseif ($payment->status === 'Aberto' && $daysToDue !== null && $daysToDue <= 7) $operational['signals'][] = ['severity' => 'attention', 'code' => 'payment_due_soon', 'message' => 'Cobrança vence em ' . $daysToDue . ' dia(s).'];
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['contracts', 'contract'], true)) {
            $contract = Contract::query()->with(['company:id,nome,status', 'product:id,nome', 'plan:id,nome'])->withCount('payments')->find((int) $recordId);
            if ($contract) {
                $daysToEnd = $contract->data_fim ? now()->startOfDay()->diffInDays($contract->data_fim->startOfDay(), false) : null;
                $operational['entity'] = ['type' => 'contract', 'id' => $contract->id, 'number' => $contract->numero, 'title' => $contract->titulo, 'company' => $contract->company?->nome, 'product' => $contract->product?->nome, 'plan' => $contract->plan?->nome, 'status' => $contract->status, 'start_date' => $contract->data_inicio?->toDateString(), 'end_date' => $contract->data_fim?->toDateString(), 'days_to_end' => $daysToEnd, 'monthly_total' => $contract->valor_total_mensal];
                $operational['counts'] = ['payments' => $contract->payments_count];
                $operational['summary'] = sprintf('Contrato %s de %s; status %s; término %s.', (string) ($contract->numero ?: $contract->titulo ?: '#' . $contract->id), (string) ($contract->company?->nome ?? 'cliente não identificado'), (string) $contract->status, (string) ($contract->data_fim?->format('d/m/Y') ?? 'não informado'));
                if (in_array($contract->status, ['Suspenso', 'Cancelado'], true)) $operational['signals'][] = ['severity' => 'alert', 'code' => 'contract_inactive', 'message' => 'Contrato está ' . strtolower((string) $contract->status) . '.'];
                if ($daysToEnd !== null && $daysToEnd >= 0 && $daysToEnd <= 30) $operational['signals'][] = ['severity' => 'attention', 'code' => 'contract_ending', 'message' => 'Contrato termina em ' . $daysToEnd . ' dia(s).'];
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['subscriptions', 'subscription'], true)) {
            $subscription = Subscription::query()->with(['company:id,nome,status', 'plan:id,nome', 'license:id,status'])->find((int) $recordId);
            if ($subscription) {
                $daysToDue = $subscription->next_due_date ? now()->startOfDay()->diffInDays($subscription->next_due_date->startOfDay(), false) : null;
                $operational['entity'] = ['type' => 'subscription', 'id' => $subscription->id, 'company' => $subscription->company?->nome, 'plan' => $subscription->plan?->nome, 'license_status' => $subscription->license?->status, 'status' => $subscription->status, 'billing_cycle' => $subscription->billing_cycle, 'value' => $subscription->value, 'next_due_date' => $subscription->next_due_date?->toDateString(), 'days_to_due' => $daysToDue];
                $operational['summary'] = sprintf('Assinatura de %s; plano %s; status %s; próximo vencimento %s.', (string) ($subscription->company?->nome ?? 'cliente não identificado'), (string) ($subscription->plan?->nome ?? 'não informado'), (string) $subscription->status, (string) ($subscription->next_due_date?->format('d/m/Y') ?? 'não informado'));
                if (in_array($subscription->status, ['Suspensa', 'Cancelada'], true)) $operational['signals'][] = ['severity' => 'alert', 'code' => 'subscription_inactive', 'message' => 'Assinatura está ' . strtolower((string) $subscription->status) . '.'];
                if ($subscription->status === 'Ativa' && $daysToDue !== null && $daysToDue < 0) $operational['signals'][] = ['severity' => 'critical', 'code' => 'subscription_due_overdue', 'message' => 'Assinatura ativa possui próximo vencimento já ultrapassado.'];
            }
        }

        if ($recordId !== '' && ctype_digit($recordId) && in_array($resource, ['leads', 'lead'], true)) {
            $lead = Lead::query()->find((int) $recordId);
            if ($lead) {
                $daysToAction = $lead->data_proxima_acao ? now()->startOfDay()->diffInDays($lead->data_proxima_acao->startOfDay(), false) : null;
                $operational['entity'] = ['type' => 'lead', 'id' => $lead->id, 'company' => $lead->empresa, 'contact' => $lead->contato, 'product_interest' => $lead->produto_interesse, 'suggested_plan' => $lead->plano_sugerido, 'estimated_value' => $lead->valor_estimado, 'status' => $lead->status_negociacao, 'owner' => $lead->responsavel_comercial, 'next_action' => $lead->proxima_acao, 'next_action_date' => $lead->data_proxima_acao?->toDateString(), 'days_to_next_action' => $daysToAction, 'source' => $lead->origem_lead];
                $operational['summary'] = sprintf('Lead %s; interesse %s; negociação %s; próxima ação %s em %s.', (string) ($lead->empresa ?: $lead->contato ?: '#' . $lead->id), (string) ($lead->produto_interesse ?? 'não informado'), (string) ($lead->status_negociacao ?? 'não informado'), (string) ($lead->proxima_acao ?? 'não definida'), (string) ($lead->data_proxima_acao?->format('d/m/Y') ?? 'não definida'));
                if (! in_array($lead->status_negociacao, ['Fechado', 'Perdido'], true) && blank($lead->proxima_acao)) $operational['signals'][] = ['severity' => 'attention', 'code' => 'lead_without_next_action', 'message' => 'Oportunidade aberta sem próxima ação definida.'];
                if (! in_array($lead->status_negociacao, ['Fechado', 'Perdido'], true) && $daysToAction !== null && $daysToAction < 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'lead_followup_overdue', 'message' => 'Follow-up comercial está atrasado há ' . abs($daysToAction) . ' dia(s).'];
            }
        }

        if ($recordId === '') {
            if (in_array($resource, ['dashboard'], true)) {
                $companies = Company::query()->count();
                $products = Product::query()->count();
                $plans = Plan::query()->count();
                $licenses = License::query()->count();
                $payments = Payment::query()->count();
                $leads = Lead::query()->count();

                $expiredLicenses = License::query()->whereNotNull('vencimento')->whereDate('vencimento', '<', today())->count();
                $expiringSoon = License::query()->whereNotNull('vencimento')->whereBetween('vencimento', [today(), today()->copy()->addDays(15)])->count();
                $overduePayments = Payment::query()->where(function ($query) {
                    $query->where('status', 'Atrasado')
                        ->orWhere(function ($nested) {
                            $nested->whereDate('vencimento', '<', today())->whereNotIn('status', ['Pago', 'Cancelado']);
                        });
                })->count();
                $overdueFollowups = Lead::query()->whereNotIn('status_negociacao', ['Fechado', 'Perdido'])
                    ->whereNotNull('data_proxima_acao')
                    ->whereDate('data_proxima_acao', '<', today())
                    ->count();
                $suspendedCompanies = Company::query()->where('status', 'Suspenso')->count();
                $productsWithoutPlans = Product::query()->where('status', 'Ativo')->doesntHave('plans')->count();

                $operational['counts'] = compact(
                    'companies', 'products', 'plans', 'licenses', 'payments', 'leads',
                    'expiredLicenses', 'expiringSoon', 'overduePayments', 'overdueFollowups',
                    'suspendedCompanies', 'productsWithoutPlans'
                );
                $operational['summary'] = sprintf(
                    'Dashboard com %d cliente(s), %d produto(s), %d plano(s), %d licença(s), %d cobrança(s) e %d lead(s). Indicadores monitorados: %d licença(s) vencida(s), %d vencendo em até 15 dias, %d cobrança(s) vencida(s)/atrasada(s), %d follow-up(s) comercial(is) atrasado(s), %d cliente(s) suspenso(s) e %d produto(s) ativo(s) sem plano.',
                    $companies,
                    $products,
                    $plans,
                    $licenses,
                    $payments,
                    $leads,
                    $expiredLicenses,
                    $expiringSoon,
                    $overduePayments,
                    $overdueFollowups,
                    $suspendedCompanies,
                    $productsWithoutPlans,
                );

                if ($expiredLicenses > 0) $operational['signals'][] = ['severity' => 'critical', 'code' => 'dashboard_licenses_expired', 'message' => $expiredLicenses . ' licença(s) estão vencidas.'];
                if ($overduePayments > 0) $operational['signals'][] = ['severity' => 'critical', 'code' => 'dashboard_payments_overdue', 'message' => $overduePayments . ' cobrança(s) estão vencidas ou atrasadas.'];
                if ($overdueFollowups > 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'dashboard_followups_overdue', 'message' => $overdueFollowups . ' follow-up(s) comercial(is) estão atrasados.'];
                if ($suspendedCompanies > 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'dashboard_companies_suspended', 'message' => $suspendedCompanies . ' cliente(s) estão suspensos.'];
                if ($expiringSoon > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'dashboard_licenses_expiring', 'message' => $expiringSoon . ' licença(s) vencem nos próximos 15 dias.'];
                if ($productsWithoutPlans > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'dashboard_products_without_plan', 'message' => $productsWithoutPlans . ' produto(s) ativo(s) não possuem plano cadastrado.'];
            } elseif (in_array($resource, ['licenses', 'license'], true)) {
                $total = License::query()->count();
                $active = License::query()->where('status', 'Ativa')->count();
                $attention = License::query()->whereIn('status', ['Trial', 'Homologação', 'Suspensa'])->count();
                $expired = License::query()->whereNotNull('vencimento')->whereDate('vencimento', '<', today())->count();
                $expiringSoon = License::query()->whereNotNull('vencimento')->whereBetween('vencimento', [today(), today()->copy()->addDays(15)])->count();

                $operational['counts'] = compact('total', 'active', 'attention', 'expired', 'expiringSoon');
                $operational['summary'] = sprintf(
                    'A listagem contém %d licença(s): %d ativa(s), %d em status de atenção, %d vencida(s) e %d com vencimento nos próximos 15 dias.',
                    $total,
                    $active,
                    $attention,
                    $expired,
                    $expiringSoon,
                );

                if ($expired > 0) {
                    $operational['signals'][] = ['severity' => 'critical', 'code' => 'licenses_expired_collection', 'message' => $expired . ' licença(s) estão vencidas.'];
                }
                if ($expiringSoon > 0) {
                    $operational['signals'][] = ['severity' => 'attention', 'code' => 'licenses_expiring_collection', 'message' => $expiringSoon . ' licença(s) vencem nos próximos 15 dias.'];
                }
                if ($attention > 0) {
                    $operational['signals'][] = ['severity' => 'attention', 'code' => 'licenses_status_attention_collection', 'message' => $attention . ' licença(s) estão em Trial, Homologação ou Suspensa.'];
                }

                $operational['items'] = License::query()
                    ->with(['company:id,nome', 'product:id,nome'])
                    ->whereNotNull('vencimento')
                    ->whereDate('vencimento', '<=', today()->copy()->addDays(15))
                    ->orderBy('vencimento')
                    ->limit(5)
                    ->get()
                    ->map(fn (License $license): array => [
                        'type' => 'license',
                        'id' => $license->id,
                        'label' => trim((string) ($license->company?->nome ?? 'Cliente não identificado') . ' · ' . (string) ($license->product?->nome ?? 'Produto não identificado')),
                        'status' => $license->status,
                        'date' => $license->vencimento?->toDateString(),
                        'days' => $license->vencimento ? now()->startOfDay()->diffInDays($license->vencimento->startOfDay(), false) : null,
                    ])->all();
            } elseif (in_array($resource, ['companies', 'company'], true)) {
                $total = Company::query()->count();
                $active = Company::query()->where('status', 'Ativo')->count();
                $suspended = Company::query()->where('status', 'Suspenso')->count();
                $withoutDomain = Company::query()->where('status', 'Ativo')->where(function ($query) {
                    $query->whereNull('dominio_principal')->orWhere('dominio_principal', '');
                })->count();

                $operational['counts'] = compact('total', 'active', 'suspended', 'withoutDomain');
                $operational['summary'] = sprintf('A listagem contém %d cliente(s): %d ativo(s), %d suspenso(s) e %d ativo(s) sem domínio principal.', $total, $active, $suspended, $withoutDomain);
                if ($suspended > 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'companies_suspended_collection', 'message' => $suspended . ' cliente(s) estão suspensos.'];
                if ($withoutDomain > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'companies_without_domain_collection', 'message' => $withoutDomain . ' cliente(s) ativos não possuem domínio principal informado.'];

                $operational['items'] = Company::query()
                    ->where(function ($query) {
                        $query->where('status', 'Suspenso')
                            ->orWhere(function ($nested) {
                                $nested->where('status', 'Ativo')
                                    ->where(function ($domain) {
                                        $domain->whereNull('dominio_principal')->orWhere('dominio_principal', '');
                                    });
                            });
                    })
                    ->orderByRaw("CASE WHEN status = 'Suspenso' THEN 0 ELSE 1 END")
                    ->orderBy('nome')
                    ->limit(5)
                    ->get()
                    ->map(fn (Company $company): array => [
                        'type' => 'company',
                        'id' => $company->id,
                        'label' => (string) $company->nome,
                        'status' => $company->status,
                        'deployment_status' => $company->status_implantacao,
                        'domain' => $company->dominio_principal,
                    ])->all();
            } elseif (in_array($resource, ['products', 'product'], true)) {
                $total = Product::query()->count();
                $active = Product::query()->where('status', 'Ativo')->count();
                $inactive = $total - $active;
                $withoutPlans = Product::query()->where('status', 'Ativo')->doesntHave('plans')->count();

                $operational['counts'] = compact('total', 'active', 'inactive', 'withoutPlans');
                $operational['summary'] = sprintf('A listagem contém %d produto(s): %d ativo(s), %d inativo(s) e %d ativo(s) sem plano cadastrado.', $total, $active, $inactive, $withoutPlans);
                if ($withoutPlans > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'products_without_plan_collection', 'message' => $withoutPlans . ' produto(s) ativos não possuem plano cadastrado.'];

                $operational['items'] = Product::query()
                    ->where(function ($query) {
                        $query->where('status', '!=', 'Ativo')
                            ->orWhere(function ($nested) {
                                $nested->where('status', 'Ativo')->doesntHave('plans');
                            });
                    })
                    ->orderByRaw("CASE WHEN status != 'Ativo' THEN 0 ELSE 1 END")
                    ->orderBy('nome')
                    ->limit(5)
                    ->get()
                    ->map(fn (Product $product): array => [
                        'type' => 'product',
                        'id' => $product->id,
                        'label' => (string) $product->nome,
                        'status' => $product->status,
                        'category' => $product->categoria,
                        'plans' => $product->plans()->count(),
                    ])->all();
            } elseif (in_array($resource, ['plans', 'plan'], true)) {
                $total = Plan::query()->count();
                $active = Plan::query()->where('status', 'Ativo')->count();
                $inactive = $total - $active;

                $operational['counts'] = compact('total', 'active', 'inactive');
                $operational['summary'] = sprintf('A listagem contém %d plano(s): %d ativo(s) e %d inativo(s).', $total, $active, $inactive);
                if ($inactive > 0) $operational['signals'][] = ['severity' => 'info', 'code' => 'plans_inactive_collection', 'message' => $inactive . ' plano(s) estão inativos.'];
            } elseif (in_array($resource, ['payments', 'payment'], true)) {
                $total = Payment::query()->count();
                $overdue = Payment::query()->where(function ($query) {
                    $query->where('status', 'Atrasado')
                        ->orWhere(function ($nested) {
                            $nested->whereDate('vencimento', '<', today())->whereNotIn('status', ['Pago', 'Cancelado']);
                        });
                })->count();
                $dueSoon = Payment::query()->where('status', 'Aberto')->whereBetween('vencimento', [today(), today()->copy()->addDays(7)])->count();
                $paid = Payment::query()->where('status', 'Pago')->count();

                $operational['counts'] = compact('total', 'overdue', 'dueSoon', 'paid');
                $operational['summary'] = sprintf('A listagem contém %d cobrança(s): %d paga(s), %d vencida(s)/atrasada(s) e %d com vencimento nos próximos 7 dias.', $total, $paid, $overdue, $dueSoon);
                if ($overdue > 0) $operational['signals'][] = ['severity' => 'critical', 'code' => 'payments_overdue_collection', 'message' => $overdue . ' cobrança(s) estão vencidas ou atrasadas.'];
                if ($dueSoon > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'payments_due_soon_collection', 'message' => $dueSoon . ' cobrança(s) vencem nos próximos 7 dias.'];

                $operational['items'] = Payment::query()
                    ->with('company:id,nome')
                    ->whereNotNull('vencimento')
                    ->whereNotIn('status', ['Pago', 'Cancelado'])
                    ->whereDate('vencimento', '<=', today()->copy()->addDays(7))
                    ->orderBy('vencimento')
                    ->limit(5)
                    ->get()
                    ->map(fn (Payment $payment): array => [
                        'type' => 'payment',
                        'id' => $payment->id,
                        'label' => (string) ($payment->company?->nome ?? 'Cliente não identificado'),
                        'status' => $payment->status,
                        'date' => $payment->vencimento?->toDateString(),
                        'days' => $payment->vencimento ? now()->startOfDay()->diffInDays($payment->vencimento->startOfDay(), false) : null,
                        'value' => $payment->valor,
                    ])->all();
            } elseif (in_array($resource, ['contracts', 'contract'], true)) {
                $total = Contract::query()->count();
                $inactive = Contract::query()->whereIn('status', ['Suspenso', 'Cancelado'])->count();
                $endingSoon = Contract::query()->whereNotNull('data_fim')->whereBetween('data_fim', [today(), today()->copy()->addDays(30)])->count();

                $operational['counts'] = compact('total', 'inactive', 'endingSoon');
                $operational['summary'] = sprintf('A listagem contém %d contrato(s): %d suspenso(s)/cancelado(s) e %d com término nos próximos 30 dias.', $total, $inactive, $endingSoon);
                if ($inactive > 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'contracts_inactive_collection', 'message' => $inactive . ' contrato(s) estão suspensos ou cancelados.'];
                if ($endingSoon > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'contracts_ending_collection', 'message' => $endingSoon . ' contrato(s) terminam nos próximos 30 dias.'];

                $operational['items'] = Contract::query()
                    ->with('company:id,nome')
                    ->whereNotNull('data_fim')
                    ->whereBetween('data_fim', [today(), today()->copy()->addDays(30)])
                    ->orderBy('data_fim')
                    ->limit(5)
                    ->get()
                    ->map(fn (Contract $contract): array => [
                        'type' => 'contract',
                        'id' => $contract->id,
                        'label' => (string) ($contract->numero ?: $contract->titulo ?: '#' . $contract->id),
                        'company' => $contract->company?->nome,
                        'status' => $contract->status,
                        'date' => $contract->data_fim?->toDateString(),
                        'days' => $contract->data_fim ? now()->startOfDay()->diffInDays($contract->data_fim->startOfDay(), false) : null,
                    ])->all();
            } elseif (in_array($resource, ['subscriptions', 'subscription'], true)) {
                $total = Subscription::query()->count();
                $inactive = Subscription::query()->whereIn('status', ['Suspensa', 'Cancelada'])->count();
                $overdue = Subscription::query()->where('status', 'Ativa')->whereNotNull('next_due_date')->whereDate('next_due_date', '<', today())->count();

                $operational['counts'] = compact('total', 'inactive', 'overdue');
                $operational['summary'] = sprintf('A listagem contém %d assinatura(s): %d suspensa(s)/cancelada(s) e %d ativa(s) com próximo vencimento já ultrapassado.', $total, $inactive, $overdue);
                if ($overdue > 0) $operational['signals'][] = ['severity' => 'critical', 'code' => 'subscriptions_overdue_collection', 'message' => $overdue . ' assinatura(s) ativas possuem próximo vencimento já ultrapassado.'];
                if ($inactive > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'subscriptions_inactive_collection', 'message' => $inactive . ' assinatura(s) estão suspensas ou canceladas.'];

                $operational['items'] = Subscription::query()
                    ->with(['company:id,nome', 'plan:id,nome'])
                    ->where(function ($query) {
                        $query->whereIn('status', ['Suspensa', 'Cancelada'])
                            ->orWhere(function ($nested) {
                                $nested->where('status', 'Ativa')
                                    ->whereNotNull('next_due_date')
                                    ->whereDate('next_due_date', '<', today());
                            });
                    })
                    ->orderByRaw("CASE WHEN status = 'Ativa' AND next_due_date < CURRENT_DATE THEN 0 ELSE 1 END")
                    ->orderBy('next_due_date')
                    ->limit(5)
                    ->get()
                    ->map(fn (Subscription $subscription): array => [
                        'type' => 'subscription',
                        'id' => $subscription->id,
                        'label' => (string) ($subscription->company?->nome ?? 'Cliente não identificado'),
                        'plan' => $subscription->plan?->nome,
                        'status' => $subscription->status,
                        'date' => $subscription->next_due_date?->toDateString(),
                        'days' => $subscription->next_due_date ? now()->startOfDay()->diffInDays($subscription->next_due_date->startOfDay(), false) : null,
                    ])->all();
            } elseif (in_array($resource, ['leads', 'lead'], true)) {
                $total = Lead::query()->count();
                $open = Lead::query()->whereNotIn('status_negociacao', ['Fechado', 'Perdido'])->count();
                $withoutNextAction = Lead::query()->whereNotIn('status_negociacao', ['Fechado', 'Perdido'])->where(function ($query) {
                    $query->whereNull('proxima_acao')->orWhere('proxima_acao', '');
                })->count();
                $overdueFollowup = Lead::query()->whereNotIn('status_negociacao', ['Fechado', 'Perdido'])->whereNotNull('data_proxima_acao')->whereDate('data_proxima_acao', '<', today())->count();

                $operational['counts'] = compact('total', 'open', 'withoutNextAction', 'overdueFollowup');
                $operational['summary'] = sprintf('A listagem contém %d lead(s): %d oportunidade(s) aberta(s), %d sem próxima ação e %d com follow-up atrasado.', $total, $open, $withoutNextAction, $overdueFollowup);
                if ($overdueFollowup > 0) $operational['signals'][] = ['severity' => 'alert', 'code' => 'leads_followup_overdue_collection', 'message' => $overdueFollowup . ' oportunidade(s) possuem follow-up atrasado.'];
                if ($withoutNextAction > 0) $operational['signals'][] = ['severity' => 'attention', 'code' => 'leads_without_next_action_collection', 'message' => $withoutNextAction . ' oportunidade(s) abertas não possuem próxima ação definida.'];

                $operational['items'] = Lead::query()
                    ->whereNotIn('status_negociacao', ['Fechado', 'Perdido'])
                    ->orderByRaw('CASE WHEN data_proxima_acao IS NULL THEN 0 ELSE 1 END')
                    ->orderBy('data_proxima_acao')
                    ->limit(5)
                    ->get()
                    ->map(fn (Lead $lead): array => [
                        'type' => 'lead',
                        'id' => $lead->id,
                        'label' => (string) ($lead->empresa ?: $lead->contato ?: '#' . $lead->id),
                        'status' => $lead->status_negociacao,
                        'next_action' => $lead->proxima_acao,
                        'date' => $lead->data_proxima_acao?->toDateString(),
                        'days' => $lead->data_proxima_acao ? now()->startOfDay()->diffInDays($lead->data_proxima_acao->startOfDay(), false) : null,
                    ])->all();
            }
        }

        $context['operational'] = $operational;

        return $context;
    }

    private function resolveConversation(Request $request, array $data, array $context, string $message): ViaConversation
    {
        $sessionId = trim((string) ($data['sessionId'] ?? ''));
        $userId = $request->user()?->id;

        if ($sessionId !== '' && ctype_digit($sessionId)) {
            $existing = ViaConversation::query()
                ->whereKey((int) $sessionId)
                ->where('user_id', $userId)
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'domain' => (string) ($context['domain'] ?? $context['module'] ?? $existing->domain),
                    'target_project_id' => (string) ($context['project'] ?? $existing->target_project_id),
                    'last_activity_at' => now(),
                ])->save();

                return $existing;
            }
        }

        return ViaConversation::query()->create([
            'user_id' => $userId,
            'title' => mb_substr($message !== '' ? $message : 'Nova conversa VIA', 0, 160),
            'domain' => (string) ($context['domain'] ?? $context['module'] ?? 'core'),
            'target_project_id' => (string) ($context['project'] ?? 'vitrine-ia-pro-core'),
            'mode' => 'OBSERVER',
            'last_activity_at' => now(),
        ]);
    }

    private function persistAssistant(ViaConversation $conversation, string $answer, array $metadata = []): void
    {
        $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $answer,
            'metadata' => $metadata,
        ]);

        $conversation->forceFill(['last_activity_at' => now()])->save();
    }

    public function transcribe(Request $request): JsonResponse
    {
        $data = $request->validate([
            'audio' => ['required', 'file', 'max:10240'],
        ]);

        $audio = $data['audio'];
        $mime = strtolower((string) ($audio->getMimeType() ?: $audio->getClientMimeType() ?: 'audio/webm'));
        $allowed = [
            'audio/webm', 'video/webm', 'audio/ogg', 'audio/mp4', 'audio/mpeg',
            'audio/wav', 'audio/x-wav', 'audio/aac', 'application/octet-stream',
        ];

        if (! in_array($mime, $allowed, true)) {
            return response()->json(['ok' => false, 'error' => 'unsupported_audio_type', 'mime' => $mime], 422);
        }

        $bytes = file_get_contents($audio->getRealPath());
        if ($bytes === false || $bytes === '') {
            return response()->json(['ok' => false, 'error' => 'empty_audio'], 422);
        }

        $baseUrl = rtrim((string) env('VIA_SERVICE_URL', 'http://via_hml_v04:3000'), '/');

        try {
            $response = Http::timeout(90)
                ->withHeaders(['Content-Type' => $mime, 'Accept' => 'application/json'])
                ->withBody($bytes, $mime)
                ->post($baseUrl . '/api/transcribe');
            $payload = $response->json();
        } catch (\Throwable $e) {
            report($e);
            return response()->json([
                'ok' => false,
                'error' => 'via_transcription_service_unreachable',
                'message' => 'O serviço de voz da VIA não respondeu.',
            ], 502);
        }

        if (! $response->successful() || ! is_array($payload) || empty($payload['ok'])) {
            return response()->json([
                'ok' => false,
                'error' => 'via_transcription_failed',
                'message' => is_array($payload) ? ($payload['error'] ?? 'Falha na transcrição da VIA.') : 'Falha na transcrição da VIA.',
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'text' => mb_substr(trim((string) ($payload['text'] ?? '')), 0, 4000),
        ]);
    }
}
