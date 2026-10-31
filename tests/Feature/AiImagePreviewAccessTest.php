<?php

namespace Tests\Feature;

use App\Models\AiMediaGeneration;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AiImagePreviewAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['filesystems.default' => 'local']);
        Storage::fake('local');

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('ai_media_generations', function (Blueprint $table): void {
            $table->id();
            $table->string('capability');
            $table->string('status');
            $table->string('model_name')->nullable();
            $table->string('asset_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('ai_media_generations');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    private function account(string $role, bool $active = true): User
    {
        return User::query()->create([
            'name' => 'Preview ' . $role,
            'email' => $role . ($active ? '.active' : '.inactive') . '@example.test',
            'password' => bcrypt('local-test-only'),
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    private function generation(string $status = 'Concluído', ?string $path = null): AiMediaGeneration
    {
        return AiMediaGeneration::query()->create([
            'capability' => 'image_generation',
            'status' => $status,
            'model_name' => 'google/gemini-3.1-flash-image',
            'asset_path' => $path ?? 'ai-generated/marketing/2026/10/23/9b72c67e-6965-4c7a-b5d6-310df2bb3f44.png',
            'metadata' => ['storage_disk' => 'local'],
        ]);
    }

    private function validPng(): string
    {
        return base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aS1sAAAAASUVORK5CYII=', true);
    }

    public function test_admin_can_open_completed_private_image_without_new_provider_request(): void
    {
        $record = $this->generation();
        $bytes = $this->validPng();
        Storage::disk('local')->put($record->asset_path, $bytes);

        $response = $this->actingAs($this->account('admin'))
            ->get(route('centro-ia.images.preview', ['generation' => $record->id]));

        $response->assertOk()
            ->assertHeader('Content-Type', 'image/png')
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->assertSame($bytes, $response->getContent());
    }

    public function test_regular_user_is_denied(): void
    {
        $record = $this->generation();
        Storage::disk('local')->put($record->asset_path, $this->validPng());
        $this->actingAs($this->account('client'))
            ->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertForbidden();
    }

    public function test_inactive_admin_is_denied(): void
    {
        $record = $this->generation();
        Storage::disk('local')->put($record->asset_path, $this->validPng());
        $this->actingAs($this->account('admin', false))
            ->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertForbidden();
    }

    public function test_guest_cannot_download_private_image(): void
    {
        $record = $this->generation();
        Storage::disk('local')->put($record->asset_path, $this->validPng());
        $this->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertRedirect();
    }

    public function test_admin_cannot_open_pending_or_missing_images(): void
    {
        $record = $this->generation('Pendente');
        Storage::disk('local')->put($record->asset_path, $this->validPng());
        $this->actingAs($this->account('admin'))
            ->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertNotFound();

        $record->update(['status' => 'Concluído']);
        Storage::disk('local')->delete($record->asset_path);
        $this->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertNotFound();
    }

    public function test_admin_cannot_open_unapproved_paths_or_non_images(): void
    {
        $record = $this->generation('Concluído', 'unsafe/file.png');
        Storage::disk('local')->put($record->asset_path, $this->validPng());
        $this->actingAs($this->account('admin'))
            ->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertNotFound();

        $record->update(['asset_path' => 'ai-generated/marketing/2026/10/23/9b72c67e-6965-4c7a-b5d6-310df2bb3f44.png']);
        Storage::disk('local')->put($record->asset_path, 'this is not a PNG');
        $this->get(route('centro-ia.images.preview', ['generation' => $record->id]))
            ->assertNotFound();
    }
}
