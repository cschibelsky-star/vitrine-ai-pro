<?php

declare(strict_types=1);

namespace App\Services\Ai\Chat;

use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

/**
 * Transactional spending ledger for a pre-provisioned DB schema.
 *
 * Required tables (not created by this change):
 * ai_budget_accounts: id, scope (unique), currency, limit_minor,
 *                    spent_minor, reserved_minor, created_at, updated_at
 * ai_budget_entries: id, account_id, request_id, state,
 *                    reserved_minor, charged_minor, created_at, updated_at
 * UNIQUE(account_id, request_id); foreign key account_id -> accounts.id.
 * All amounts are integer minor units. No paid API calls.
 */
final class TransactionalBudgetLedger
{
    public function reserve(string $scope, string $currency, string $requestId, int $amountMinor): array
    {
        $this->validate($scope, $currency, $requestId, $amountMinor, false);
        return DB::transaction(function () use ($scope, $currency, $requestId, $amountMinor): array {
            $account = DB::table('ai_budget_accounts')->where('scope', $scope)->lockForUpdate()->first();
            if (! $account || $account->currency !== $currency) {
                return ['allowed' => false, 'reason' => 'account_missing_or_currency_mismatch'];
            }

            $existing = DB::table('ai_budget_entries')
                ->where('account_id', $account->id)->where('request_id', $requestId)
                ->lockForUpdate()->first();
            if ($existing) {
                return ['allowed' => false, 'reason' => 'duplicate_request_id'];
            }

            if ($account->spent_minor + $account->reserved_minor + $amountMinor > $account->limit_minor) {
                return ['allowed' => false, 'reason' => 'budget_exceeded'];
            }
            DB::table('ai_budget_entries')->insert([
                'account_id' => $account->id, 'request_id' => $requestId,
                'state' => 'reserved', 'reserved_minor' => $amountMinor,
                'charged_minor' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('ai_budget_accounts')->where('id', $account->id)
                ->update(['reserved_minor' => $account->reserved_minor + $amountMinor, 'updated_at' => now()]);
            return ['allowed' => true, 'reason' => 'reserved', 'reserved_minor' => $amountMinor];
        }, 3);
    }

    /**
     * Finalizes a reservation ONLY with a verified provider charge.
     * Actual charges exceeding reservation are refused and require manual reconciliation.
     */
    public function settle(string $scope, string $currency, string $requestId, int $actualMinor, bool $receiptVerified): array
    {
        $this->validate($scope, $currency, $requestId, $actualMinor, true);
        if (! $receiptVerified) {
            return ['settled' => false, 'reason' => 'provider_receipt_unverified'];
        }
        return DB::transaction(function () use ($scope, $currency, $requestId, $actualMinor): array {
            $account = DB::table('ai_budget_accounts')->where('scope', $scope)->lockForUpdate()->first();
            if (! $account || $account->currency !== $currency) {
                return ['settled' => false, 'reason' => 'account_missing_or_currency_mismatch'];
            }
            $entry = DB::table('ai_budget_entries')
                ->where('account_id', $account->id)->where('request_id', $requestId)
                ->lockForUpdate()->first();
            if (! $entry || $entry->state !== 'reserved') {
                return ['settled' => false, 'reason' => 'reservation_missing_or_finalized'];
            }
            if ($actualMinor > $entry->reserved_minor) {
                return ['settled' => false, 'reason' => 'charge_exceeds_reservation'];
            }

            DB::table('ai_budget_entries')->where('id', $entry->id)->update([
                'state' => 'settled', 'charged_minor' => $actualMinor, 'reserved_minor' => 0,
                'updated_at' => now(),
            ]);
            DB::table('ai_budget_accounts')->where('id', $account->id)->update([
                'spent_minor' => $account->spent_minor + $actualMinor,
                'reserved_minor' => $account->reserved_minor - $entry->reserved_minor,
                'updated_at' => now(),
            ]);
            return ['settled' => true, 'reason' => 'verified_charge_recorded',
                'actual_cost_minor' => $actualMinor];
        }, 3);
    }

    /**
     * No receipt is NOT proof of zero cost. Only a trusted provider cancellation
     * or explicitly verified zero-charge outcome may release a reservation.
     */
    public function cancel(string $scope, string $currency, string $requestId, bool $zeroChargeVerified): array
    {
        $this->validate($scope, $currency, $requestId, 0, true);
        if (! $zeroChargeVerified) {
            return ['released' => false, 'reason' => 'zero_charge_unverified'];
        }
        return DB::transaction(function () use ($scope, $currency, $requestId): array {
            $account = DB::table('ai_budget_accounts')->where('scope', $scope)->lockForUpdate()->first();
            if (! $account || $account->currency !== $currency) {
                return ['released' => false, 'reason' => 'account_missing_or_currency_mismatch'];
            }
            $entry = DB::table('ai_budget_entries')
                ->where('account_id', $account->id)->where('request_id', $requestId)
                ->lockForUpdate()->first();
            if (! $entry || $entry->state !== 'reserved') {
                return ['released' => false, 'reason' => 'reservation_missing_or_finalized'];
            }
            DB::table('ai_budget_entries')->where('id', $entry->id)->update([
                'state' => 'cancelled', 'reserved_minor' => 0, 'updated_at' => now(),
            ]);
            DB::table('ai_budget_accounts')->where('id', $account->id)->update([
                'reserved_minor' => $account->reserved_minor - $entry->reserved_minor, 'updated_at' => now(),
            ]);
            return ['released' => true, 'reason' => 'verified_zero_charge'];
        }, 3);
    }

    private function validate(string $scope, string $currency, string $requestId, int $amount, bool $allowZero): void
    {
        if (trim($scope) === '' || trim($requestId) === '' ||
            ! in_array($currency, ['BRL', 'USD'], true) ||
            $amount < 0 || (! $allowZero && $amount === 0)) {
            throw new InvalidArgumentException('Invalid budget ledger arguments.');
        }
    }
}
