<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFinanceiroRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cliente_id' => ['required', 'integer'],
            'descricao' => ['required', 'string'],
            'valor' => ['nullable', 'numeric'],
            'vencimento' => ['nullable', 'date'],
            'status' => ['required', 'string']
        ];
    }
}
