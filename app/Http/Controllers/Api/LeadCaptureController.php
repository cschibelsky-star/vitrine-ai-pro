<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class LeadCaptureController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $configuredKey = (string) config('lead_capture.key', '');
        $providedKey = (string) ($request->header('X-Vitrine-Lead-Key') ?: $request->bearerToken() ?: '');

        if ($configuredKey === '') {
            Log::critical('LEAD_CAPTURE_KEY não configurada. A API pública de leads foi bloqueada.');

            return response()->json([
                'success' => false,
                'message' => 'Captação temporariamente indisponível.',
            ], 503);
        }

        if ($providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.',
            ], 401);
        }

        $consentRules = config('lead_capture.require_consent', true)
            ? ['required', 'accepted']
            : ['nullable', 'boolean'];

        $validator = Validator::make($request->all(), [
            'external_id' => ['nullable', 'string', 'max:191'],
            'empresa' => ['nullable', 'string', 'max:255'],
            'contato' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'size:2'],
            'produto_interesse' => ['required', 'string', 'max:255'],
            'plano_sugerido' => ['nullable', 'string', 'max:255'],
            'valor_estimado' => ['nullable', 'numeric'],
            'origem_lead' => ['nullable', 'string', 'max:255'],
            'pagina_origem' => ['required', 'string', 'max:255'],
            'campanha' => ['nullable', 'string', 'max:255'],
            'consentimento_lgpd' => $consentRules,
            'capturado_em' => ['nullable', 'date'],
            'observacoes' => ['nullable', 'string', 'max:5000'],
            'metadata' => ['nullable', 'array', 'max:50'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        try {
            $planoSugerido = $data['plano_sugerido'] ?? null;
            $valorEstimado = array_key_exists('valor_estimado', $data)
                ? $data['valor_estimado']
                : $this->valorPorPlano($planoSugerido);

            $payload = [
                'empresa' => $data['empresa'] ?? null,
                'contato' => $data['contato'],
                'telefone' => $data['telefone'],
                'email' => $data['email'] ?? null,
                'produto_interesse' => $data['produto_interesse'],
                'plano_sugerido' => $planoSugerido,
                'valor_estimado' => $valorEstimado,
                'origem_lead' => $data['origem_lead'] ?? 'Site',
                'status_negociacao' => 'Novo',
                'proxima_acao' => 'Fazer follow-up',
                'observacoes' => $data['observacoes'] ?? null,
            ];

            $table = (new Lead())->getTable();

            $optionalColumns = [
                'cidade',
                'estado',
                'pagina_origem',
                'campanha',
                'consentimento_lgpd',
                'capturado_em',
                'metadata',
            ];

            foreach ($optionalColumns as $column) {
                if (Schema::hasColumn($table, $column) && array_key_exists($column, $data)) {
                    $payload[$column] = $data[$column];
                }
            }

            $externalId = $data['external_id'] ?? null;
            $supportsExternalId = Schema::hasColumn($table, 'external_id');

            if ($externalId && $supportsExternalId) {
                $lead = Lead::updateOrCreate(
                    ['external_id' => $externalId],
                    $payload,
                );
            } else {
                $lead = Lead::create($payload);
            }

            $created = $lead->wasRecentlyCreated;

            return response()->json([
                'success' => true,
                'message' => $created ? 'Lead recebido com sucesso.' : 'Lead já recebido anteriormente.',
                'lead_id' => $lead->id,
                'external_id' => $supportsExternalId ? $lead->external_id : null,
                'duplicate' => ! $created,
            ], $created ? 201 : 200);
        } catch (\Throwable $e) {
            Log::error('Erro ao capturar lead da API.', [
                'exception' => $e::class,
                'message' => $e->getMessage(),
                'external_id' => $data['external_id'] ?? null,
                'pagina_origem' => $data['pagina_origem'] ?? null,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ocorreu um erro interno ao processar o lead.',
            ], 500);
        }
    }

    private function valorPorPlano(?string $plano): ?float
    {
        if (! $plano) {
            return null;
        }

        return match ($plano) {
            'Beta', 'Trial', 'Implantação' => 0.00,
            'Start' => 497.00,
            'Pro' => 997.00,
            'Enterprise', 'Governo' => 1500.00,
            'White Label' => 2500.00,
            'Agência' => 3500.00,
            'Sob proposta' => null,
            default => null,
        };
    }
}
