<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test retrieving the list of users for direct messaging.
     */
    public function test_can_get_users_list(): void
    {
        User::factory()->create([
            'name' => 'Alice Johnson',
            'username' => 'alice_j',
            'email' => 'alice@cloudnews.test',
            'is_guest' => false,
        ]);

        User::factory()->create([
            'name' => 'Bob Williams',
            'username' => null,
            'email' => 'bob@cloudnews.test',
            'is_guest' => false,
        ]);

        // Test with v1 prefix
        $responseV1 = $this->getJson('/api/v1/users');
        $responseV1->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Users retrieved successfully',
            ])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'username',
                        'email',
                        'avatar_url',
                        'role',
                        'is_guest',
                    ],
                ],
            ]);

        // Ensure username fallback works when username is null
        $bob = collect($responseV1->json('data'))->firstWhere('name', 'Bob Williams');
        $this->assertNotNull($bob['username']);
        $this->assertEquals('bob', $bob['username']);

        // Test direct route without v1 prefix
        $responseDirect = $this->getJson('/api/users');
        $responseDirect->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonCount(2, 'data');
    }

    /**
     * Test filtering users by search term.
     */
    public function test_can_filter_users_by_search(): void
    {
        User::factory()->create([
            'name' => 'Charlie Brown',
            'username' => 'charlie_b',
            'email' => 'charlie@cloudnews.test',
        ]);

        User::factory()->create([
            'name' => 'David Miller',
            'username' => 'david_m',
            'email' => 'david@cloudnews.test',
        ]);

        $response = $this->getJson('/api/v1/users?search=Charlie');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Charlie Brown');
    }

    /**
     * Test excluding the authenticated user when requested.
     */
    public function test_can_exclude_self_when_requested(): void
    {
        $currentUser = User::factory()->create([
            'name' => 'Current Host',
            'username' => 'currenthost',
            'email' => 'host@cloudnews.test',
        ]);

        User::factory()->create([
            'name' => 'Other Colleague',
            'username' => 'colleague',
            'email' => 'colleague@cloudnews.test',
        ]);

        $response = $this->actingAs($currentUser)->getJson('/api/v1/users?exclude_self=true');
        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Other Colleague');
    }
}
