<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'company_id',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        /*
         * Segurança 1.7.1:
         * - Admin interno acessa /admin.
         * - Usuário cliente não acessa /admin; usa /cliente.
         * - Usuários antigos sem role continuam tratados como admin para não bloquear o painel.
         */
        return ($this->is_active ?? true)
            && (
                $this->role === 'admin'
                || blank($this->role)
            );
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin' || blank($this->role);
    }

    public function isClient(): bool
    {
        return in_array($this->role, ['client', 'cliente'], true);
    }
}
