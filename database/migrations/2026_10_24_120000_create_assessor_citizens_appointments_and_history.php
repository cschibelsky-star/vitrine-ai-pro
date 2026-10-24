<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_citizens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $t->string('name', 160);
            $t->string('phone', 32)->nullable();
            $t->string('email', 180)->nullable();
            $t->string('neighborhood', 120)->nullable();
            $t->timestamps();
            $t->index(['cabinet_id', 'name']);
        });
        Schema::table('assessor_demands', function (Blueprint $t) {
            $t->foreignId('citizen_id')->nullable()->after('cabinet_id')
                ->constrained('assessor_citizens')->nullOnDelete();
        });
        Schema::create('assessor_demand_events', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $t->foreignId('demand_id')->constrained('assessor_demands')->cascadeOnDelete();
            $t->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $t->string('event', 40);
            $t->string('note', 500)->nullable();
            $t->timestamps();
        });
        Schema::create('assessor_appointments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('cabinet_id')->constrained('assessor_cabinets')->cascadeOnDelete();
            $t->foreignId('citizen_id')->nullable()->constrained('assessor_citizens')->nullOnDelete();
            $t->foreignId('assessor_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('title', 180);
            $t->dateTime('starts_at');
            $t->dateTime('ends_at');
            $t->string('status', 24)->default('scheduled');
            $t->timestamps();
            $t->index(['cabinet_id', 'starts_at']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assessor_appointments');
        Schema::dropIfExists('assessor_demand_events');
        Schema::table('assessor_demands', fn (Blueprint $t) => $t->dropConstrainedForeignId('citizen_id'));
        Schema::dropIfExists('assessor_citizens');
    }
};
