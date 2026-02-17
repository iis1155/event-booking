<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'role'              => UserRole::class,
            'deleted_at'        => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function payments(): HasMany
    {
        return $this->hasManyThrough(Payment::class, Booking::class);
    }

    // ─── Role Helpers ─────────────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isOrganizer(): bool
    {
        return $this->role === UserRole::Organizer;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::Customer;
    }

    public function hasRole(string|UserRole $role): bool
    {
        $roleEnum = $role instanceof UserRole ? $role : UserRole::from($role);
        return $this->role === $roleEnum;
    }
}
