<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer'],
            'nome' => ['nullable', 'string'],
            'especie' => ['nullable', 'string'],
            'raca' => ['nullable', 'string'],
            'status' => ['nullable', 'string']
        ];
    }
}
