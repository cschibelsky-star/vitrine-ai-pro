<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro_id' => ['required', 'integer'],
            'nome' => ['required', 'string'],
            'arquivo' => ['nullable', 'string'],
            'status' => ['required', 'string']
        ];
    }
}
