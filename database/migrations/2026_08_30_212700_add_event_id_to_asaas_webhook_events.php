<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('asaas_webhook_events')) {
            return;
        }

        Schema::table('asaas_webhook_events', function (Blueprint $table) {
            if (! Schema::hasColumn('asaas_webhook_events', 'event_id')) {
                $table->string('event_id', 160)->nullable()->unique()->after('id');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('asaas_webhook_events') || ! Schema::hasColumn('asaas_webhook_events', 'event_id')) {
            return;
        }

        Schema::table('asaas_webhook_events', function (Blueprint $table) {
            $table->dropUnique(['event_id']);
            $table->dropColumn('event_id');
        });
    }
};
