<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProntuarioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['nullable', 'integer'],
            'descricao' => ['nullable', 'string'],
            'diagnostico' => ['nullable', 'string'],
            'status' => ['nullable', 'string']
        ];
    }
}
