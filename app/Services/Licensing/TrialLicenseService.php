<?php

namespace App\Services\Licensing;

use App\Models\Company;
use App\Models\License;
use App\Models\Plan;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class TrialLicenseService
{
    /**
     * Internal-only operation: caller must authenticate and verify authorization.
     * A company can have at most one license per product via this enrollment flow.
     */
    public function enroll(Company $company, Plan $plan): License
    {
        if ($plan->ciclo_cobranca !== 'trial' || $plan->status !== 'Ativo' || ! $plan->product_id) {
            throw ValidationException::withMessages([
                'plan_id' => 'Plano de avaliação indisponível.',
            ]);
        }

        return DB::transaction(function () use ($company, $plan): License {
            // Serialize simultaneous enrollment requests for the same company.
            Company::query()->whereKey($company->getKey())->lockForUpdate()->firstOrFail();

            // Fail closed for existing licenses, including expired and cancelled ones.
            // This prevents repeated trials and accidental overwrites of paid contracts.
            if (License::query()
                ->where('company_id', $company->getKey())
                ->where('product_id', $plan->product_id)
                ->exists()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Já existe uma licença para este cliente e produto.',
                ]);
            }

            $start = CarbonImmutable::today();
            return License::query()->create([
                'company_id' => $company->getKey(),
                'product_id' => $plan->product_id,
                'plan_id' => $plan->getKey(),
                'plano' => $plan->nome,
                'valor' => 0,
                'status' => 'Trial',
                'inicio' => $start->toDateString(),
                'vencimento' => $start->addDays(30)->toDateString(),
            ]);
        }, 3);
    }

    public function isTrialAccessAllowed(License $license, ?CarbonImmutable $today = null): bool
    {
        $today ??= CarbonImmutable::today();

        return $license->status === 'Trial'
            && $license->inicio !== null
            && $license->vencimento !== null
            && $today->greaterThanOrEqualTo(CarbonImmutable::parse($license->inicio)->startOfDay())
            && $today->lessThan(CarbonImmutable::parse($license->vencimento)->startOfDay());
    }
}
