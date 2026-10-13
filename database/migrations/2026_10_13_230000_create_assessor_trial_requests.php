<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void
    {
        Schema::create('assessor_trial_requests', function (Blueprint $t) {
            $t->char('id',32)->primary();
            $t->foreignId('user_id')->constrained()->restrictOnDelete();
            $t->foreignId('company_id')->constrained()->restrictOnDelete();
            $t->unique('company_id'); // One evaluation per cabinet; cancellation cannot reset it.
            $t->text('cabinet_name');
            $t->string('mandate_type',20);
            $t->string('terms_version',60);
            $t->timestamp('consented_at');
            $t->string('status',30)->default('requested')->index();
            $t->string('hostname')->unique();
            $t->string('route_id')->nullable();
            $t->timestamp('starts_at')->nullable();
            $t->timestamp('ends_at')->nullable()->index();
            $t->timestamp('cancelled_at')->nullable();
            $t->string('blocker',80)->nullable();
            $t->timestamps();
        });
        Schema::create('assessor_trial_events', function (Blueprint $t) {
            $t->id();
            $t->char('trial_id',32);
            $t->foreign('trial_id')->references('id')->on('assessor_trial_requests')->restrictOnDelete();
            $t->string('event',40);
            $t->timestamp('created_at');
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assessor_trial_events');
        Schema::dropIfExists('assessor_trial_requests');
    }
};
