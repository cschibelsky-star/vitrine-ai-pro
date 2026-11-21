<?php

/**
 * O QUE É: contrato de entrada do resultado produzido para uma ata.
 * COMO FUNCIONA: valida o texto da ata, pendências e o funcionário responsável.
 * FUNÇÃO NO SISTEMA: permite registrar uma saída supervisionada sem disparar integrações externas.
 */

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrativeMinutesResultRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->is_active ?? true) && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'minutes_text' => ['required', 'string', 'min:30', 'max:50000'],
            'summary' => ['nullable', 'string', 'max:5000'],
            'decisions' => ['nullable', 'array', 'max:50'],
            'decisions.*' => ['string', 'min:3', 'max:500'],
            'action_items' => ['nullable', 'array', 'max:50'],
            'action_items.*' => ['string', 'min:3', 'max:500'],
            'unresolved_items' => ['nullable', 'array', 'max:50'],
            'unresolved_items.*' => ['string', 'min:3', 'max:500'],
            'agent_name' => ['required', 'string', 'min:2', 'max:120'],
            'source' => ['nullable', 'string', 'max:120'],
            'confidence' => ['nullable', 'in:needs_review,medium,high'],
        ];
    }
}
