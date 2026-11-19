<?php

/**
 * O QUE É: contrato de entrada da revisão de uma ata administrativa.
 * COMO FUNCIONA: valida aprovação ou solicitação de ajustes e a observação do gestor.
 * FUNÇÃO NO SISTEMA: impede decisões inválidas antes do serviço transacional.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReviewAdministrativeMinutesRequest extends FormRequest
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
        ];
    }
}
