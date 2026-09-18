<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_store_and_retrieve_meeting_messages(): void
    {
        $host = User::factory()->create(['name' => 'Host User']);
        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'is_active' => true,
        ]);

        // 1. Host sends a text message
        $response1 = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/messages", [
            'type' => 'text',
            'text' => 'Welcome everyone to the meeting!',
            'sender_name' => 'Host User',
        ]);

        $response1->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'text',
                    'text' => 'Welcome everyone to the meeting!',
                    'sender_name' => 'Host User',
                ],
            ]);

        // 2. Host shares an image file
        $response2 = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->meeting_code}/messages", [
            'type' => 'image',
            'text' => 'design_mockup.png',
            'file_name' => 'design_mockup.png',
            'file_size' => '2.4 MB',
            'media_url' => 'https://api.cloudnewsmeet.com/storage/meeting-files/123/design_mockup.png',
            'sender_name' => 'Host User',
        ]);

        $response2->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'type' => 'image',
                    'file_name' => 'design_mockup.png',
                    'file_size' => '2.4 MB',
                ],
            ]);

        // 3. A NEW user (guest or new participant) joins later and retrieves messages
        $newMember = User::factory()->create([
            'name' => 'New Joiner',
            'is_guest' => true,
        ]);

        $historyResponse = $this->actingAs($newMember)->getJson("/api/v1/meetings/{$meeting->meeting_code}/messages");

        $historyResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $messages = $historyResponse->json('data');
        $this->assertCount(2, $messages);
        $this->assertEquals('Welcome everyone to the meeting!', $messages[0]['text']);
        $this->assertEquals('text', $messages[0]['type']);
        $this->assertEquals('design_mockup.png', $messages[1]['file_name']);
        $this->assertEquals('image', $messages[1]['type']);
        $this->assertEquals('2.4 MB', $messages[1]['file_size']);

        // 4. New member also sends a message
        $response3 = $this->actingAs($newMember)->postJson("/api/v1/meetings/{$meeting->meeting_code}/messages", [
            'type' => 'text',
            'text' => 'Thanks for having me! I see the file.',
            'sender_name' => 'New Joiner',
        ]);
        $response3->assertStatus(201);

        // Verify database has 3 messages
        $this->assertDatabaseCount('meeting_messages', 3);
    }

    public function test_messages_cascade_delete_with_meeting(): void
    {
        $host = User::factory()->create();
        $meeting = Meeting::factory()->create(['host_id' => $host->id]);

        MeetingMessage::create([
            'meeting_id' => $meeting->id,
            'meeting_code' => $meeting->meeting_code,
            'user_id' => $host->id,
            'sender_name' => $host->name,
            'type' => 'text',
            'text' => 'Hello',
        ]);

        $this->assertDatabaseCount('meeting_messages', 1);

        // Delete meeting
        $this->actingAs($host)->deleteJson("/api/v1/meetings/{$meeting->meeting_code}");

        $this->assertDatabaseCount('meeting_messages', 0);
    }

    public function test_meeting_chat_controller_endpoints_with_aliases_and_numeric_id(): void
    {
        $host = User::factory()->create(['name' => 'Host Lead']);
        $meeting = Meeting::factory()->create([
            'host_id' => $host->id,
            'meeting_code' => '999-888',
            'room_name' => 'cloudnews-room999',
            'is_active' => true,
        ]);

        // 1. Post using sender_id, message, file_url, file_type, client_msg_id to numeric ID
        $postRes = $this->actingAs($host)->postJson("/api/v1/meetings/{$meeting->id}/messages", [
            'sender_id' => $host->id,
            'sender_name' => 'Host Lead',
            'message' => 'Quarterly report presentation file',
            'file_url' => 'https://example.com/files/report.pdf',
            'file_type' => 'document',
            'file_name' => 'report.pdf',
            'file_size' => '1.2 MB',
            'client_msg_id' => 'client-uuid-12345',
        ]);

        $postRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'sender_name' => 'Host Lead',
                    'message' => 'Quarterly report presentation file',
                    'text' => 'Quarterly report presentation file',
                    'file_url' => 'https://example.com/files/report.pdf',
                    'file_type' => 'document',
                    'type' => 'document',
                    'client_msg_id' => 'client-uuid-12345',
                ],
            ]);

        // 2. Fetch using meeting_code without dash as a guest
        $getRes = $this->getJson('/api/v1/meetings/999888/messages');
        $getRes->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $data = $getRes->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('Quarterly report presentation file', $data[0]['message']);
        $this->assertEquals('https://example.com/files/report.pdf', $data[0]['file_url']);
        $this->assertEquals('client-uuid-12345', $data[0]['client_msg_id']);
        $this->assertNotNull($data[0]['timestamp']);

        // 3. Fetch using room_name
        $getRoomRes = $this->getJson('/api/v1/meetings/cloudnews-room999/messages');
        $getRoomRes->assertStatus(200);
        $this->assertCount(1, $getRoomRes->json('data'));
    }
}
