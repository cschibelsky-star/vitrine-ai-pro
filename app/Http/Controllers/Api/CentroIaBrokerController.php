<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgent;
use App\Services\Ai\AiExecutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CentroIaBrokerController extends Controller
{
    public function execute(Request $request, AiExecutionService $service): JsonResponse
    {
        $expectedToken = (string) config('centro_ia.internal_token', '');
        $receivedToken = (string) $request->bearerToken();

        if ($expectedToken === '' || $receivedToken === '' || ! hash_equals($expectedToken, $receivedToken)) {
            return response()->json([
                'ok' => false,
                'error' => 'unauthorized',
            ], 401);
        }

        $data = $request->validate([
            'project_id' => ['required', 'string', 'max:120'],
            'capability' => ['required', 'string', 'max:120'],
            'input' => ['required', 'array'],
            'input.system' => ['nullable', 'string', 'max:20000'],
            'input.user' => ['required', 'string', 'min:5', 'max:120000'],
            'input.response_format' => ['nullable', 'string', 'max:30'],
            'input.temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
        ]);

        $projectHeader = trim((string) $request->header('X-Vitrine-Project', ''));
        if ($projectHeader === '' || ! hash_equals((string) $data['project_id'], $projectHeader)) {
            return response()->json([
                'ok' => false,
                'error' => 'project_identity_mismatch',
            ], 422);
        }

        $capability = (string) $data['capability'];
        $capabilityConfig = config('centro_ia.capabilities.' . $capability);

        if (! is_array($capabilityConfig)) {
            return response()->json([
                'ok' => false,
                'error' => 'capability_not_supported',
                'capability' => $capability,
            ], 422);
        }

        $agent = $this->resolveAgent($capabilityConfig);

        if (! $agent) {
            return response()->json([
                'ok' => false,
                'error' => 'capability_agent_not_configured',
                'capability' => $capability,
            ], 503);
        }

        $system = trim((string) ($data['input']['system'] ?? ''));
        $user = trim((string) $data['input']['user']);
        $prompt = $system !== ''
            ? "INSTRUCOES DO SISTEMA:\n{$system}\n\nSOLICITACAO:\n{$user}"
            : $user;

        $execution = $service->execute($agent, $prompt);
        $status = (string) ($execution->status ?? '');
        $output = (string) ($execution->output ?? '');

        if ($status !== 'Concluído') {
            return response()->json([
                'ok' => false,
                'error' => 'ai_execution_failed',
                'execution_id' => $execution->id,
                'status' => $status,
                'message' => $output,
            ], 502);
        }

        return response()->json([
            'ok' => true,
            'project_id' => $data['project_id'],
            'capability' => $capability,
            'execution_id' => $execution->id,
            'agent_id' => $agent->id,
            'model' => $execution->model_name ?? null,
            'output_text' => $output,
        ]);
    }

    private function resolveAgent(array $config): ?AiAgent
    {
        $agentId = $config['agent_id'] ?? null;
        if ($agentId !== null && $agentId !== '') {
            $agent = AiAgent::find($agentId);
            if ($agent) {
                return $agent;
            }
        }

        $agentSlug = trim((string) ($config['agent_slug'] ?? ''));
        if ($agentSlug !== '') {
            return AiAgent::query()->where('slug', $agentSlug)->first();
        }

        return null;
    }
}
