<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registro_id' => ['nullable', 'integer'],
            'nome' => ['nullable', 'string'],
            'arquivo' => ['nullable', 'string'],
            'status' => ['nullable', 'string']
        ];
    }
}
