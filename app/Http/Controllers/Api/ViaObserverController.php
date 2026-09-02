<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Lead;
use App\Models\License;
use App\Models\Module;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Subscription;
use App\Shared\AI\Models\AiModel;
use App\Shared\AI\Services\ViaObserverGateway;
use App\Shared\AI\Services\ViaObserverMissionOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ViaObserverController extends Controller
{
    public function capabilities(Request $request, ViaObserverGateway $gateway): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        return response()->json([
            'ok' => true,
            'data' => $gateway->capabilities(),
        ]);
    }

    public function missionDryRun(Request $request, ViaObserverMissionOrchestrator $orchestrator): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $this->validateMission($request);

        try {
            return response()->json([
                'ok' => true,
                'data' => $orchestrator->dryRun($data),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'via_mission_dry_run_failed',
                'message' => 'Falha ao validar o pipeline da missão VIA.',
            ], 422);
        }
    }

    public function missionExecute(Request $request, ViaObserverMissionOrchestrator $orchestrator): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $this->validateMission($request);

        try {
            return response()->json([
                'ok' => true,
                'data' => $orchestrator->execute($data),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'via_mission_failed',
                'message' => 'Falha ao processar a missão no VIA Agent Hub.',
            ], 502);
        }
    }

    public function analyze(Request $request, ViaObserverGateway $gateway): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $data = $request->validate([
            'domain' => ['nullable', 'string', 'max:80'],
            'target_project_id' => ['nullable', 'string', 'max:120'],
            'mission' => ['required', 'string', 'min:3', 'max:120000'],
            'context' => ['nullable', 'string', 'max:200000'],
            'provider' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:190'],
            'profile' => ['nullable', 'in:economy,balanced,premium'],
            'options' => ['nullable', 'array'],
        ]);

        try {
            return response()->json([
                'ok' => true,
                'data' => $gateway->analyze($data),
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'ok' => false,
                'error' => 'via_observer_failed',
                'message' => 'Falha ao processar a missão no VIA Agent Hub.',
            ], 502);
        }
    }

    public function globalContext(Request $request): JsonResponse
    {
        if ($unauthorized = $this->authorizeRequest($request)) {
            return $unauthorized;
        }

        $count = static fn (string $table, string $model): int => Schema::hasTable($table) ? $model::query()->count() : 0;

        $payload = [
            'collected_at' => now()->toISOString(),
            'source' => 'core_database_readonly_v1',
            'read_only' => true,
            'architecture' => [
                'core' => 'gestão, clientes, comercial, produtos, planos, licenças, financeiro, configurações e Centro IA',
                'factory' => 'produção de software, arquitetura, desenvolvimento, QA, documentação, builds, releases, homologação e deploy',
                'flow' => 'automações e orquestrações de processo',
                'roteia' => 'gateway transversal de IA do ecossistema',
                'via' => 'inteligência virtual persistente e orquestradora do ecossistema',
            ],
            'core' => [
                'clients' => $count('companies', Company::class),
                'active_clients' => Schema::hasTable('companies') ? Company::query()->where('status', 'Ativo')->count() : 0,
                'licenses' => $count('licenses', License::class),
                'active_licenses' => Schema::hasTable('licenses') ? License::query()->where('status', 'Ativa')->count() : 0,
                'plans' => $count('plans', Plan::class),
                'modules' => $count('modules', Module::class),
                'products' => $count('products', Product::class),
                'leads' => $count('leads', Lead::class),
                'subscriptions' => $count('subscriptions', Subscription::class),
                'active_subscriptions' => Schema::hasTable('subscriptions') ? Subscription::query()->where('status', 'Ativa')->count() : 0,
                'open_payments' => Schema::hasTable('payments') ? Payment::query()->whereIn('status', ['Aberto', 'Atrasado'])->count() : 0,
                'verified_ai_models' => Schema::hasTable('ai_models') && Schema::hasColumn('ai_models', 'is_verified')
                    ? AiModel::query()->where('is_active', true)->where('is_verified', true)->count()
                    : 0,
            ],
            'products' => Schema::hasTable('products')
                ? Product::query()->orderBy('nome')->get()->map(fn (Product $product): array => [
                    'name' => $product->nome,
                    'status' => $product->status,
                ])->values()->all()
                : [],
            'safety' => [
                'writes' => 0,
                'deploys' => 0,
                'destructive_actions' => 0,
            ],
        ];

        return response()->json(['ok' => true, 'data' => $payload]);
    }

    private function validateMission(Request $request): array
    {
        return $request->validate([
            'domain' => ['nullable', 'string', 'max:80'],
            'target_project_id' => ['nullable', 'string', 'max:120'],
            'mission' => ['required', 'string', 'min:3', 'max:120000'],
            'provider' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:190'],
            'profile' => ['nullable', 'in:economy,balanced,premium'],
            'options' => ['nullable', 'array'],
        ]);
    }

    private function authorizeRequest(Request $request): ?JsonResponse
    {
        $expected = (string) config('ai_dev_hub.internal_token', '');
        $received = (string) $request->bearerToken();

        if ($expected === '' || $received === '' || ! hash_equals($expected, $received)) {
            return response()->json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        return null;
    }
}
