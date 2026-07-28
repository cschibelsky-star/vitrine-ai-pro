<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Throwable;

class VendedoriaProNewsLeadController extends Controller
{
    public function store(Request $request)
    {
        if ($request->filled('website')) {
            return response()->json([
                'success' => true,
                'message' => 'Lead recebido com sucesso.',
            ], 201);
        }

        $validator = Validator::make($request->all(), [
            'contato' => ['required', 'string', 'max:150'],
            'empresa' => ['nullable', 'string', 'max:190'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:190'],
            'cidade' => ['nullable', 'string', 'max:120'],
            'estado' => ['nullable', 'string', 'max:2'],
            'plano_sugerido' => ['nullable', 'string', 'max:120'],
            'pagina_origem' => ['nullable', 'string', 'max:255'],
            'observacoes' => ['nullable', 'string', 'max:2000'],
            'consentimento_lgpd' => ['accepted'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Dados inválidos.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $plano = $request->input('plano_sugerido') ?: 'Portal Automatizado';

        $valorEstimado = match ($plano) {
            'Portal Local' => 497,
            'Portal Automatizado' => 997,
            'Licenciamento Institucional' => 1500,
            default => null,
        };

        $dados = [
            'external_id' => 'vendedoria-pro-news-' . Str::uuid(),
            'empresa' => $request->input('empresa') ?: 'Lead VendedorIA Pro News',
            'contato' => $request->input('contato'),
            'telefone' => $request->input('telefone'),
            'email' => $request->input('email'),
            'cidade' => $request->input('cidade'),
            'estado' => $request->input('estado') ?: 'SP',
            'produto_interesse' => 'VitrineIA Pro News',
            'plano_sugerido' => $plano,
            'valor_estimado' => $valorEstimado,
            'origem_lead' => 'VendedorIA Pro News',
            'pagina_origem' => $request->input('pagina_origem') ?: 'Widget VendedorIA Pro News',
            'campanha' => 'VendedorIA Pro News 1.1',
            'consentimento_lgpd' => true,
            'observacoes' => $request->input('observacoes') ?: 'Lead captado pelo VendedorIA Pro News.',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $insert = [];

        foreach ($dados as $coluna => $valor) {
            if (Schema::hasColumn('leads', $coluna)) {
                $insert[$coluna] = $valor;
            }
        }

        try {
            $leadId = DB::table('leads')->insertGetId($insert);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'Não foi possível registrar o lead no momento. Tente novamente mais tarde.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lead recebido com sucesso.',
            'lead_id' => $leadId,
        ], 201);
    }
}
