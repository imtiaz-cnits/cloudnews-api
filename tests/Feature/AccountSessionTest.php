<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
     * 2. Second-device login returns 409 ACTIVE_SESSION_EXISTS while first device is active.
     */
    public function test_second_device_login_returns_409_active_session_exists(): void
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

        // Device B attempts login with the exact same credentials
        $responseB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $responseB->assertStatus(409)
            ->assertJson([
                'success' => false,
                'error_code' => 'ACTIVE_SESSION_EXISTS',
                'message' => 'This account is already signed in on another device. Please log out from that device first.',
            ]);
    }

    /**
     * 3. First device remains fully authenticated after second device login is rejected.
     */
    public function test_first_device_remains_authenticated_after_second_login_rejected(): void
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

        // Device B attempts login and is rejected with 409
        $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ])->assertStatus(409);

        // Device A continues to access protected endpoints without interruption
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($tokenA)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200)
            ->assertJsonPath('data.email', 'device_user@cloudnews.com');

        // Confirm only 1 token exists for the user
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 4. Explicit logout from Device A releases the active session, allowing Device B to log in.
     */
    public function test_explicit_logout_allows_second_device_login(): void
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

        // Device A explicitly logs out
        $logoutResponse = $this->withToken($tokenA)
            ->postJson('/api/v1/auth/logout');
        $logoutResponse->assertStatus(200);

        $this->assertEquals(0, $user->tokens()->count());

        // Device B can now log in successfully
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $responseB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $responseB->assertStatus(200);
        $this->assertNotEmpty($responseB->json('data.token'));
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 5. Concurrent login requests: exactly one succeeds and the other receives 409.
     */
    public function test_concurrent_login_requests_serialize_and_only_one_succeeds(): void
    {
        $user = User::factory()->create([
            'email' => 'concurrent_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $results = [];

        // Simulate concurrent attempts
        for ($i = 0; $i < 3; $i++) {
            $response = $this->postJson('/api/v1/auth/login', [
                'login' => 'concurrent_user@cloudnews.com',
                'password' => 'Password123!',
            ]);
            $results[] = $response->status();
        }

        // Exactly one 200 and remaining two 409s
        $successCount = count(array_filter($results, fn ($s) => $s === 200));
        $conflictCount = count(array_filter($results, fn ($s) => $s === 409));

        $this->assertEquals(1, $successCount);
        $this->assertEquals(2, $conflictCount);
        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 6. Stale/expired token behavior according to existing token lifecycle.
     */
    public function test_expired_token_is_pruned_allowing_new_login(): void
    {
        $user = User::factory()->create([
            'email' => 'expired_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        // Create an expired personal access token directly in the database
        $user->tokens()->create([
            'name' => 'auth_token',
            'token' => hash('sha256', 'expired_token_test'),
            'abilities' => ['*'],
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $this->assertEquals(1, $user->tokens()->count());

        // When logging in, the expired token should be pruned and login should succeed
        $response = $this->postJson('/api/v1/auth/login', [
            'login' => 'expired_user@cloudnews.com',
            'password' => 'Password123!',
        ]);

        $response->assertStatus(200);
        $this->assertEquals(1, $user->tokens()->count());
        $this->assertNull($user->tokens()->first()->expires_at);
    }

    /**
     * 7. Failed second login (e.g. wrong password) does not modify the first session.
     */
    public function test_failed_second_login_does_not_modify_first_session(): void
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

        // Device B attempts login with wrong password
        $responseB = $this->postJson('/api/v1/auth/login', [
            'login' => 'device_user@cloudnews.com',
            'password' => 'WrongPassword!',
        ]);
        $responseB->assertStatus(401);

        // Device A remains valid
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $this->withToken($tokenA)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(200);

        $this->assertEquals(1, $user->tokens()->count());
    }

    /**
     * 8. Unrelated user accounts are completely unaffected.
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

        $res1 = $this->postJson('/api/v1/auth/login', [
            'login' => 'user1@cloudnews.com',
            'password' => 'Password123!',
        ]);
        $res1->assertStatus(200);

        $res2 = $this->postJson('/api/v1/auth/login', [
            'login' => 'user2@cloudnews.com',
            'password' => 'Password123!',
        ]);
        $res2->assertStatus(200);

        $this->assertEquals(1, $user1->tokens()->count());
        $this->assertEquals(1, $user2->tokens()->count());
    }
}
