<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name'              => fake()->name(),
            'email'             => fake()->unique()->safeEmail(),
            'password'          => static::$password ??= Hash::make('password'),
            'phone'             => fake()->phoneNumber(),
            'role'              => UserRole::Customer,
            'email_verified_at' => now(),
            'remember_token'    => Str::random(10),
        ];
    }

    // ─── Role States ──────────────────────────────────────────────────────────

    public function admin(): static
    {
        return $this->state(fn() => ['role' => UserRole::Admin]);
    }

    public function organizer(): static
    {
        return $this->state(fn() => ['role' => UserRole::Organizer]);
    }

    public function customer(): static
    {
        return $this->state(fn() => ['role' => UserRole::Customer]);
    }

    public function unverified(): static
    {
        return $this->state(fn() => ['email_verified_at' => null]);
    }
}
