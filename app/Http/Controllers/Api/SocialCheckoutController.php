<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Checkout\SocialCheckoutActivationService;
use Illuminate\Http\Request;

class SocialCheckoutController extends Controller
{
    public function order(Request $request, SocialCheckoutActivationService $service)
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:essencial,pro,premium'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:255'],
            'customer.name' => ['required', 'string', 'max:160'],
            'customer.email' => ['required', 'email', 'max:190'],
            'customer.phone' => ['nullable', 'string', 'max:30'],
            'customer.company' => ['nullable', 'string', 'max:190'],
        ]);

        return response()->json($service->createOrder($validated), 201);
    }

    public function paid(Request $request, SocialCheckoutActivationService $service)
    {
        $validated = $request->validate([
            'order_nsu' => ['required', 'string', 'max:100'],
            'transaction_nsu' => ['nullable', 'string', 'max:150'],
        ]);

        return response()->json($service->confirmPaid($validated));
    }
}
