<?php

namespace App\Services\Asaas;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AsaasLicenseService
{
    public function processWebhook(array $payload): array
    {
        $event = (string) data_get($payload, 'event');
        $payment = (array) data_get($payload, 'payment', []);
        $subscription = (array) data_get($payload, 'subscription', []);

        $asaasPaymentId = data_get($payment, 'id');
        $asaasSubscriptionId = data_get($payment, 'subscription') ?: data_get($subscription, 'id');
        $externalReference = data_get($payment, 'externalReference') ?: data_get($subscription, 'externalReference');
        $asaasStatus = (string) data_get($payment, 'status');

        $foundPayment = $this->findPayment($asaasPaymentId, $externalReference);
        $foundSubscription = $this->findSubscription($asaasSubscriptionId, $externalReference);

        if ($foundPayment) {
            DB::table('payments')->where('id', $foundPayment->id)->update($this->safeData('payments', [
                'status' => $this->mapPaymentStatus($event, $asaasStatus),
                'asaas_payment_id' => $asaasPaymentId,
                'asaas_subscription_id' => $asaasSubscriptionId,
                'asaas_customer_id' => data_get($payment, 'customer'),
                'asaas_status' => $asaasStatus,
                'asaas_event' => $event,
                'asaas_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'paid_at' => $this->isPaid($event, $asaasStatus) ? now() : null,
                'external_reference' => $externalReference,
            ], false));
        }

        if ($foundSubscription) {
            DB::table('subscriptions')->where('id', $foundSubscription->id)->update($this->safeData('subscriptions', [
                'asaas_subscription_id' => $asaasSubscriptionId,
                'asaas_customer_id' => data_get($payment, 'customer') ?: data_get($subscription, 'customer'),
                'external_reference' => $externalReference,
                'status' => $this->mapSubscriptionStatus($event, $asaasStatus),
                'next_due_date' => data_get($subscription, 'nextDueDate') ?: data_get($payment, 'dueDate'),
                'asaas_payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'activated_at' => $this->isPaid($event, $asaasStatus) ? now() : ($foundSubscription->activated_at ?? null),
                'suspended_at' => $this->isSuspended($event, $asaasStatus) ? now() : ($foundSubscription->suspended_at ?? null),
                'cancelled_at' => $this->isCancelled($event, $asaasStatus) ? now() : ($foundSubscription->cancelled_at ?? null),
            ], false));
        }

        if ($this->isPaid($event, $asaasStatus)) {
            $this->activateLicense($foundPayment, $foundSubscription);
        }

        if ($this->isSuspended($event, $asaasStatus) || $this->isCancelled($event, $asaasStatus)) {
            $this->suspendLicense($foundPayment, $foundSubscription, $this->isCancelled($event, $asaasStatus) ? 'Cancelada' : 'Suspensa');
        }

        $this->logEvent($event, $asaasPaymentId, $asaasSubscriptionId, $externalReference, $payload, true);

        return [
            'ok' => true,
            'event' => $event,
            'payment_found' => (bool) $foundPayment,
            'subscription_found' => (bool) $foundSubscription,
        ];
    }

    protected function findPayment($asaasPaymentId, $externalReference)
    {
        if (! Schema::hasTable('payments')) return null;

        if ($asaasPaymentId && Schema::hasColumn('payments', 'asaas_payment_id')) {
            $row = DB::table('payments')->where('asaas_payment_id', $asaasPaymentId)->first();
            if ($row) return $row;
        }

        if ($externalReference && Schema::hasColumn('payments', 'external_reference')) {
            $row = DB::table('payments')->where('external_reference', $externalReference)->first();
            if ($row) return $row;
        }

        if ($externalReference && is_numeric($externalReference)) {
            return DB::table('payments')->where('id', (int) $externalReference)->first();
        }

        return null;
    }

    protected function findSubscription($asaasSubscriptionId, $externalReference)
    {
        if (! Schema::hasTable('subscriptions')) return null;

        if ($asaasSubscriptionId) {
            $row = DB::table('subscriptions')->where('asaas_subscription_id', $asaasSubscriptionId)->first();
            if ($row) return $row;
        }

        if ($externalReference) {
            return DB::table('subscriptions')->where('external_reference', $externalReference)->first();
        }

        return null;
    }

    protected function activateLicense($payment, $subscription): void
    {
        if (! Schema::hasTable('licenses')) return;

        $query = DB::table('licenses');

        if ($payment && Schema::hasColumn('licenses', 'payment_id')) {
            $query->orWhere('payment_id', $payment->id);
        }

        if ($subscription && Schema::hasColumn('licenses', 'subscription_id')) {
            $query->orWhere('subscription_id', $subscription->id);
        }

        if ($payment && isset($payment->company_id)) {
            $query->orWhere('company_id', $payment->company_id);
        }

        if ($subscription && isset($subscription->company_id)) {
            $query->orWhere('company_id', $subscription->company_id);
        }

        $query->update($this->safeData('licenses', [
            'status' => 'Ativa',
            'is_active' => 1,
            'activated_at' => now(),
            'suspended_at' => null,
            'cancelled_at' => null,
        ], false));
    }

    protected function suspendLicense($payment, $subscription, string $status): void
    {
        if (! Schema::hasTable('licenses')) return;

        $companyId = $payment->company_id ?? $subscription->company_id ?? null;
        if (! $companyId) return;

        DB::table('licenses')->where('company_id', $companyId)->update($this->safeData('licenses', [
            'status' => $status,
            'is_active' => 0,
            'suspended_at' => $status === 'Suspensa' ? now() : null,
            'cancelled_at' => $status === 'Cancelada' ? now() : null,
        ], false));
    }

    protected function isPaid(string $event, string $status): bool
    {
        return in_array($event, ['PAYMENT_RECEIVED', 'PAYMENT_CONFIRMED'], true)
            || in_array($status, ['RECEIVED', 'CONFIRMED'], true);
    }

    protected function isSuspended(string $event, string $status): bool
    {
        return in_array($event, ['PAYMENT_OVERDUE'], true) || $status === 'OVERDUE';
    }

    protected function isCancelled(string $event, string $status): bool
    {
        return in_array($event, ['PAYMENT_DELETED', 'SUBSCRIPTION_DELETED'], true)
            || in_array($status, ['DELETED', 'CANCELED', 'CANCELLED'], true);
    }

    protected function mapPaymentStatus(string $event, string $status): string
    {
        if ($this->isPaid($event, $status)) return 'Pago';
        if ($this->isSuspended($event, $status)) return 'Vencido';
        if ($this->isCancelled($event, $status)) return 'Cancelado';
        return 'Pendente';
    }

    protected function mapSubscriptionStatus(string $event, string $status): string
    {
        if ($this->isPaid($event, $status)) return 'Ativa';
        if ($this->isSuspended($event, $status)) return 'Suspensa';
        if ($this->isCancelled($event, $status)) return 'Cancelada';
        return 'Pendente';
    }

    protected function logEvent($event, $paymentId, $subscriptionId, $externalReference, array $payload, bool $processed): void
    {
        if (! Schema::hasTable('asaas_webhook_events')) return;

        DB::table('asaas_webhook_events')->insert($this->safeData('asaas_webhook_events', [
            'event' => $event,
            'asaas_payment_id' => $paymentId,
            'asaas_subscription_id' => $subscriptionId,
            'external_reference' => $externalReference,
            'processed' => $processed,
            'status' => $processed ? 'Processado' : 'Ignorado',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]));
    }

    protected function safeData(string $table, array $data, bool $withTimestamps = true): array
    {
        $cols = Schema::getColumnListing($table);
        $out = [];

        foreach ($data as $k => $v) {
            if (in_array($k, $cols, true)) $out[$k] = $v;
        }

        if ($withTimestamps && in_array('created_at', $cols, true)) $out['created_at'] = now();
        if ($withTimestamps && in_array('updated_at', $cols, true)) $out['updated_at'] = now();

        return $out;
    }
}
