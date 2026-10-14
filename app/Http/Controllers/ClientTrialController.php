<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Services\Licensing\TrialLicenseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

final class ClientTrialController extends Controller
{
    public function store(Request $request, TrialLicenseService $trials): RedirectResponse
    {
        $user = $request->user();

        // Fail closed for unverified, inactive, admin or unlinked accounts.
        abort_unless(
            $user
            && $user->isClient()
            && $user->is_active
            && $user->company_id
            && $user->email_verified_at,
            403
        );

        $data = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
        ]);

        $plan = Plan::query()->findOrFail($data['plan_id']);
        $trials->enroll($user->company, $plan);

        Log::info('Client trial enrolled', [
            'company_id' => $user->company_id,
            'product_id' => $plan->product_id,
            'plan_id' => $plan->id,
        ]);

        return redirect()->route('client.portal')
            ->with('status', 'Avaliação gratuita ativada. Consulte o prazo no painel.');
    }
}
