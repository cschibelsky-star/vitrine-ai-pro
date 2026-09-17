<?php

namespace App\Services\Checkout;

use App\Models\Company;
use App\Models\License;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SocialCheckoutActivationService
{
    public function createOrder(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $planKey = (string) ($data['plan'] ?? '');
            $amountCents = (int) ($data['amount_cents'] ?? 0);
            $description = (string) ($data['description'] ?? '');
            $customer = (array) ($data['customer'] ?? []);

            if (! in_array($planKey, ['essencial', 'pro', 'premium'], true) || $amountCents <= 0) {
                throw new RuntimeException('Invalid social checkout order.');
            }

            $product = Product::query()
                ->where('status', 'Ativo')
                ->where('nome', 'like', '%Social%')
                ->first();

            if (! $product) {
                throw new RuntimeException('Vitrine Social Midia product not found in Core.');
            }

            $plan = Plan::query()
                ->where('product_id', $product->id)
                ->where('status', 'Ativo')
                ->where('nome', 'like', '%'.ucfirst($planKey).'%')
                ->first();

            if (! $plan) {
                throw new RuntimeException('Plan not found in Core: '.$planKey);
            }

            $email = trim((string) ($customer['email'] ?? ''));
            $name = trim((string) ($customer['name'] ?? $customer['company'] ?? ''));

            if ($email === '' || $name === '') {
                throw new RuntimeException('Customer name and email are required.');
            }

            $company = Company::query()->firstOrCreate(
                ['email' => $email],
                [
                    'nome' => $customer['company'] ?? $name,
                    'responsavel' => $name,
                    'telefone' => $customer['phone'] ?? null,
                    'produto_principal' => $product->nome,
                    'status' => 'Implantação',
                    'ambiente' => 'Homologação',
                    'status_implantacao' => 'Não iniciado',
                    'tipo_instancia' => 'cliente',
                ],
            );

            $orderNsu = 'VSM-'.Str::upper((string) Str::ulid());
            $amount = round($amountCents / 100, 2);

            $payment = Payment::query()->create([
                'company_id' => $company->id,
                'product_id' => $product->id,
                'plan_id' => $plan->id,
                'tipo_cobranca' => 'anual',
                'descricao' => $description !== '' ? $description : $plan->nome,
                'valor' => $amount,
                'vencimento' => now()->toDateString(),
                'status' => 'Aberto',
                'forma_pagamento' => 'InfinitePay',
                'referencia_externa' => $orderNsu,
            ]);

            $license = License::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'product_id' => $product->id,
                    'plan_id' => $plan->id,
                ],
                [
                    'plano' => $plan->nome,
                    'valor' => $amount,
                    'inicio' => null,
                    'vencimento' => null,
                    'status' => 'Homologação',
                ],
            );

            return [
                'order_nsu' => $orderNsu,
                'payment_id' => $payment->id,
                'license_id' => $license->id,
                'company_id' => $company->id,
                'product_id' => $product->id,
                'plan_id' => $plan->id,
            ];
        });
    }

    public function confirmPaid(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $orderNsu = (string) ($data['order_nsu'] ?? '');

            $payment = Payment::query()
                ->where('referencia_externa', $orderNsu)
                ->lockForUpdate()
                ->first();

            if (! $payment) {
                throw new RuntimeException('Payment not found for order_nsu.');
            }

            if ($payment->status !== 'Pago') {
                $payment->update([
                    'status' => 'Pago',
                    'data_pagamento' => now()->toDateString(),
                    'forma_pagamento' => 'InfinitePay',
                    'observacao' => trim((string) ($data['transaction_nsu'] ?? '')) !== ''
                        ? 'InfinitePay transaction: '.(string) $data['transaction_nsu']
                        : $payment->observacao,
                ]);
            }

            $license = License::query()
                ->where('company_id', $payment->company_id)
                ->where('product_id', $payment->product_id)
                ->when($payment->plan_id, fn ($q) => $q->where('plan_id', $payment->plan_id))
                ->first();

            if ($license) {
                $license->update([
                    'status' => 'Ativa',
                    'inicio' => $license->inicio ?: now()->toDateString(),
                    'vencimento' => $license->vencimento ?: now()->addYear()->toDateString(),
                ]);
            }

            $company = Company::query()->find($payment->company_id);
            if ($company) {
                $company->update([
                    'status' => 'Implantação',
                    'ambiente' => 'Homologação',
                    'status_implantacao' => 'Em implantação',
                ]);
            }

            return [
                'ok' => true,
                'payment_id' => $payment->id,
                'license_id' => $license?->id,
                'company_id' => $payment->company_id,
                'provisioning_required' => true,
            ];
        });
    }
}
