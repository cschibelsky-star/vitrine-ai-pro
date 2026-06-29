<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payments')) {
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'asaas_payment_id')) $table->string('asaas_payment_id')->nullable()->index();
            if (! Schema::hasColumn('payments', 'asaas_customer_id')) $table->string('asaas_customer_id')->nullable()->index();
            if (! Schema::hasColumn('payments', 'asaas_status')) $table->string('asaas_status')->nullable();
            if (! Schema::hasColumn('payments', 'asaas_event')) $table->string('asaas_event')->nullable();
            if (! Schema::hasColumn('payments', 'external_reference')) $table->string('external_reference')->nullable()->index();
            if (! Schema::hasColumn('payments', 'asaas_payload')) $table->longText('asaas_payload')->nullable();
            if (! Schema::hasColumn('payments', 'paid_at')) $table->timestamp('paid_at')->nullable();
        });
    }

    public function down(): void
    {
        //
    }
};
