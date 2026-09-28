<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use App\Services\LiveKitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MeetingAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock LiveKitService to prevent actual network calls during tests
        $liveKitMock = Mockery::mock(LiveKitService::class);
        $liveKitMock->shouldReceive('deleteRoom')->andReturn(true);
        $this->app->instance(LiveKitService::class, $liveKitMock);
    }

    public function test_admin_can_view_meetings_management_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $meeting1 = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Weekly Sync',
            'is_active' => true,
            'is_host_online' => true,
        ]);

        $meeting2 = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Product Demo',
            'is_active' => false,
            'ended_at' => now()->subHour(),
        ]);

        $this->actingAs($admin);

        $response = $this->get(route('dashboard.meetings.index'));
        $response->assertStatus(200)
            ->assertViewIs('dashboard.meetings.index')
            ->assertViewHas('meetings');

        $meetingsInView = $response->viewData('meetings');
        $this->assertTrue($meetingsInView->contains('id', $meeting1->id));
        $this->assertTrue($meetingsInView->contains('id', $meeting2->id));
    }

    public function test_admin_can_filter_meetings_by_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $activeMeeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Active Call',
            'is_active' => true,
        ]);

        $endedMeeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'title' => 'Ended Call',
            'is_active' => false,
            'ended_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        // Filter active
        $resActive = $this->get(route('dashboard.meetings.index', ['status' => 'active']));
        $resActive->assertStatus(200);
        $activeList = $resActive->viewData('meetings');
        $this->assertTrue($activeList->contains('id', $activeMeeting->id));
        $this->assertFalse($activeList->contains('id', $endedMeeting->id));

        // Filter ended
        $resEnded = $this->get(route('dashboard.meetings.index', ['status' => 'ended']));
        $resEnded->assertStatus(200);
        $endedList = $resEnded->viewData('meetings');
        $this->assertFalse($endedList->contains('id', $activeMeeting->id));
        $this->assertTrue($endedList->contains('id', $endedMeeting->id));
    }

    public function test_admin_can_end_an_active_meeting_room(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
            'is_host_online' => true,
        ]);

        $participant = MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $host->id,
            'role' => 'host',
            'joined_at' => now()->subMinutes(10),
            'left_at' => null,
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.meetings.end', $meeting));
        $response->assertRedirect(route('dashboard.meetings.index'))
            ->assertSessionHas('success');

        $meeting->refresh();
        $this->assertFalse($meeting->is_active);
        $this->assertFalse($meeting->is_host_online);
        $this->assertNotNull($meeting->ended_at);

        $participant->refresh();
        $this->assertNotNull($participant->left_at);
    }

    public function test_admin_can_permanently_delete_a_meeting(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        MeetingParticipant::create([
            'meeting_id' => $meeting->id,
            'user_id' => $host->id,
            'role' => 'host',
            'joined_at' => now(),
        ]);

        $this->actingAs($admin);

        $response = $this->delete(route('dashboard.meetings.destroy', $meeting));
        $response->assertRedirect(route('dashboard.meetings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
        $this->assertDatabaseMissing('meeting_participants', ['meeting_id' => $meeting->id]);
    }

    public function test_admin_can_bulk_end_selected_meeting_rooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $m1 = Meeting::factory()->create(['host_id' => $host->id, 'is_active' => true]);
        $m2 = Meeting::factory()->create(['host_id' => $host->id, 'is_active' => true]);
        $m3 = Meeting::factory()->create(['host_id' => $host->id, 'is_active' => false]);

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.meetings.bulk-action'), [
            'action' => 'end',
            'selected_ids' => [$m1->id, $m2->id, $m3->id],
        ]);

        $response->assertRedirect(route('dashboard.meetings.index'))
            ->assertSessionHas('success');

        $m1->refresh();
        $m2->refresh();
        $this->assertFalse($m1->is_active);
        $this->assertFalse($m2->is_active);
    }

    public function test_admin_can_bulk_delete_selected_meeting_rooms(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_guest' => false]);
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);

        $m1 = Meeting::factory()->create(['host_id' => $host->id]);
        $m2 = Meeting::factory()->create(['host_id' => $host->id]);

        $this->actingAs($admin);

        $response = $this->post(route('dashboard.meetings.bulk-action'), [
            'action' => 'delete',
            'selected_ids' => [$m1->id, $m2->id],
        ]);

        $response->assertRedirect(route('dashboard.meetings.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('meetings', ['id' => $m1->id]);
        $this->assertDatabaseMissing('meetings', ['id' => $m2->id]);
    }

    public function test_unauthorized_user_cannot_access_or_manage_meetings(): void
    {
        $host = User::factory()->create(['role' => 'host', 'is_guest' => false]);
        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        // Unauthenticated request
        $response = $this->get(route('dashboard.meetings.index'));
        $response->assertRedirect(route('login'));

        // Non-admin user
        $response = $this->actingAs($host)->post(route('dashboard.meetings.end', $meeting));
        $response->assertRedirect(route('login'));
    }
}
