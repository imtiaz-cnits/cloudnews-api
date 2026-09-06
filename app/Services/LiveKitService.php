<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use Exception;
use Illuminate\Support\Facades\Log;

class LiveKitService
{
    protected string $host;
    protected string $apiKey;
    protected string $apiSecret;

    public function __construct()
    {
        $host = config('livekit.url');
        $this->host = !empty($host) ? $host : (env('LIVEKIT_URL') ?: 'http://127.0.0.1:7880');

        $apiKey = config('livekit.api_key');
        $this->apiKey = !empty($apiKey) ? $apiKey : (env('LIVEKIT_API_KEY') ?: 'devkey');

        $apiSecret = config('livekit.api_secret');
        $this->apiSecret = !empty($apiSecret) ? $apiSecret : (env('LIVEKIT_API_SECRET') ?: 'dev_secret_key_at_least_32_characters_long!');
    }

    /**
     * Create a new class instance.
     * Create or configure a LiveKit room.
     */
    public function __construct()
    public function createRoom(string $roomName, array $options = [])
    {
        //
        try {
            $client = new RoomServiceClient($this->host, $this->apiKey, $this->apiSecret);
            $roomOptions = (new RoomCreateOptions())
                ->setName($roomName)
                ->setEmptyTimeout($options['empty_timeout'] ?? 300)
                ->setMaxParticipants($options['max_participants'] ?? 50);

            return $client->createRoom($roomOptions);
        } catch (Exception $e) {
            Log::error('LiveKit createRoom error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate an access token to join a room.
     */
    public function generateJoinToken(string $roomName, string $identity, ?string $name = null, array $metadata = []): string
    {
        try {
            $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($identity)
                ->setTtl(6 * 60 * 60); // 6 hours

            if ($name) {
                $tokenOptions->setName($name);
            }

            if (!empty($metadata)) {
                $tokenOptions->setMetadata(json_encode($metadata));
            }

            $accessToken = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);

            $grant = (new VideoGrant())
                ->setRoomJoin(true)
                ->setRoomName($roomName)
                ->setCanPublish(true)
                ->setCanSubscribe(true);

            $accessToken->setGrant($grant);

            return $accessToken->toJwt();
        } catch (Exception $e) {
            Log::error('LiveKit generateJoinToken error: ' . $e->getMessage());
            throw $e;
        }
    }
}
