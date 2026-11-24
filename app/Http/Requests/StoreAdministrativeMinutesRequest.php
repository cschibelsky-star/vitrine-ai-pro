<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdministrativeMinutesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && ($user->is_active ?? true) && $user->isAdmin();
    }

    public function rules(): array
    {
        return [
            'meeting_title' => ['required', 'string', 'min:4', 'max:180'],
            'notes' => ['required', 'string', 'min:20', 'max:20000'],
            'decisions' => ['nullable', 'array', 'max:50'],
            'decisions.*' => ['string', 'min:3', 'max:500'],
            'open_items' => ['nullable', 'array', 'max:50'],
            'open_items.*' => ['string', 'min:3', 'max:500'],
            'priority' => ['required', 'in:low,normal,high'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return [
            'meeting_title' => 'título da reunião',
            'notes' => 'anotações',
            'decisions' => 'decisões',
            'open_items' => 'pendências',
            'idempotency_key' => 'chave de idempotência',
        ];
    }
}
