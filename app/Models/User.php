<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

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
        if (($this->is_active ?? true) === false) {
            return false;
        }

        if ($this->role === 'admin') {
            return true;
        }

        if (! blank($this->role)) {
            return false;
        }

        // Legacy accounts without a role may access Core only when they are
        // not associated with an Assessor cabinet. Explicit Core admins remain allowed.
        return ! DB::table('assessor_memberships')
            ->where('user_id', $this->getAuthIdentifier())
            ->where('active', true)
            ->exists();
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
