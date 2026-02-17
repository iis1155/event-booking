<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ── Registration ──────────────────────────────────────────────────────

    public function test_customer_can_register(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['user', 'access_token', 'token_type'],
            ])
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'test@test.com']);
    }

    public function test_registration_fails_with_duplicate_email(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@test.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_registration_fails_with_weak_password(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Test User',
            'email'                 => 'test@test.com',
            'password'              => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422);
    }

    // ── Login ─────────────────────────────────────────────────────────────

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@test.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['access_token', 'token_type', 'user'],
            ])
            ->assertJson(['success' => true]);
    }

    public function test_login_fails_with_wrong_password(): void
    {
        User::factory()->create(['email' => 'test@test.com']);

        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'test@test.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_login_fails_with_unknown_email(): void
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email'    => 'nobody@test.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    // ── Logout ────────────────────────────────────────────────────────────

    public function test_user_can_logout(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        // Token should be revoked
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_fails_without_token(): void
    {
        $response = $this->postJson('/api/v1/auth/logout');
        $response->assertStatus(401);
    }

    // ── Me ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_get_profile(): void
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'data' => ['email' => $user->email],
            ]);
    }

    public function test_unauthenticated_user_cannot_get_profile(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }
}
