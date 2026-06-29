<?php

namespace App\Filament\Widgets;

use App\Models\License;
use App\Models\Payment;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class OperationalAlertsWidget extends Widget
{
    protected static string $view = 'filament.widgets.operational-alerts-widget';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    protected function getLicenseExpirationColumn(): ?string
    {
        if (! Schema::hasTable('licenses')) {
            return null;
        }

        foreach (['expires_at', 'data_vencimento', 'vencimento', 'end_date', 'expires_date', 'valid_until'] as $column) {
            if (Schema::hasColumn('licenses', $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function getPaymentDueColumn(): ?string
    {
        if (! Schema::hasTable('payments')) {
            return null;
        }

        foreach (['due_date', 'vencimento', 'data_vencimento'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function getPaymentAmountColumn(): ?string
    {
        if (! Schema::hasTable('payments')) {
            return null;
        }

        foreach (['amount', 'valor', 'value'] as $column) {
            if (Schema::hasColumn('payments', $column)) {
                return $column;
            }
        }

        return null;
    }

    protected function getViewData(): array
    {
        $licensesExpiring = 0;
        $paymentsOpen = 0;
        $paymentsOverdue = 0;

        if (class_exists(License::class)) {
            $expirationColumn = $this->getLicenseExpirationColumn();

            if ($expirationColumn) {
                $licensesExpiring = License::query()
                    ->whereDate($expirationColumn, '>=', Carbon::today())
                    ->whereDate($expirationColumn, '<=', Carbon::today()->addDays(15))
                    ->count();
            }
        }

        if (class_exists(Payment::class) && Schema::hasTable('payments')) {
            $paymentsOpen = Payment::query()
                ->whereIn('status', ['aberta', 'Aberta', 'pendente', 'Pendente', 'open'])
                ->count();

            $dueColumn = $this->getPaymentDueColumn();

            if ($dueColumn) {
                $paymentsOverdue = Payment::query()
                    ->whereDate($dueColumn, '<', Carbon::today())
                    ->whereNotIn('status', ['pago', 'Pago', 'paid'])
                    ->count();
            } else {
                $paymentsOverdue = Payment::query()
                    ->whereIn('status', ['vencida', 'Vencida', 'overdue'])
                    ->count();
            }
        }

        return [
            'licensesExpiring' => $licensesExpiring,
            'paymentsOpen' => $paymentsOpen,
            'paymentsOverdue' => $paymentsOverdue,
        ];
    }
}
