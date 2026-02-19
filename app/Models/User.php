<?php

namespace App\Models;

use App\Enums\Perfil;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'employee_id',
        'role',
        'name',
        'email',
        'password',
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
            'role' => Perfil::class,
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function isAdministrator(): bool
    {
        return $this->role === Perfil::Administrator;
    }

    public function isManager(): bool
    {
        return $this->role === Perfil::Manager;
    }

    public function isStaff(): bool
    {
        return $this->role === Perfil::Staff;
    }

    public function isManagerOrHigher(): bool
    {
        return in_array($this->role, [Perfil::Manager, Perfil::Administrator]);
    }
}
