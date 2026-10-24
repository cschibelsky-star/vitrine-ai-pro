<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_cabinets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->unique()->constrained('companies')->restrictOnDelete();
            $table->string('name', 160);
            $table->string('mandate_type', 40);
            $table->timestamps();
        });
        Schema::create('assessor_cabinet_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('role', 24);
            $table->timestamps();
            $table->unique(['cabinet_id', 'user_id']);
        });
        Schema::create('assessor_demands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject', 200);
            $table->text('description');
            $table->string('status', 24)->default('open');
            $table->timestamps();
            $table->index(['cabinet_id', 'status']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assessor_demands');
        Schema::dropIfExists('assessor_cabinet_members');
        Schema::dropIfExists('assessor_cabinets');
    }
};
