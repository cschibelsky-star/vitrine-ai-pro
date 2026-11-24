<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketing_campaign_tasks', function (Blueprint $table): void {
            $table->string('via_status', 30)->default('not_submitted')->after('status');
            $table->text('via_note')->nullable()->after('via_status');
            $table->foreignId('via_reviewed_by')->nullable()->after('via_note')->constrained('users')->nullOnDelete();
            $table->timestamp('via_reviewed_at')->nullable()->after('via_reviewed_by');
            $table->index(['company_id', 'via_status'], 'marketing_tasks_via_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('marketing_campaign_tasks', function (Blueprint $table): void {
            $table->dropIndex('marketing_tasks_via_status_index');
            $table->dropForeign(['via_reviewed_by']);
            $table->dropColumn(['via_status', 'via_note', 'via_reviewed_by', 'via_reviewed_at']);
        });
    }
};
