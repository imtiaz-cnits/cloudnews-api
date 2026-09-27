<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountSessionTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 1. First login succeeds and receives a valid token.
     */
    public function test_first_login_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 2. Second-device login succeeds.
     */
    public function test_second_device_login_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        // Device A logs in
        $responseA = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);
        $responseA->assertStatus(200);

        // Device B logs in
        $responseB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);
        $responseB->assertStatus(200);
        $this->assertNotEmpty($responseB->json('data.token'));
    }

    /**
     * 3. First device token becomes invalid after second device logs in.
     */
    public function test_first_device_token_becomes_invalid_after_second_login(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        // Device A logs in
        $tokenA = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // Verify Device A can access protected endpoint
        $this->withToken($tokenA)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        // Device B logs in with same account
        $tokenB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // Reset in-memory guard cache to simulate separate device/process request
        \Illuminate\Support\Facades\Auth::forgetGuards();

        // Device A now makes an authenticated request: MUST BE 401 with AUTH_SESSION_REVOKED
        $revokedResponse = $this->withToken($tokenA)
            ->getJson('/api/v1/auth/me');

        $revokedResponse->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_SESSION_REVOKED',
            ]);
    }

    /**
     * 4. Second device token remains valid after first device token is revoked.
     */
    public function test_second_device_token_remains_valid(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $tokenA = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $tokenB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // Device B's request succeeds
        $this->withToken($tokenB)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                ],
            ]);
    }

    /**
     * 5. Multiple consecutive/concurrent logins leave exactly one active session.
     */
    public function test_consecutive_logins_leave_only_one_active_session(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'login' => 'device_user@cloudnews.com',
                'password' => 'Password123!',
            ])->assertStatus(200);
        }

        // Exactly one token exists in the database
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 6. Unrelated accounts are unaffected by other users logging in.
     */
    public function test_unrelated_accounts_are_unaffected(): void
    {
        $user1 = User::factory()->create([
            'email' => 'user1@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $user2 = User::factory()->create([
            'email' => 'user2@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $token1 = $this->postJson('/api/v1/auth/login', [
            'login' => 'user1@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $token2 = $this->postJson('/api/v1/auth/login', [
            'login' => 'user2@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // User 1 logs in from second device
        $token1New = $this->postJson('/api/v1/auth/login', [
            'login' => 'user1@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // User 2's token MUST still be valid!
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($token2)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['id' => $user2->id],
            ]);

        // User 1's old token is revoked
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($token1)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJson(['error_code' => 'AUTH_SESSION_REVOKED']);

        // User 1's new token is valid
        $this->withToken($token1New)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJson(['data' => ['id' => $user1->id]]);
    }

    /**
     * 7. Revoked session returns stable 401 with AUTH_SESSION_REVOKED.
     */
    public function test_revoked_session_returns_stable_401_error_code(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $tokenOld = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        // Supresede with new login
        $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $response = $this->withToken($tokenOld)->getJson('/api/v1/meetings');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error_code' => 'AUTH_SESSION_REVOKED',
                'message' => 'This account was signed in on another device.',
            ]);
    }

    /**
     * 8. Logout behavior remains correct.
     */
    public function test_logout_behavior_remains_correct(): void
    {
        $user = User::factory()->create([
            'email' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->json('data.token');

        $this->withToken($token)
            ->postJson('/api/v1/auth/logout')
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully',
            ]);

        // After logout, token is revoked
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($token)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);

        $this->assertEquals(0, $user->tokens()->count());
    }
}
