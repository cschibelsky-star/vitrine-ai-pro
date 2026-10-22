<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('slug', 120)->unique();
            $table->string('kind', 32)->default('vereador');
            $table->timestamps();
        });
        Schema::create('assessor_citizens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('assessor_tenants')->cascadeOnDelete();
            $table->string('name', 160);
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->json('residential_address')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'id']);
        });
        Schema::create('assessor_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('assessor_tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('citizen_id');
            $table->foreign(['tenant_id', 'citizen_id'])->references(['tenant_id', 'id'])->on('assessor_citizens')->restrictOnDelete();
            $table->uuid('protocol')->unique();
            $table->string('subject', 180);
            $table->text('description');
            $table->json('issue_location')->nullable();
            $table->string('status', 30)->default('received');
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status', 'created_at']);
            $table->unique(['tenant_id', 'id']);
        });
        Schema::create('assessor_case_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('assessor_tenants')->cascadeOnDelete();
            $table->unsignedBigInteger('case_id');
            $table->foreign(['tenant_id', 'case_id'])->references(['tenant_id', 'id'])->on('assessor_cases')->cascadeOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 50);
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'case_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_case_events');
        Schema::dropIfExists('assessor_cases');
        Schema::dropIfExists('assessor_citizens');
        Schema::dropIfExists('assessor_tenants');
    }
};
