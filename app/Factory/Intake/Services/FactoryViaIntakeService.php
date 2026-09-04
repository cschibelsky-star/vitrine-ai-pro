<?php

declare(strict_types=1);

namespace App\Factory\Intake\Services;

use App\Factory\Models\FactoryExecution;
use App\Factory\RealBuilder\Services\FinishProjectService;
use App\Factory\Models\FactoryProject;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

final class FactoryViaIntakeService
{
    public function __construct(
        private readonly FinishProjectService $finisher,
    ) {
    }

    public function prepare(string $request, ?int $userId = null): array
    {
        $request = trim($request);
        if (mb_strlen($request) < 10) {
            throw new RuntimeException('A solicitação precisa ter pelo menos 10 caracteres.');
        }

        $analysis = $this->analyzeWithCore($request);
        $discoveryGate = $this->evaluateDiscoveryGate($analysis, $request);
        $name = trim((string) ($analysis['name'] ?? '')) ?: Str::headline(Str::limit($request, 60, ''));
        $slugBase = Str::slug($name) ?: 'factory-request';
        $slug = $slugBase.'-'.Str::lower(Str::random(6));

        $project = FactoryProject::query()->create([
            'uuid' => (string) Str::uuid(),
            'name' => mb_substr($name, 0, 180),
            'slug' => $slug,
            'description' => $request,
            'status' => 'analysis_ready',
            'metadata' => [
                'contract' => 'factory.via.intake.v1',
                'source' => 'via',
                'request' => $request,
                'analysis_status' => 'ready',
                'approval_status' => 'pending',
                'profile_dna' => $analysis['profile_dna'] ?? [],
                'master_prompt' => $analysis['master_prompt'] ?? '',
                'analysis' => $analysis,
                'discovery_gate' => $discoveryGate,
                'prepared_at' => now()->toISOString(),
            ],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        FactoryExecution::query()->create([
            'uuid' => (string) Str::uuid(),
            'factory_project_id' => $project->id,
            'name' => 'VIA Intake Analysis',
            'status' => 'finished',
            'attempt' => 1,
            'input' => ['request' => $request],
            'output' => $analysis,
            'context' => ['source' => 'via', 'contract' => 'factory.via.intake.v1'],
            'started_at' => now(),
            'finished_at' => now(),
            'duration_ms' => 0,
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        return [
            'project' => [
                'id' => $project->id,
                'uuid' => $project->uuid,
                'name' => $project->name,
                'slug' => $project->slug,
                'status' => $project->status,
            ],
            'analysis' => $analysis,
            'discovery_gate' => $discoveryGate,
        ];
    }

    public function discoveryDecisionForProject(int $projectId, ?int $userId = null): array
    {
        $project = FactoryProject::query()->findOrFail($projectId);
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        if (($metadata['contract'] ?? null) !== 'factory.via.intake.v1') {
            throw new RuntimeException('Este projeto não foi criado pelo fluxo VIA Intake v1.');
        }
        if (($metadata['analysis_status'] ?? null) !== 'ready') {
            throw new RuntimeException('A análise ainda não está pronta para o Discovery Gate.');
        }

        $gate = $this->evaluateDiscoveryGate(
            is_array($metadata['analysis'] ?? null) ? $metadata['analysis'] : [],
            (string) ($metadata['request'] ?? $project->description),
        );

        $project->forceFill([
            'metadata' => array_merge($metadata, [
                'discovery_gate' => $gate,
                'discovery_checked_at' => now()->toISOString(),
            ]),
            'updated_by' => $userId,
        ])->save();

        return $gate;
    }

    public function validateDiscovery(int $projectId, array $evidence, ?int $userId = null): array
    {
        $project = FactoryProject::query()->findOrFail($projectId);
        $metadata = is_array($project->metadata) ? $project->metadata : [];
        $gate = is_array($metadata['discovery_gate'] ?? null) ? $metadata['discovery_gate'] : [];

        if (($gate['decision'] ?? null) === 'reuse_or_evolve') {
            throw new RuntimeException('Discovery Validation não pode sobrescrever reuse_or_evolve.');
        }
        if (($gate['decision'] ?? null) !== 'discovery_required') {
            throw new RuntimeException('Discovery Validation exige decisão inicial discovery_required.');
        }

        foreach (['factory', 'github', 'n8n'] as $source) {
            $item = is_array($evidence[$source] ?? null) ? $evidence[$source] : [];
            if (($item['checked'] ?? false) !== true) {
                throw new RuntimeException("Discovery Validation sem evidência de {$source}.");
            }
            if (! empty($item['matches'])) {
                throw new RuntimeException("Discovery Validation encontrou capacidade existente em {$source}.");
            }
        }

        $validation = [
            'decision' => 'build_after_validation',
            'validated_at' => now()->toISOString(),
            'evidence' => $evidence,
        ];

        $project->forceFill([
            'metadata' => array_merge($metadata, [
                'discovery_validation' => $validation,
                'approval_status' => 'pending',
            ]),
            'updated_by' => $userId,
        ])->save();

        return $validation;
    }

    public function executeApproved(int $projectId, ?int $userId = null): array
    {
        $project = FactoryProject::query()->findOrFail($projectId);
        $metadata = is_array($project->metadata) ? $project->metadata : [];

        if (($metadata['contract'] ?? null) !== 'factory.via.intake.v1') {
            throw new RuntimeException('Este projeto não foi criado pelo fluxo VIA Intake v1.');
        }
        if (($metadata['analysis_status'] ?? null) !== 'ready') {
            throw new RuntimeException('A análise ainda não está pronta para aprovação.');
        }

        $gate = $this->discoveryDecisionForProject($projectId, $userId);
        $project->refresh();
        $metadata = is_array($project->metadata) ? $project->metadata : [];

        $effectiveDecision = (string) ($gate['decision'] ?? 'unknown');
        if ($effectiveDecision === 'discovery_required') {
            $validation = is_array($metadata['discovery_validation'] ?? null) ? $metadata['discovery_validation'] : [];
            $effectiveDecision = (string) ($validation['decision'] ?? 'discovery_required');
        }

        if ($effectiveDecision !== 'build_after_validation') {
            $project->forceFill([
                'metadata' => array_merge($metadata, [
                    'discovery_gate' => $gate,
                    'approval_status' => 'blocked_by_discovery',
                    'discovery_blocked_at' => now()->toISOString(),
                ]),
                'updated_by' => $userId,
            ])->save();

            throw new RuntimeException(
                'Discovery Gate bloqueou a construção: '.
                $effectiveDecision.'. '.
                (string) ($gate['reason'] ?? 'A construção não foi autorizada pelo Factory Kernel.')
            );
        }

        $request = trim((string) ($metadata['analysis']['build_request'] ?? $metadata['request'] ?? $project->description));
        if ($request === '') {
            throw new RuntimeException('Pedido de construção ausente no Intake.');
        }

        $execution = FactoryExecution::query()->create([
            'uuid' => (string) Str::uuid(),
            'factory_project_id' => $project->id,
            'name' => 'VIA Approved Factory E2E',
            'status' => 'pending',
            'attempt' => 1,
            'input' => ['request' => $request, 'approved_by' => $userId],
            'context' => ['source' => 'via', 'contract' => 'factory.via.intake.v1'],
            'created_by' => $userId,
            'updated_by' => $userId,
        ]);

        $execution->markAsRunning();
        $project->forceFill([
            'status' => 'building',
            'metadata' => array_merge($metadata, [
                'approval_status' => 'approved',
                'approved_at' => now()->toISOString(),
                'approved_by' => $userId,
            ]),
            'updated_by' => $userId,
        ])->save();

        try {
            $finalization = $this->finisher->finish($request);
            if (($finalization['finalize_status'] ?? null) !== 'passed') {
                throw new RuntimeException('A finalização genérica da Factory não terminou com sucesso.');
            }

            $qaExit = Artisan::call('factory:smart-qa2');
            $qaOutput = trim(Artisan::output());
            if ($qaExit !== 0) {
                throw new RuntimeException('Smart QA falhou: '.mb_substr($qaOutput, 0, 1500));
            }

            $blueprint = trim((string) ($finalization['blueprint'] ?? ''));
            if ($blueprint === '') {
                throw new RuntimeException('A Factory não informou o blueprint gerado.');
            }

            $dryRunExit = Artisan::call('factory:real-install', [
                'blueprint' => $blueprint,
                '--dry-run' => true,
            ]);
            $dryRunOutput = trim(Artisan::output());
            if ($dryRunExit !== 0) {
                throw new RuntimeException('Dry-run real de instalação falhou: '.mb_substr($dryRunOutput, 0, 1500));
            }

            $result = [
                'finalization' => $finalization,
                'qa' => ['exit_code' => $qaExit, 'output' => $qaOutput],
                'dry_run' => ['exit_code' => $dryRunExit, 'output' => $dryRunOutput],
                'result' => 'ready_for_human_release_checkpoint',
            ];

            $project->forceFill([
                'status' => 'ready_for_release',
                'metadata' => array_merge($project->metadata ?? [], [
                    'execution_status' => 'validated',
                    'blueprint' => $blueprint,
                    'qa_status' => 'passed',
                    'dry_run_status' => 'passed',
                    'validated_at' => now()->toISOString(),
                ]),
                'updated_by' => $userId,
            ])->save();
            $execution->markAsFinished($result);

            return ['project' => $project->fresh(), 'execution' => $execution->fresh(), 'result' => $result];
        } catch (Throwable $e) {
            $project->forceFill([
                'status' => 'failed',
                'metadata' => array_merge($project->metadata ?? [], [
                    'execution_status' => 'failed',
                    'execution_error' => mb_substr($e->getMessage(), 0, 2000),
                    'failed_at' => now()->toISOString(),
                ]),
                'updated_by' => $userId,
            ])->save();
            $execution->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    private function evaluateDiscoveryGate(array $analysis, string $request): array
    {
        $capability = trim((string) ($analysis['summary'] ?? $analysis['build_request'] ?? $request));
        if ($capability === '') {
            throw new RuntimeException('Discovery Gate sem capacidade para avaliar.');
        }

        $url = rtrim((string) env('FACTORY_KERNEL_URL', 'http://vitrine_mcp_ops_broker:8770/internal/factory-kernel/decide'), '/');
        try {
            $response = Http::timeout(15)
                ->acceptJson()
                ->get($url, ['capability' => mb_substr($capability, 0, 500)]);
        } catch (Throwable $e) {
            throw new RuntimeException('Factory Kernel indisponível para Discovery Gate: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            throw new RuntimeException('Factory Kernel HTTP '.$response->status().' no Discovery Gate.');
        }

        $gate = $response->json();
        if (! is_array($gate) || ! in_array(($gate['decision'] ?? null), ['reuse_or_evolve', 'discovery_required', 'build_after_validation'], true)) {
            throw new RuntimeException('Factory Kernel retornou decisão inválida no Discovery Gate.');
        }

        return $gate;
    }

    private function analyzeWithCore(string $request): array
    {
        $token = trim((string) env('CENTRO_IA_INTERNAL_TOKEN', ''));
        if ($token === '') {
            throw new RuntimeException('CENTRO_IA_INTERNAL_TOKEN ausente na Factory.');
        }

        $url = rtrim((string) env('CORE_AI_HUB_URL', 'http://vitrine_core_web_hml/api/internal/ai-dev/chat'), '/');
        $projectId = trim((string) env('VIA_AI_PROJECT_ID', 'via-agent-hub')) ?: 'via-agent-hub';
        $schema = [
            'name' => 'Nome curto do sistema/projeto',
            'summary' => 'Resumo objetivo do pedido',
            'profile_dna' => ['domain' => 'domínio', 'users' => ['usuários'], 'goals' => ['objetivos'], 'constraints' => ['restrições'], 'capabilities' => ['capacidades']],
            'master_prompt' => 'Prompt mestre para construção',
            'build_request' => 'Pedido técnico consolidado, sem inventar credenciais, domínio ou integrações',
            'risks' => ['riscos identificados'],
            'assumptions' => ['premissas'],
            'open_decisions' => ['decisões que exigem validação humana'],
        ];

        $response = Http::withToken($token)
            ->withHeaders(['X-Vitrine-Project' => $projectId])
            ->timeout(90)
            ->acceptJson()
            ->asJson()
            ->post($url, [
                'project_id' => $projectId,
                'profile' => 'balanced',
                'system' => 'Você é a inteligência de Intake da Vitrine IA Pro Factory. Converta o pedido em análise estruturada para construção de software. Responda SOMENTE JSON válido. Preserve fatos, explicite riscos e decisões abertas, não invente credenciais, domínios, repositórios ou integrações.',
                'prompt' => "Pedido do usuário:\n{$request}\n\nFormato obrigatório:\n".json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'options' => ['temperature' => 0.1],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Core AI Hub HTTP '.$response->status().': '.mb_substr($response->body(), 0, 1000));
        }

        $content = trim((string) data_get($response->json(), 'data.content', ''));
        if ($content === '') {
            throw new RuntimeException('Core AI Hub respondeu sem conteúdo.');
        }
        if (str_starts_with($content, '```')) {
            $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
            $content = preg_replace('/\s*```$/', '', $content) ?? $content;
            $content = trim($content);
        }

        $analysis = json_decode($content, true);
        if (! is_array($analysis) || json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Core AI Hub retornou JSON inválido: '.json_last_error_msg());
        }

        foreach (['name', 'profile_dna', 'master_prompt', 'build_request'] as $required) {
            if (! array_key_exists($required, $analysis)) {
                throw new RuntimeException('Análise do Core ausente em campo obrigatório: '.$required);
            }
        }

        return $analysis;
    }
}
