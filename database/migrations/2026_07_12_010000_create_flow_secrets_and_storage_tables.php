<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flow_secrets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->string('key', 160);
            $table->longText('encrypted_value');
            $table->string('scope', 80)->default('tenant');
            $table->string('status', 30)->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'key', 'scope'], 'flow_secrets_company_key_scope_unique');
            $table->index(['status', 'expires_at']);
        });

        Schema::create('flow_storage_objects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('workflow_uuid')->nullable();
            $table->string('execution_id', 190)->nullable();
            $table->string('disk', 80)->default('local');
            $table->string('path', 1024);
            $table->string('visibility', 20)->default('private');
            $table->string('mime_type', 190)->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('checksum', 128)->nullable();
            $table->string('status', 30)->default('available');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['disk', 'path'], 'flow_storage_disk_path_unique');
            $table->index(['company_id', 'workflow_uuid']);
            $table->index(['execution_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flow_storage_objects');
        Schema::dropIfExists('flow_secrets');
    }
};
