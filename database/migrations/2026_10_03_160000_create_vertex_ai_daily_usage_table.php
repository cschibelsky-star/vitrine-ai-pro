<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vertex_ai_daily_usage', function (Blueprint $table) {
            $table->string('project', 128);
            $table->date('usage_date');
            $table->unsignedInteger('requests')->default(0);
            $table->primary(['project', 'usage_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vertex_ai_daily_usage');
    }
};
