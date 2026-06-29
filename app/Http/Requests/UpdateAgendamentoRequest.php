<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAgendamentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['nullable', 'integer'],
            'data_agendamento' => ['nullable', 'date'],
            'tipo' => ['nullable', 'string'],
            'observacoes' => ['nullable', 'string'],
            'status' => ['nullable', 'string']
        ];
    }
}
