<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('assessor_tenants')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20)->default('viewer');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'role', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessor_memberships');
    }
};
