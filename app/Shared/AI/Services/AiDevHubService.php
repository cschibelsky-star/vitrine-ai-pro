<?php

declare(strict_types=1);

namespace App\Shared\AI\Services;

use App\Shared\AI\Models\AiConsumption;
use App\Shared\AI\Models\AiModel;
use RuntimeException;

class AiDevHubService
{
    public function __construct(
        private readonly AiHubService $hub,
        private readonly AiModelRouter $router,
        private readonly AiUsageBudgetService $budget,
    ) {
    }

    public function chat(array $request): array
    {
        $this->assertEnabled();
        $projectId = (string) ($request['project_id'] ?? '');
        $this->assertProjectAllowed($projectId);
        $this->assertGlobalMonthlyBudget();
        $this->budget->assertProjectWithinBudget($projectId);

        $provider = (string) ($request['provider'] ?? config('ai_dev_hub.default_provider', 'roteia'));
        $model = (string) ($request['model'] ?? $this->router->resolve(
            $provider,
            (string) ($request['profile'] ?? 'balanced'),
            'text',
        )->provider_model_id);

        return $this->hub->generate([
            'provider' => $provider,
            'model' => $model,
            'resource_type' => 'internal_development.chat',
            'system' => (string) ($request['system'] ?? 'Você atua como consultor técnico auxiliar da equipe Vitrine IA Pro.'),
            'prompt' => (string) ($request['prompt'] ?? ''),
            'options' => (array) ($request['options'] ?? []),
            'audit_metadata' => $this->sanitizeAuditMetadata((array) ($request['audit_metadata'] ?? [])),
        ] + $this->internalScope($request));
    }

    public function compare(array $request): array
    {
        $this->assertEnabled();
        $projectId = (string) ($request['project_id'] ?? '');
        $this->assertProjectAllowed($projectId);

        $provider = (string) ($request['provider'] ?? config('ai_dev_hub.default_provider', 'roteia'));
        $models = array_values(array_filter((array) ($request['models'] ?? [])));

        if ($models === []) {
            $models = $this->verifiedTextModelsQuery($provider)
                ->orderByRaw("FIELD(tier, 'economy', 'balanced', 'premium')")
                ->orderBy('input_cost_per_million')
                ->limit((int) config('ai_dev_hub.max_compare_models', 3))
                ->pluck('provider_model_id')
                ->all();
        }

        $models = array_slice($models, 0, (int) config('ai_dev_hub.max_compare_models', 3));
        $results = [];

        foreach ($models as $model) {
            try {
                $this->assertGlobalMonthlyBudget();
                $this->budget->assertProjectWithinBudget($projectId);

                $results[] = [
                    'ok' => true,
                    'model' => $model,
                    'result' => $this->hub->generate([
                        'provider' => $provider,
                        'model' => (string) $model,
                        'resource_type' => 'internal_development.compare',
                        'system' => (string) ($request['system'] ?? 'Você atua como consultor técnico independente da equipe Vitrine IA Pro. Analise com rigor e aponte riscos.'),
                        'prompt' => (string) ($request['prompt'] ?? ''),
                        'options' => (array) ($request['options'] ?? []),
                        'audit_metadata' => $this->sanitizeAuditMetadata((array) ($request['audit_metadata'] ?? [])),
                    ] + $this->internalScope($request)),
                ];
            } catch (\Throwable) {
                $results[] = [
                    'ok' => false,
                    'model' => $model,
                    'error' => 'model_execution_failed',
                ];
            }
        }

        return ['project_id' => $projectId, 'results' => $results];
    }

    public function codeReview(array $request): array
    {
        $system = <<<'PROMPT'
Você é um revisor sênior de código. Analise o trecho fornecido buscando: bugs, regressões, segurança, desempenho, compatibilidade, manutenibilidade e testes ausentes. Não reescreva tudo sem necessidade. Priorize achados por severidade e seja específico.
PROMPT;

        return $this->chat($request + [
            'system' => $system,
            'profile' => $request['profile'] ?? 'balanced',
        ]);
    }

    public function routePreview(array $request): array
    {
        $this->assertEnabled();
        $this->assertProjectAllowed((string) ($request['project_id'] ?? ''));

        $provider = (string) ($request['provider'] ?? config('ai_dev_hub.default_provider', 'roteia'));

        return $this->router->preview(
            $provider,
            (string) ($request['profile'] ?? 'balanced'),
            (string) ($request['modality'] ?? 'text'),
        );
    }

    public function listModels(array $filters = []): array
    {
        $query = AiModel::query()
            ->with('provider:id,name,slug')
            ->where('is_active', true)
            ->where('is_verified', true);

        if (! empty($filters['provider'])) {
            $query->whereHas('provider', fn ($q) => $q->where('slug', $filters['provider']));
        }
        if (! empty($filters['tier'])) {
            $query->where('tier', $filters['tier']);
        }
        if (! empty($filters['modality'])) {
            $query->where('modality', $filters['modality']);
        }

        return $query->orderBy('tier')->orderBy('name')->get()->map(fn (AiModel $model) => [
            'provider' => $model->provider?->slug,
            'model' => $model->provider_model_id,
            'name' => $model->name,
            'tier' => $model->tier,
            'modality' => $model->modality,
            'billing_unit' => $model->billing_unit,
            'verified' => (bool) $model->is_verified,
            'input_cost_per_million' => (float) $model->input_cost_per_million,
            'output_cost_per_million' => (float) $model->output_cost_per_million,
            'unit_cost_brl' => (float) $model->unit_cost_brl,
        ])->all();
    }

    public function usageSummary(?string $projectId = null): array
    {
        if ($projectId !== null && $projectId !== '') {
            $this->assertProjectAllowed($projectId);
        }

        $query = AiConsumption::query()
            ->where('resource_type', 'like', 'internal_development.%')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);

        if ($projectId !== null && $projectId !== '') {
            $query->where('project_id', $projectId);
        }

        return [
            'month' => now()->format('Y-m'),
            'project_id' => $projectId,
            'requests' => (clone $query)->count(),
            'provider_cost_brl' => round((float) (clone $query)->sum('provider_cost_brl'), 6),
            'billable_cost_brl' => round((float) (clone $query)->sum('billable_cost_brl'), 6),
            'ai_credits' => round((float) (clone $query)->sum('ai_credits'), 4),
            'total_tokens' => (int) (clone $query)->sum('total_tokens'),
            'budget' => $this->budget->summary($projectId),
        ];
    }

    private function verifiedTextModelsQuery(string $provider)
    {
        return AiModel::query()
            ->whereHas('provider', fn ($query) => $query->where('slug', $provider))
            ->where('modality', 'text')
            ->where('is_active', true)
            ->where('is_verified', true);
    }

    private function internalScope(array $request): array
    {
        return [
            'company_id' => $request['company_id'] ?? null,
            'product_id' => $request['product_id'] ?? null,
            'license_id' => $request['license_id'] ?? null,
            'ai_agent_id' => $request['ai_agent_id'] ?? null,
            'project_id' => $request['project_id'] ?? null,
        ];
    }

    private function sanitizeAuditMetadata(array $metadata): array
    {
        $allowed = [
            'mission_id',
            'evidence_pack_version',
            'evidence_pack_sha256',
            'runtime_version',
            'grounding_policy',
            'observer_mode',
            'domain',
            'target_project_id',
        ];

        return array_intersect_key($metadata, array_flip($allowed));
    }

    private function assertEnabled(): void
    {
        if (! config('ai_dev_hub.enabled')) {
            throw new RuntimeException('AI Dev Hub está desabilitado.');
        }
    }

    private function assertProjectAllowed(string $projectId): void
    {
        if ($projectId === '' || ! in_array($projectId, (array) config('ai_dev_hub.allowed_projects', []), true)) {
            throw new RuntimeException('Projeto não autorizado no AI Dev Hub.');
        }
    }

    private function assertGlobalMonthlyBudget(): void
    {
        $limit = (float) config('ai_dev_hub.monthly_limit_brl', 0);
        if ($limit <= 0) {
            return;
        }

        $spent = (float) AiConsumption::query()
            ->where('resource_type', 'like', 'internal_development.%')
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('provider_cost_brl');

        if ($spent >= $limit) {
            throw new RuntimeException('Limite mensal global do AI Dev Hub atingido.');
        }
    }
}
