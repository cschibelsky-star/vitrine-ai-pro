<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('via_operational_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->string('domain', 80);
            $table->string('source', 120);
            $table->string('project_id', 160)->nullable();
            $table->string('status', 40)->default('unknown');
            $table->json('metrics')->nullable();
            $table->json('evidence')->nullable();
            $table->string('fingerprint', 64)->index();
            $table->timestamp('collected_at')->index();
            $table->timestamps();
            $table->index(['domain', 'project_id', 'source']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('via_operational_snapshots');
    }
};
