<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_tasks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $t->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $t->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('demand_id')->nullable()->constrained('assessor_demands')->nullOnDelete();
            $t->string('title', 200);
            $t->text('description')->nullable();
            $t->string('status', 24)->default('pending');
            $t->date('due_date')->nullable();
            $t->timestamps();
            $t->index(['cabinet_id', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_tasks');
    }
};
