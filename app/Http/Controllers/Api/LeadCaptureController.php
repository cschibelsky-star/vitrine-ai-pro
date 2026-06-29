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
        $validator = Validator::make($request->all(), [
            'empresa' => ['nullable', 'string', 'max:255'],
            'contato' => ['required', 'string', 'max:255'],
            'telefone' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'cidade' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'max:2'],
            'produto_interesse' => ['required', 'string', 'max:255'],
            'plano_sugerido' => ['nullable', 'string', 'max:255'],
            'valor_estimado' => ['nullable', 'numeric'],
            'origem_lead' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string'],
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

            $tabelaLeads = (new Lead())->getTable();

            if (Schema::hasColumn($tabelaLeads, 'cidade') && isset($data['cidade'])) {
                $payload['cidade'] = $data['cidade'];
            }

            if (Schema::hasColumn($tabelaLeads, 'estado') && isset($data['estado'])) {
                $payload['estado'] = $data['estado'];
            }

            $lead = Lead::create($payload);

            return response()->json([
                'success' => true,
                'message' => 'Lead recebido com sucesso.',
                'lead_id' => $lead->id,
            ], 201);
        } catch (\Throwable $e) {
            Log::error('Erro ao capturar lead da API: '.$e->getMessage(), [
                'request' => $request->except(['password', 'token']),
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
