<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_consumptions', function (Blueprint $table) {
            $table->string('project_id', 120)->nullable()->after('ai_agent_id');
            $table->index(['project_id', 'consumption_date'], 'ai_consumptions_project_date_index');
        });

        DB::table('ai_consumptions')
            ->whereNull('project_id')
            ->whereNotNull('metadata')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $metadata = json_decode((string) $row->metadata, true);
                    $projectId = is_array($metadata) ? ($metadata['project_id'] ?? null) : null;

                    if (is_string($projectId) && $projectId !== '') {
                        DB::table('ai_consumptions')
                            ->where('id', $row->id)
                            ->update(['project_id' => $projectId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('ai_consumptions', function (Blueprint $table) {
            $table->dropIndex('ai_consumptions_project_date_index');
            $table->dropColumn('project_id');
        });
    }
};
