<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('factory_observer_audit_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('event', 80);
            $table->string('mission_id', 80)->index();
            $table->string('tenant_id', 80)->index();
            $table->string('actor_id', 80);
            $table->string('capability', 80);
            $table->timestamp('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('factory_observer_audit_events');
    }
};
