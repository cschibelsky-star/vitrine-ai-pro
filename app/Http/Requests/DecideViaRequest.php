<?php

/**
 * O QUE É: contrato de entrada da DECISÃO DA VIA.
 * COMO FUNCIONA: valida a decisão, o parecer e — ⭐⭐ o mais importante — a
 * IDENTIDADE DO SUPERVISOR que decide.
 * FUNÇÃO NO SISTEMA: ⭐⭐ impedir, na borda, que uma decisão entre sem supervisor
 * identificado. ⭐ É a primeira das três defesas da independência da VIA.
 *
 * ⭐⭐ POR QUE O SUPERVISOR VEM NO CORPO, E NÃO DO TOKEN:
 *
 * A VIA é uma camada supervisora com identidade própria — é ela quem responde
 * pela supervisão, e não o usuário que está logado na tela. ⭐ Por isso o
 * supervisor é EXPLICITO na requisição, e validado contra o cadastro de
 * supervisores.
 *
 * ⭐⭐ E A VALIDAÇÃO NÃO É SÓ DE FORMATO: o supervisor precisa existir, estar
 * ativo e ter a permissão de supervisionar. ⭐ Um id inventado não passa.
 */

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class DecideViaRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->is_active ?? true) && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'decision' => ['required', 'in:approve,request_changes'],
            'note' => ['nullable', 'string', 'max:5000'],

            // ⭐⭐ O SUPERVISOR DA VIA — obrigatório e validado.
            'via_supervisor_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * ⭐⭐⭐ A VALIDAÇÃO DO SUPERVISOR — a segunda defesa da independência.
     *
     * ⭐⭐ O `exists:users,id` só garante que o id existe. ⭐ Aqui a garantia é
     * mais forte: o supervisor precisa estar ATIVO e ter a permissão de
     * supervisionar. ⭐ Um usuário desativado, ou sem permissão, não pode
     * Supervisionar — mesmo que o id esteja correto.
     *
     * ⭐⭐ E O `authorize()` acima é a PRIMEIRA defesa: só um admin ativo chega
     * aqui. ⭐⭐ Três defesas, três camadas:
     *   1. a rota exige admin ativo;
     *   2. o request exige supervisor explícito;
     *   3. ⭐ aqui: o supervisor existe, está ativo e pode supervisionar.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $supervisorId = (int) $this->input('via_supervisor_id');

            if ($supervisorId <= 0) {
                return;
            }

            $supervisor = User::find($supervisorId);

            if (! $supervisor) {
                throw ValidationException::withMessages([
                    'via_supervisor_id' => 'O supervisor da VIA não existe.',
                ]);
            }

            if (! ($supervisor->is_active ?? true)) {
                throw ValidationException::withMessages([
                    'via_supervisor_id' => 'O supervisor da VIA está inativo.',
                ]);
            }

            if (! $supervisor->isAdmin()) {
                throw ValidationException::withMessages([
                    'via_supervisor_id' => 'O supervisor da VIA não tem permissão de supervisão.',
                ]);
            }
        });
    }
}
