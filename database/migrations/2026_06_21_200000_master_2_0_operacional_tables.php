<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('subscriptions')) {
            Schema::create('subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
                $table->foreignId('license_id')->nullable()->constrained('licenses')->nullOnDelete();
                $table->string('asaas_subscription_id')->nullable()->index();
                $table->string('asaas_customer_id')->nullable()->index();
                $table->string('external_reference')->nullable()->index();
                $table->string('status', 60)->default('Pendente')->index();
                $table->string('billing_cycle', 40)->nullable();
                $table->decimal('value', 12, 2)->default(0);
                $table->date('next_due_date')->nullable();
                $table->timestamp('activated_at')->nullable();
                $table->timestamp('suspended_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                $table->json('metadata')->nullable();
                $table->longText('asaas_payload')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('asaas_webhook_events')) {
            Schema::create('asaas_webhook_events', function (Blueprint $table) {
                $table->id();
                $table->string('event')->nullable()->index();
                $table->string('asaas_payment_id')->nullable()->index();
                $table->string('asaas_subscription_id')->nullable()->index();
                $table->string('external_reference')->nullable()->index();
                $table->boolean('processed')->default(false)->index();
                $table->string('status', 60)->nullable();
                $table->longText('payload')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('heygen_avatars')) {
            Schema::create('heygen_avatars', function (Blueprint $table) {
                $table->id();
                $table->string('name', 150);
                $table->string('avatar_id')->nullable()->index();
                $table->string('voice_id')->nullable();
                $table->string('language', 20)->default('pt-BR');
                $table->string('status', 60)->default('Ativo')->index();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('heygen_video_jobs')) {
            Schema::create('heygen_video_jobs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('ai_agent_id')->nullable()->constrained('ai_agents')->nullOnDelete();
                $table->foreignId('ai_provider_id')->nullable()->constrained('ai_providers')->nullOnDelete();
                $table->foreignId('heygen_avatar_id')->nullable()->constrained('heygen_avatars')->nullOnDelete();
                $table->string('title', 180)->nullable();
                $table->string('status', 60)->default('Pendente')->index();
                $table->longText('script')->nullable();
                $table->string('heygen_video_id')->nullable()->index();
                $table->string('video_url')->nullable();
                $table->string('thumbnail_url')->nullable();
                $table->integer('duration_seconds')->nullable();
                $table->decimal('credits_used', 10, 2)->default(0);
                $table->longText('payload')->nullable();
                $table->longText('response')->nullable();
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('heygen_credit_ledgers')) {
            Schema::create('heygen_credit_ledgers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
                $table->foreignId('heygen_video_job_id')->nullable()->constrained('heygen_video_jobs')->nullOnDelete();
                $table->string('type', 40)->default('debit')->index();
                $table->decimal('amount', 10, 2)->default(0);
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('licenses', function (Blueprint $table) {
            if (! Schema::hasColumn('licenses', 'payment_id')) $table->unsignedBigInteger('payment_id')->nullable()->index();
            if (! Schema::hasColumn('licenses', 'subscription_id')) $table->unsignedBigInteger('subscription_id')->nullable()->index();
            if (! Schema::hasColumn('licenses', 'is_active')) $table->boolean('is_active')->default(true)->index();
            if (! Schema::hasColumn('licenses', 'activated_at')) $table->timestamp('activated_at')->nullable();
            if (! Schema::hasColumn('licenses', 'suspended_at')) $table->timestamp('suspended_at')->nullable();
            if (! Schema::hasColumn('licenses', 'cancelled_at')) $table->timestamp('cancelled_at')->nullable();
        });

        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'subscription_id')) $table->unsignedBigInteger('subscription_id')->nullable()->index();
            if (! Schema::hasColumn('payments', 'license_id')) $table->unsignedBigInteger('license_id')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('heygen_credit_ledgers');
        Schema::dropIfExists('heygen_video_jobs');
        Schema::dropIfExists('heygen_avatars');
        Schema::dropIfExists('asaas_webhook_events');
        Schema::dropIfExists('subscriptions');
    }
};
