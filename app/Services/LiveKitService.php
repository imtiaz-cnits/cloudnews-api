<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\RoomCreateOptions;
use Agence104\LiveKit\RoomServiceClient;
use Agence104\LiveKit\VideoGrant;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Throwable;

class LiveKitService
{
    protected string $host;

    protected string $apiKey;

    protected string $apiSecret;

    protected int $tokenTtl;

    public function __construct()
    {
        $host = config('livekit.url');
        $this->host = !empty($host) ? $host : (env('LIVEKIT_URL') ?: 'http://127.0.0.1:7880');
        $this->host = rtrim(config('livekit.url', env('LIVEKIT_URL', 'http://119.28.138.19:7880')), '/');
        $this->apiKey = config('livekit.api_key', env('LIVEKIT_API_KEY', 'devkey'));
        $this->apiSecret = config('livekit.api_secret', env('LIVEKIT_API_SECRET', 'secret_token_for_cloudnews_2026_32chars'));
        $this->tokenTtl = (int) config('livekit.token_ttl', env('LIVEKIT_TOKEN_TTL', 21600)); // 6 hours
    }

        $apiKey = config('livekit.api_key');
        $this->apiKey = !empty($apiKey) ? $apiKey : (env('LIVEKIT_API_KEY') ?: 'devkey');
    /**
     * Get the configured LiveKit host URL.
     */
    public function getHost(): string
    {
        return $this->host;
    }

        $apiSecret = config('livekit.api_secret');
        $this->apiSecret = !empty($apiSecret) ? $apiSecret : (env('LIVEKIT_API_SECRET') ?: 'dev_secret_key_at_least_32_characters_long!');
    /**
     * Generate an admin JWT token for server-to-server RPC calls.
     */
    public function generateAdminToken(int $ttl = 120): string
    {
        $tokenOptions = (new AccessTokenOptions)
            ->setTtl($ttl);

        $videoGrant = (new VideoGrant)
            ->setRoomCreate(true)
            ->setRoomList(true)
            ->setRoomAdmin(true);

        $token = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);
        $token->setGrant($videoGrant);

        return $token->toJwt();
    }

    /**
     * Create a new class instance.
     * Create or configure a LiveKit room.
     * Generate a participant or host access token to join a room.
     */
    public function __construct()
    public function createRoom(string $roomName, array $options = [])
    public function generateJoinToken(
        string $roomName,
        string $identity,
        string $name,
        bool $isHost = false,
        string $role = 'participant',
        ?int $ttl = null,
        array $metadata = []
    ): string {
        $tokenOptions = (new AccessTokenOptions)
            ->setIdentity($identity)
            ->setName($name)
            ->setTtl($ttl ?? $this->tokenTtl);

        if (! empty($metadata)) {
            $tokenOptions->setMetadata(json_encode($metadata));
        }

        $canPublish = ($role !== 'viewer');

        $videoGrant = (new VideoGrant)
            ->setRoomName($roomName)
            ->setRoomJoin(true)
            ->setCanPublish($canPublish)
            ->setCanSubscribe(true)
            ->setCanPublishData(true)
            ->setRoomAdmin($isHost);

        $accessToken = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);
        $accessToken->setGrant($videoGrant);

        return $accessToken->toJwt();
    }

    /**
     * Explicitly create a room on the LiveKit SFU via Twirp JSON API.
     */
    public function createRoom(string $roomName, array $options = []): array
    {
        //
        try {
            $client = new RoomServiceClient($this->host, $this->apiKey, $this->apiSecret);
            $roomOptions = (new RoomCreateOptions())
                ->setName($roomName)
                ->setEmptyTimeout($options['empty_timeout'] ?? 300)
                ->setMaxParticipants($options['max_participants'] ?? 50);
            $adminToken = $this->generateAdminToken(120);

            return $client->createRoom($roomOptions);
        } catch (Exception $e) {
            Log::error('LiveKit createRoom error: ' . $e->getMessage());
            throw $e;
            $payload = [
                'name' => $roomName,
                'empty_timeout' => $options['empty_timeout'] ?? 300,
                'max_participants' => $options['max_participants'] ?? 12,
            ];

            $response = Http::withToken($adminToken)
                ->asJson()
                ->timeout(5)
                ->post("{$this->host}/twirp/livekit.RoomService/CreateRoom", $payload);

            if ($response->successful()) {
                return $response->json() ?? ['name' => $roomName];
            }

            Log::warning('LiveKit createRoom API warning: '.$response->status().' '.$response->body());

            return ['name' => $roomName];
        } catch (Throwable $e) {
            Log::warning('LiveKit createRoom exception (LiveKit will create implicitly on join): '.$e->getMessage());

            return ['name' => $roomName];
        }
    }

    /**
     * Generate an access token to join a room.
     * Terminate and delete a room on the LiveKit SFU via Twirp JSON API.
     */
    public function generateJoinToken(string $roomName, string $identity, ?string $name = null, array $metadata = []): string
    public function deleteRoom(string $roomName): bool
    {
        try {
            $tokenOptions = (new AccessTokenOptions())
                ->setIdentity($identity)
                ->setTtl(6 * 60 * 60); // 6 hours
            $adminToken = $this->generateAdminToken(120);

            if ($name) {
                $tokenOptions->setName($name);
            }
            $response = Http::withToken($adminToken)
                ->asJson()
                ->timeout(5)
                ->post("{$this->host}/twirp/livekit.RoomService/DeleteRoom", [
                    'room' => $roomName,
                ]);

            if (!empty($metadata)) {
                $tokenOptions->setMetadata(json_encode($metadata));
            if ($response->successful()) {
                return true;
            }

            $accessToken = new AccessToken($this->apiKey, $this->apiSecret, $tokenOptions);
            Log::warning('LiveKit deleteRoom response: '.$response->status().' - '.$response->body());

            $grant = (new VideoGrant())
                ->setRoomJoin(true)
                ->setRoomName($roomName)
                ->setCanPublish(true)
                ->setCanSubscribe(true);
            return false;
        } catch (Throwable $e) {
            Log::error('LiveKit deleteRoom exception: '.$e->getMessage());

            $accessToken->setGrant($grant);
            return false;
        }
    }

            return $accessToken->toJwt();
        } catch (Exception $e) {
            Log::error('LiveKit generateJoinToken error: ' . $e->getMessage());
            throw $e;
    /**
     * Verify LiveKit webhook signature and SHA256 checksum, then decode payload.
     *
     * @param  string  $rawBody  The raw body from $request->getContent()
     * @param  string|null  $authHeader  The Authorization header from the request
     * @return array The decoded webhook event data
     *
     * @throws InvalidArgumentException
     */
    public function verifyWebhook(string $rawBody, ?string $authHeader): array
    {
        if (empty($authHeader)) {
            throw new InvalidArgumentException('Authorization header is missing.');
        }

        // Strip "Bearer " prefix if present
        if (str_starts_with($authHeader, 'Bearer ')) {
            $authHeader = substr($authHeader, 7);
        }

        try {
            $decoded = JWT::decode($authHeader, new Key($this->apiSecret, 'HS256'));
        } catch (Throwable $e) {
            throw new InvalidArgumentException('Invalid authorization token: '.$e->getMessage());
        }

        if (! isset($decoded->iss) || $decoded->iss !== $this->apiKey) {
            throw new InvalidArgumentException('Invalid webhook issuer.');
        }

        $expectedHash = base64_encode(hash('sha256', $rawBody, true));
        $tokenSha256 = $decoded->sha256 ?? null;

        if ($tokenSha256 !== $expectedHash) {
            throw new InvalidArgumentException('Webhook body checksum does not match sha256 claim.');
        }

        $payload = json_decode($rawBody, true);
        if (! is_array($payload)) {
            throw new InvalidArgumentException('Webhook body is not valid JSON.');
        }

        return $payload;
    }
}
