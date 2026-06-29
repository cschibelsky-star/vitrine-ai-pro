<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProntuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['required', 'integer'],
            'descricao' => ['nullable', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'status' => ['required', 'string']
        ];
    }
}
