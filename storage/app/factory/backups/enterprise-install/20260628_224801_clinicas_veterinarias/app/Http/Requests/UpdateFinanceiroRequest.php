<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFinanceiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['nullable', 'integer'],
            'descricao' => ['nullable', 'string'],
            'valor' => ['nullable', 'numeric'],
            'vencimento' => ['nullable', 'date'],
            'status' => ['nullable', 'string']
        ];
    }
}
