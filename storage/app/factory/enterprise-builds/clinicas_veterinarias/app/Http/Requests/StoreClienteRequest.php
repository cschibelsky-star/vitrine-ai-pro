<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => ['required', 'string'],
            'documento' => ['nullable', 'string'],
            'telefone' => ['nullable', 'string'],
            'email' => ['nullable', 'string'],
            'status' => ['required', 'string']
        ];
    }
}
