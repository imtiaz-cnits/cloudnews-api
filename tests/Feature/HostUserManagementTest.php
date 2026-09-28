<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_dashboard_listing_only_shows_registered_hosts_and_excludes_guests(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'role' => 'admin',
            'is_guest' => false,
        ]);

        $registeredHost1 = User::factory()->create([
            'name' => 'Valid Host 1',
            'username' => 'host_one',
            'email' => 'host1@example.com',
            'role' => 'host',
            'is_guest' => false,
        ]);

        $registeredHost2 = User::factory()->create([
            'name' => 'Valid Host 2',
            'username' => 'host_two',
            'email' => 'host2@example.com',
            'role' => 'host',
            'is_guest' => false,
        ]);

        // Create various types of guest users (legacy and newly created)
        $deviceGuest = User::create([
            'name' => 'Anika Guest',
            'username' => 'guest_dev_mulk9xe7_isswqslp',
            'role' => 'guest',
            'is_guest' => true,
        ]);

        $legacyGuestWithHostRole = User::create([
            'name' => 'Legacy Guest',
            'username' => 'guest_dev_legacy123',
            'role' => 'host', // simulate stale database state
            'is_guest' => true,
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('dashboard.hosts.index'));
        $response->assertStatus(200);

        // Assert view only contains valid hosts
        $response->assertViewHas('hosts', function ($hosts) use ($registeredHost1, $registeredHost2, $deviceGuest, $legacyGuestWithHostRole) {
            $hostIds = $hosts->pluck('id')->toArray();

            return in_array($registeredHost1->id, $hostIds, true)
                && in_array($registeredHost2->id, $hostIds, true)
                && ! in_array($deviceGuest->id, $hostIds, true)
                && ! in_array($legacyGuestWithHostRole->id, $hostIds, true);
        });
    }

    public function test_user_model_host_and_guest_scopes(): void
    {
        $host = User::factory()->create([
            'role' => 'host',
            'is_guest' => false,
            'username' => 'regular_host',
        ]);

        $guest = User::create([
            'name' => 'Test Guest',
            'username' => 'guest_dev_9999',
            'role' => 'guest',
            'is_guest' => true,
        ]);

        $legacyGuest = User::create([
            'name' => 'Stale Guest',
            'username' => 'guest_dev_8888',
            'role' => 'host',
            'is_guest' => true,
        ]);

        $hosts = User::hosts()->get();
        $this->assertTrue($hosts->contains('id', $host->id));
        $this->assertFalse($hosts->contains('id', $guest->id));
        $this->assertFalse($hosts->contains('id', $legacyGuest->id));

        $guests = User::guests()->get();
        $this->assertTrue($guests->contains('id', $guest->id));
        $this->assertTrue($guests->contains('id', $legacyGuest->id));
        $this->assertFalse($guests->contains('id', $host->id));

        $this->assertTrue($host->isHost());
        $this->assertFalse($host->isGuest());

        $this->assertTrue($guest->isGuest());
        $this->assertFalse($guest->isHost());

        $this->assertTrue($legacyGuest->isGuest());
        $this->assertFalse($legacyGuest->isHost());
    }

    public function test_guest_endpoint_sets_guest_role(): void
    {
        $response = $this->postJson('/api/v1/auth/guest', [
            'name' => 'Guest Visitor',
            'device_id' => 'device_abc123',
        ]);

        $response->assertStatus(201);
        $user = User::where('username', 'guest_device_abc123')->first();

        $this->assertNotNull($user);
        $this->assertEquals('guest', $user->role);
        $this->assertTrue((bool) $user->is_guest);
        $this->assertTrue($user->isGuest());
        $this->assertFalse($user->isHost());
    }

    public function test_authorized_admin_can_revoke_host_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_guest' => false,
        ]);

        $host = User::factory()->create([
            'role' => 'host',
            'is_guest' => false,
        ]);

        $otherHost = User::factory()->create([
            'role' => 'host',
            'is_guest' => false,
        ]);

        // Issue tokens
        $host->createToken('device_a');
        $host->createToken('device_b');
        $otherHost->createToken('other_device');

        $this->assertEquals(2, $host->tokens()->count());
        $this->assertEquals(1, $otherHost->tokens()->count());

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.hosts.revoke-session', $host));
        $response->assertRedirect(route('dashboard.hosts.index'))
            ->assertSessionHas('success');

        // Host tokens revoked, other host tokens intact
        $this->assertEquals(0, $host->tokens()->count());
        $this->assertEquals(1, $otherHost->tokens()->count());
    }

    public function test_unauthorized_user_cannot_revoke_session(): void
    {
        $host = User::factory()->create([
            'role' => 'host',
            'is_guest' => false,
        ]);
        $host->createToken('device_a');

        // Unauthenticated request
        $response = $this->post(route('dashboard.hosts.revoke-session', $host));
        $response->assertRedirect(route('login'));
        $this->assertEquals(1, $host->tokens()->count());

        // Regular non-admin host acting
        $nonAdmin = User::factory()->create([
            'role' => 'host',
            'is_guest' => false,
        ]);
        $response = $this->actingAs($nonAdmin)->post(route('dashboard.hosts.revoke-session', $host));
        $response->assertRedirect(route('login'))
            ->assertSessionHas('error');
        $this->assertEquals(1, $host->tokens()->count());
    }

    public function test_after_admin_revokes_session_user_can_login_again(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_guest' => false,
        ]);

        $host = User::factory()->create([
            'email' => 'host_lock@example.com',
            'password' => 'Password123!',
            'role' => 'host',
            'is_guest' => false,
        ]);

        // Device A logs in
        $login1 = $this->postJson('/api/v1/auth/login', [
            'login' => 'host_lock@example.com',
            'password' => 'Password123!',
        ]);
        $login1->assertStatus(200);

        // Device B tries to log in, rejected with 409
        $login2 = $this->postJson('/api/v1/auth/login', [
            'login' => 'host_lock@example.com',
            'password' => 'Password123!',
        ]);
        $login2->assertStatus(409)
            ->assertJson([
                'error_code' => 'ACTIVE_SESSION_EXISTS',
            ]);

        // Admin revokes session from dashboard
        $this->actingAs($admin);
        $revokeResponse = $this->post(route('dashboard.hosts.revoke-session', $host));
        $revokeResponse->assertRedirect(route('dashboard.hosts.index'));
        $this->assertEquals(0, $host->tokens()->count());

        // Device B can now log in successfully
        \Illuminate\Support\Facades\Auth::forgetGuards();
        $login3 = $this->postJson('/api/v1/auth/login', [
            'login' => 'host_lock@example.com',
            'password' => 'Password123!',
        ]);
        $login3->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful',
            ]);
        $this->assertNotEmpty($login3->json('data.token'));
        $this->assertEquals(1, $host->tokens()->count());
    }

    public function test_admin_can_bulk_revoke_host_sessions(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);

        $host1 = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $host2 = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $host1->createToken('dev_1');
        $host2->createToken('dev_2');

        $this->assertEquals(1, $host1->tokens()->count());
        $this->assertEquals(1, $host2->tokens()->count());

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.hosts.bulk-action'), [
            'action' => 'revoke_sessions',
            'selected_ids' => [$host1->id, $host2->id],
        ]);

        $response->assertRedirect(route('dashboard.hosts.index'))
            ->assertSessionHas('success');

        $this->assertEquals(0, $host1->tokens()->count());
        $this->assertEquals(0, $host2->tokens()->count());
    }

    public function test_admin_can_bulk_delete_hosts(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);

        $host1 = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $host2 = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.hosts.bulk-action'), [
            'action' => 'delete',
            'selected_ids' => [$host1->id, $host2->id],
        ]);

        $response->assertRedirect(route('dashboard.hosts.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $host1->id]);
        $this->assertDatabaseMissing('users', ['id' => $host2->id]);
    }
}
