<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVacinaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'animal_id' => ['nullable', 'integer'],
            'nome' => ['nullable', 'string'],
            'data_aplicacao' => ['nullable', 'date'],
            'proxima_dose' => ['nullable', 'date'],
            'status' => ['nullable', 'string']
        ];
    }
}
