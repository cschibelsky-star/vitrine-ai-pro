<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'plan_id')) {
                $table->foreignId('plan_id')->nullable()->after('product_id')->constrained('plans')->nullOnDelete();
            }
        });
    }
    public function down(): void
    {
        Schema::table('licenses', function (Blueprint $table) {
            if (Schema::hasColumn('licenses', 'plan_id')) { $table->dropForeign(['plan_id']); $table->dropColumn('plan_id'); }
        });
    }
};
