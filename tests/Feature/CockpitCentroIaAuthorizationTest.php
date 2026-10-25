<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

final class CockpitCentroIaAuthorizationTest extends TestCase
{
    private const GALLERY = '/cockpit/centro-ia';
    private const IMAGE = '/cockpit/centro-ia/image/2026/10/23/83a4a570-66c8-4f37-bf79-02e97255a118.png';

    private function testIdentity(string $role, bool $active = true): User
    {
        $user = new User();
        $user->forceFill([
            'id' => 900001,
            'name' => 'Cockpit access test',
            'email' => 'cockpit-access-test@example.invalid',
            'role' => $role,
            'is_active' => $active,
        ]);

        return $user;
    }

    public function test_anonymous_cannot_open_gallery(): void
    {
        $this->get(self::GALLERY)->assertForbidden();
    }

    public function test_anonymous_cannot_open_image_directly(): void
    {
        $this->get(self::IMAGE)->assertForbidden();
    }

    public function test_authenticated_client_cannot_open_gallery(): void
    {
        $this->actingAs($this->testIdentity('client'))
            ->get(self::GALLERY)
            ->assertForbidden();
    }

    public function test_authenticated_client_cannot_open_image_directly(): void
    {
        $this->actingAs($this->testIdentity('client'))
            ->get(self::IMAGE)
            ->assertForbidden();
    }

    public function test_inactive_administrator_cannot_open_gallery(): void
    {
        $this->actingAs($this->testIdentity('admin', false))
            ->get(self::GALLERY)
            ->assertForbidden();
    }

    public function test_inactive_administrator_cannot_open_image_directly(): void
    {
        $this->actingAs($this->testIdentity('admin', false))
            ->get(self::IMAGE)
            ->assertForbidden();
    }

    public function test_active_administrator_can_open_gallery(): void
    {
        $this->actingAs($this->testIdentity('admin'))
            ->get(self::GALLERY)
            ->assertOk();
    }
}
