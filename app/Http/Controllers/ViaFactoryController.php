<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Factory\Models\FactoryExecution;
use App\Factory\Models\FactoryProject;
use App\Factory\Production\Services\ProductionStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Throwable;

final class ViaFactoryController extends Controller
{
    public function context(Request $request, ProductionStatusService $productionStatus): JsonResponse
    {
        $this->assertAdmin($request);

        return response()->json([
            'ok' => true,
            'context' => $this->buildOperationalContext(
                $request,
                $productionStatus,
                $request->only(['url', 'path', 'title', 'module', 'resource'])
            ),
            'capabilities' => [
                'consultar_saude',
                'listar_projetos',
                'listar_execucoes',
                'consultar_producao',
                'consultar_release',
                'gerar_plano',
                'criar_arquitetura_com_confirmacao',
                'produzir_solicitacao_com_confirmacao',
                'finalizar_projeto_com_confirmacao',
                'produzir_enterprise_com_confirmacao',
            ],
        ]);
    }

    public function chat(Request $request, ProductionStatusService $productionStatus): JsonResponse
    {
        $this->assertAdmin($request);

        $validated = $request->validate([
            'message' => ['required', 'string', 'max:20000'],
            'history' => ['sometimes', 'array', 'max:30'],
            'history.*.role' => ['required_with:history', Rule::in(['user', 'assistant'])],
            'history.*.content' => ['required_with:history', 'string', 'max:12000'],
            'sessionId' => ['sometimes', 'nullable', 'string', 'max:160'],
            'context' => ['sometimes', 'array'],
        ]);

        $message = trim($validated['message']);
        $operationalContext = $this->buildOperationalContext(
            $request,
            $productionStatus,
            is_array($validated['context'] ?? null) ? $validated['context'] : []
        );

        if ($answer = $this->answerOperationalIntent($message, $operationalContext)) {
            return response()->json($answer);
        }

        try {
            $response = Http::timeout(75)
                ->acceptJson()
                ->asJson()
                ->post($this->vaeBaseUrl().'/api/via', [
                    'message' => $message,
                    'history' => $validated['history'] ?? [],
                    'sessionId' => $validated['sessionId'] ?? 'factory-user-'.$request->user()->getAuthIdentifier(),
                    'context' => $operationalContext,
                ]);

            if ($response->successful()) {
                return response()->json(array_merge($response->json(), [
                    'factory_connected' => true,
                    'operational_context' => $operationalContext,
                ]));
            }

            Log::warning('via.factory.chat_upstream_failed', [
                'status' => $response->status(),
                'user_id' => $request->user()->getAuthIdentifier(),
            ]);
        } catch (Throwable $exception) {
            Log::warning('via.factory.chat_connection_failed', [
                'error' => $exception->getMessage(),
                'user_id' => $request->user()->getAuthIdentifier(),
            ]);
        }

        return response()->json([
            'answer' => $this->fallbackAnswer($operationalContext),
            'mode' => 'factory-fallback',
            'factory_connected' => true,
        ]);
    }

    public function action(Request $request): JsonResponse
    {
        $this->assertAdmin($request);

        $validated = $request->validate([
            'action' => ['required', Rule::in([
                'factory_health',
                'production_status',
                'release_status',
                'plugins',
                'ai_plan',
                'architect_request',
                'produce_request',
                'finish_project',
                'produce_enterprise',
            ])],
            'payload' => ['sometimes', 'array'],
            'confirm' => ['sometimes', 'nullable', 'string', 'max:20'],
        ]);

        $action = $validated['action'];
        $payload = is_array($validated['payload'] ?? null) ? $validated['payload'] : [];
        $confirm = (string) ($validated['confirm'] ?? '');

        $definition = match ($action) {
            'factory_health' => ['command' => 'factory:health', 'arguments' => [], 'sensitive' => false],
            'production_status' => ['command' => 'factory:production-status', 'arguments' => [], 'sensitive' => false],
            'release_status' => ['command' => 'factory:release-status', 'arguments' => [], 'sensitive' => false],
            'plugins' => ['command' => 'factory:plugins', 'arguments' => [], 'sensitive' => false],
            'ai_plan' => [
                'command' => 'factory:ai-plan',
                'arguments' => ['prompt' => $this->words($this->requiredText($payload, 'prompt', 10))],
                'sensitive' => false,
            ],
            'architect_request' => [
                'command' => 'factory:architect-request',
                'arguments' => ['request' => $this->words($this->requiredText($payload, 'request', 10))],
                'sensitive' => true,
            ],
            'produce_request' => [
                'command' => 'factory:produce-request',
                'arguments' => ['request' => $this->words($this->requiredText($payload, 'request', 10))],
                'sensitive' => true,
            ],
            'finish_project' => [
                'command' => 'factory:finish-project',
                'arguments' => ['request' => $this->words($this->requiredText($payload, 'request', 10))],
                'sensitive' => true,
            ],
            'produce_enterprise' => [
                'command' => 'factory:produce-enterprise',
                'arguments' => ['product' => $this->allowedProduct($this->requiredText($payload, 'product', 3))],
                'sensitive' => true,
            ],
        };

        if ($definition['sensitive'] && $confirm !== 'EXECUTAR') {
            return response()->json([
                'ok' => false,
                'requires_confirmation' => true,
                'answer' => 'Esta ação criará ou alterará artefatos da Factory. Confirme explicitamente para executar.',
                'action' => $action,
                'payload' => $payload,
            ], 409);
        }

        $result = $this->runArtisan($definition['command'], $definition['arguments']);

        Log::info('via.factory.action', [
            'action' => $action,
            'command' => $definition['command'],
            'exit_code' => $result['exit_code'],
            'user_id' => $request->user()->getAuthIdentifier(),
        ]);

        return response()->json([
            'ok' => $result['exit_code'] === 0,
            'action' => $action,
            'answer' => $result['exit_code'] === 0
                ? "Ação concluída pela Factory.\n\n".$result['output']
                : "A Factory não concluiu a ação.\n\n".$result['output'],
            'result' => $result,
        ], $result['exit_code'] === 0 ? 200 : 422);
    }

    private function answerOperationalIntent(string $message, array $context): ?array
    {
        $normalized = mb_strtolower($message, 'UTF-8');

        if (preg_match('/\b(status|sa[uú]de|situa[cç][aã]o)\b.*\bfactory\b|\bfactory\b.*\b(status|sa[uú]de|situa[cç][aã]o)\b/u', $normalized)) {
            return [
                'answer' => $this->factoryStatusAnswer($context),
                'mode' => 'factory-operational',
                'factory_connected' => true,
            ];
        }

        if (preg_match('/\b(projetos?|produto(?:s)?)\b/u', $normalized)) {
            return [
                'answer' => $this->projectsAnswer($context),
                'mode' => 'factory-operational',
                'factory_connected' => true,
            ];
        }

        if (preg_match('/\b(execu[cç][oõ]es?|tarefas?|jobs?)\b/u', $normalized)) {
            return [
                'answer' => $this->executionsAnswer($context),
                'mode' => 'factory-operational',
                'factory_connected' => true,
            ];
        }

        if (preg_match('/\b(produ[cç][aã]o|release|vers[aã]o)\b/u', $normalized)) {
            return [
                'answer' => $this->productionAnswer($context),
                'mode' => 'factory-operational',
                'factory_connected' => true,
            ];
        }

        if (preg_match('/\b(ecossistema|servidor|vps|servi[cç]os?)\b/u', $normalized)) {
            return [
                'answer' => $this->ecosystemAnswer($context),
                'mode' => 'factory-operational',
                'factory_connected' => true,
            ];
        }

        if (preg_match('/(?:planeje|planejar|crie um plano|gere um plano)\s+(?:para\s+)?(.+)/iu', $message, $matches)) {
            $prompt = trim($matches[1]);
            if (mb_strlen($prompt) >= 10) {
                $result = $this->runArtisan('factory:ai-plan', ['prompt' => $this->words($prompt)]);

                return [
                    'answer' => $result['exit_code'] === 0
                        ? "Plano gerado pela Factory.\n\n".$result['output']
                        : "Não consegui gerar o plano.\n\n".$result['output'],
                    'mode' => 'factory-action',
                    'factory_connected' => true,
                    'action' => 'ai_plan',
                    'ok' => $result['exit_code'] === 0,
                ];
            }
        }

        if (preg_match('/(?:produza|gerar|gere)\s+(?:o\s+)?(?:produto\s+)?enterprise\s+([a-z0-9_-]+)/iu', $message, $matches)) {
            $product = trim($matches[1]);
            return [
                'answer' => 'Posso produzir o pacote Enterprise do produto informado. A ação gera builds completos e exige confirmação.',
                'mode' => 'factory-confirmation',
                'factory_connected' => true,
                'requires_confirmation' => true,
                'action' => 'produce_enterprise',
                'payload' => ['product' => $product],
            ];
        }

        if (preg_match('/(?:produza|produzir|gere o pacote|crie o pacote)\s+(?:um\s+)?(?:sistema|produto|pacote)?\s*(?:para|de)?\s*(.+)/iu', $message, $matches)) {
            $description = trim($matches[1]);
            if (mb_strlen($description) >= 10) {
                return [
                    'answer' => 'Posso produzir essa solicitação na área segura da Factory. A ação gera artefatos e precisa da sua confirmação.',
                    'mode' => 'factory-confirmation',
                    'factory_connected' => true,
                    'requires_confirmation' => true,
                    'action' => 'produce_request',
                    'payload' => ['request' => $description],
                ];
            }
        }

        if (preg_match('/(?:finalize|finalizar|conclua|concluir)\s+(?:o\s+)?(?:projeto|sistema)?\s*(?:para|de)?\s*(.+)/iu', $message, $matches)) {
            $description = trim($matches[1]);
            if (mb_strlen($description) >= 10) {
                return [
                    'answer' => 'Posso executar a finalização completa, incluindo produção e real build. Essa ação exige confirmação explícita.',
                    'mode' => 'factory-confirmation',
                    'factory_connected' => true,
                    'requires_confirmation' => true,
                    'action' => 'finish_project',
                    'payload' => ['request' => $description],
                ];
            }
        }

        if (preg_match('/(?:crie|gere|monte)\s+(?:uma\s+)?(?:arquitetura|blueprint)\s*(?:para|de)?\s*(.+)/iu', $message, $matches)) {
            $description = trim($matches[1]);
            if (mb_strlen($description) >= 10) {
                return [
                    'answer' => 'Posso criar a arquitetura e o blueprint na Factory. Essa ação grava artefatos e precisa da sua confirmação.',
                    'mode' => 'factory-confirmation',
                    'factory_connected' => true,
                    'requires_confirmation' => true,
                    'action' => 'architect_request',
                    'payload' => ['request' => $description],
                ];
            }
        }

        return null;
    }

    private function buildOperationalContext(
        Request $request,
        ProductionStatusService $productionStatus,
        array $pageContext = []
    ): array {
        $factory = [
            'projects_total' => 0,
            'projects_by_status' => [],
            'executions_total' => 0,
            'executions_by_status' => [],
            'recent_projects' => [],
            'recent_executions' => [],
            'production' => [],
        ];

        try {
            $factory['projects_total'] = FactoryProject::query()->count();
            $factory['projects_by_status'] = FactoryProject::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->all();
            $factory['recent_projects'] = FactoryProject::query()
                ->latest('id')
                ->limit(6)
                ->get(['id', 'uuid', 'name', 'slug', 'status', 'updated_at'])
                ->map(fn (FactoryProject $project): array => [
                    'id' => $project->id,
                    'uuid' => $project->uuid,
                    'name' => $project->name,
                    'slug' => $project->slug,
                    'status' => $project->status,
                    'updated_at' => optional($project->updated_at)->toISOString(),
                ])
                ->all();

            $factory['executions_total'] = FactoryExecution::query()->count();
            $factory['executions_by_status'] = FactoryExecution::query()
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->map(fn ($value): int => (int) $value)
                ->all();
            $factory['recent_executions'] = FactoryExecution::query()
                ->with('project:id,name')
                ->latest('id')
                ->limit(8)
                ->get(['id', 'uuid', 'factory_project_id', 'name', 'status', 'duration_ms', 'created_at'])
                ->map(fn (FactoryExecution $execution): array => [
                    'id' => $execution->id,
                    'uuid' => $execution->uuid,
                    'name' => $execution->name,
                    'project' => $execution->project?->name,
                    'status' => $execution->status,
                    'duration_ms' => $execution->duration_ms,
                    'created_at' => optional($execution->created_at)->toISOString(),
                ])
                ->all();

            $factory['production'] = $productionStatus->status();
        } catch (Throwable $exception) {
            $factory['error'] = $exception->getMessage();
        }

        $ecosystem = [
            'status' => 'unavailable',
            'summary' => [],
            'services' => [],
        ];

        try {
            $response = Http::timeout(6)->acceptJson()->get($this->vaeBaseUrl().'/api/vae/ecosystem');
            if ($response->successful()) {
                $ecosystem = $response->json();
            }
        } catch (Throwable $exception) {
            $ecosystem['error'] = $exception->getMessage();
        }

        return [
            'source' => 'Vitrine IA Pro Factory',
            'generated_at' => now()->toISOString(),
            'user' => [
                'id' => $request->user()->getAuthIdentifier(),
                'name' => $request->user()->name,
                'role' => $request->user()->role,
            ],
            'page' => [
                'url' => $pageContext['url'] ?? $request->headers->get('referer'),
                'path' => $pageContext['path'] ?? null,
                'title' => $pageContext['title'] ?? null,
                'module' => $pageContext['module'] ?? 'Factory',
                'resource' => $pageContext['resource'] ?? null,
            ],
            'factory' => $factory,
            'ecosystem' => $ecosystem,
        ];
    }

    private function factoryStatusAnswer(array $context): string
    {
        $factory = $context['factory'];
        $ecosystem = $context['ecosystem'];
        $summary = $ecosystem['summary'] ?? [];

        return sprintf(
            "A Factory está conectada.\nProjetos: %d.\nExecuções: %d.\nProdução: %s.\nEcossistema: %s (%d online, %d degradados, %d offline).",
            (int) ($factory['projects_total'] ?? 0),
            (int) ($factory['executions_total'] ?? 0),
            (string) ($factory['production']['status'] ?? 'não informado'),
            (string) ($ecosystem['status'] ?? 'indisponível'),
            (int) ($summary['online'] ?? 0),
            (int) ($summary['degraded'] ?? 0),
            (int) ($summary['offline'] ?? 0),
        );
    }

    private function projectsAnswer(array $context): string
    {
        $factory = $context['factory'];
        $projects = $factory['recent_projects'] ?? [];
        $lines = [sprintf('A Factory possui %d projeto(s).', (int) ($factory['projects_total'] ?? 0))];

        if ($projects === []) {
            $lines[] = 'Ainda não há projetos registrados na tabela operacional.';
        } else {
            $lines[] = 'Projetos mais recentes:';
            foreach ($projects as $project) {
                $lines[] = sprintf('• %s — %s', $project['name'] ?: $project['slug'], $project['status'] ?: 'sem status');
            }
        }

        return implode("\n", $lines);
    }

    private function executionsAnswer(array $context): string
    {
        $factory = $context['factory'];
        $executions = $factory['recent_executions'] ?? [];
        $lines = [sprintf('A Factory possui %d execução(ões) registrada(s).', (int) ($factory['executions_total'] ?? 0))];

        if ($executions === []) {
            $lines[] = 'Não há execuções recentes para apresentar.';
        } else {
            $lines[] = 'Execuções mais recentes:';
            foreach ($executions as $execution) {
                $label = $execution['name'] ?: ($execution['uuid'] ?: 'Execução');
                $project = $execution['project'] ? ' · '.$execution['project'] : '';
                $lines[] = sprintf('• %s%s — %s', $label, $project, $execution['status'] ?: 'sem status');
            }
        }

        return implode("\n", $lines);
    }

    private function productionAnswer(array $context): string
    {
        $production = $context['factory']['production'] ?? [];

        return sprintf(
            "Motor de produção: %s.\nVersão: %s.\nStatus: %s.\nProdutos disponíveis: %s.\nArmazenamento: %s.",
            (string) ($production['engine'] ?? 'não informado'),
            (string) ($production['version'] ?? 'não informada'),
            (string) ($production['status'] ?? 'não informado'),
            implode(', ', $production['products_available'] ?? []) ?: 'nenhum informado',
            !empty($production['storage_ready']) ? 'pronto' : 'indisponível',
        );
    }

    private function ecosystemAnswer(array $context): string
    {
        $ecosystem = $context['ecosystem'] ?? [];
        $summary = $ecosystem['summary'] ?? [];
        $lines = [sprintf(
            'Ecossistema: %s. %d serviço(s) configurado(s), %d online, %d degradado(s), %d offline.',
            (string) ($ecosystem['status'] ?? 'indisponível'),
            (int) ($summary['configured'] ?? 0),
            (int) ($summary['online'] ?? 0),
            (int) ($summary['degraded'] ?? 0),
            (int) ($summary['offline'] ?? 0),
        )];

        foreach (($ecosystem['services'] ?? []) as $service) {
            $lines[] = sprintf('• %s — %s', $service['label'] ?? $service['id'] ?? 'Serviço', $service['status'] ?? 'desconhecido');
        }

        return implode("\n", $lines);
    }

    private function fallbackAnswer(array $context): string
    {
        return $this->factoryStatusAnswer($context)
            ."\n\nO motor conversacional está temporariamente indisponível, mas continuo conectada aos dados operacionais da Factory.";
    }

    private function runArtisan(string $command, array $arguments = []): array
    {
        try {
            $exitCode = Artisan::call($command, $arguments);

            return [
                'command' => $command,
                'exit_code' => $exitCode,
                'output' => trim(Artisan::output()) ?: 'Comando concluído sem saída textual.',
            ];
        } catch (Throwable $exception) {
            return [
                'command' => $command,
                'exit_code' => 1,
                'output' => $exception->getMessage(),
            ];
        }
    }

    private function requiredText(array $payload, string $key, int $minimum): string
    {
        $value = trim((string) ($payload[$key] ?? ''));
        abort_if(mb_strlen($value) < $minimum, 422, "O campo {$key} precisa ter pelo menos {$minimum} caracteres.");

        return $value;
    }

    private function allowedProduct(string $value): string
    {
        $product = mb_strtolower(trim($value), 'UTF-8');
        $aliases = [
            'gov360' => 'gov360',
            'governo' => 'gov360',
            'guia_digital' => 'guia_digital',
            'guia-digital' => 'guia_digital',
            'guia' => 'guia_digital',
            'portal_news' => 'portal_news',
            'portal-news' => 'portal_news',
            'news' => 'portal_news',
            'tv_digital' => 'tv_digital',
            'tv-digital' => 'tv_digital',
            'tv' => 'tv_digital',
            'sismed' => 'sismed',
        ];

        abort_unless(isset($aliases[$product]), 422, 'Produto Enterprise não permitido. Use gov360, guia_digital, portal_news, tv_digital ou sismed.');

        return $aliases[$product];
    }

    private function words(string $value): array
    {
        return preg_split('/\s+/u', trim($value), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    private function assertAdmin(Request $request): void
    {
        abort_unless($request->user() && $request->user()->isAdmin(), 403);
    }

    private function vaeBaseUrl(): string
    {
        return rtrim((string) (config('services.vae_core.url') ?: 'http://vae_core:3091'), '/');
    }
}
