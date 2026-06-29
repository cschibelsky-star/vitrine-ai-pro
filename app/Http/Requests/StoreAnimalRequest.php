<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAnimalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer'],
            'nome' => ['required', 'string'],
            'especie' => ['nullable', 'string'],
            'raca' => ['nullable', 'string'],
            'status' => ['required', 'string']
        ];
    }
}
