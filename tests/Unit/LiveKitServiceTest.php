<?php

namespace Tests\Unit;

use App\Services\LiveKitService;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Tests\TestCase;

class LiveKitServiceTest extends TestCase
{
    protected LiveKitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LiveKitService();
    }

    public function test_generates_valid_host_livekit_token(): void
    {
        $roomName = 'cloudnews-test-room-123';
        $identity = 'user_42';
        $name = 'Conference Host';

        $token = $this->service->generateJoinToken(
            roomName: $roomName,
            identity: $identity,
            name: $name,
            isHost: true,
            role: 'host'
        );

        $this->assertNotEmpty($token);

        // Decode JWT using the secret key
        $secret = config('livekit.api_secret', 'secret_token_for_cloudnews_2026_32chars');
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        $this->assertEquals($identity, $decoded->sub);
        $this->assertEquals($name, $decoded->name);
        $this->assertEquals(config('livekit.api_key', 'devkey'), $decoded->iss);

        $video = (array) $decoded->video;
        $this->assertEquals($roomName, $video['room']);
        $this->assertTrue($video['roomJoin']);
        $this->assertTrue($video['canPublish']);
        $this->assertTrue($video['canSubscribe']);
        $this->assertTrue($video['canPublishData']);
        $this->assertTrue($video['roomAdmin']);
    }

    public function test_viewer_token_cannot_publish(): void
    {
        $roomName = 'cloudnews-viewer-room';
        $identity = 'user_99';
        $name = 'Silent Viewer';

        $token = $this->service->generateJoinToken(
            roomName: $roomName,
            identity: $identity,
            name: $name,
            isHost: false,
            role: 'viewer'
        );

        $secret = config('livekit.api_secret', 'secret_token_for_cloudnews_2026_32chars');
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

        $video = (array) $decoded->video;
        $this->assertFalse($video['canPublish']);
        $this->assertTrue($video['canSubscribe']);
        $this->assertFalse($video['roomAdmin'] ?? false);
    }
}
