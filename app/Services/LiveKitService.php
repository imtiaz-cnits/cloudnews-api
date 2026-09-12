<?php

namespace App\Services;

use Agence104\LiveKit\AccessToken;
use Agence104\LiveKit\AccessTokenOptions;
use Agence104\LiveKit\VideoGrant;
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
        $this->host = rtrim(config('livekit.url', env('LIVEKIT_URL', 'http://119.28.138.19:7880')), '/');
        $this->apiKey = config('livekit.api_key', env('LIVEKIT_API_KEY', 'devkey'));
        $this->apiSecret = config('livekit.api_secret', env('LIVEKIT_API_SECRET', 'secret_token_for_cloudnews_2026_32chars'));
        $this->tokenTtl = (int) config('livekit.token_ttl', env('LIVEKIT_TOKEN_TTL', 21600)); // 6 hours
    }

    /**
     * Get the configured LiveKit host URL.
     */
    public function getHost(): string
    {
        return $this->host;
    }

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
     * Generate a participant or host access token to join a room.
     */
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
        try {
            $adminToken = $this->generateAdminToken(120);

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

            Log::warning('LiveKit createRoom API warning: ' . $response->status() . ' ' . $response->body());

            return ['name' => $roomName];
        } catch (Throwable $e) {
            Log::warning('LiveKit createRoom exception (LiveKit will create implicitly on join): ' . $e->getMessage());

            return ['name' => $roomName];
        }
    }

    /**
     * Terminate and delete a room on the LiveKit SFU via Twirp JSON API.
     */
    public function deleteRoom(string $roomName): bool
    {
        try {
            $adminToken = $this->generateAdminToken(120);

            $response = Http::withToken($adminToken)
                ->asJson()
                ->timeout(5)
                ->post("{$this->host}/twirp/livekit.RoomService/DeleteRoom", [
                    'room' => $roomName,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('LiveKit deleteRoom response: ' . $response->status() . ' - ' . $response->body());

            return false;
        } catch (Throwable $e) {
            Log::error('LiveKit deleteRoom exception: ' . $e->getMessage());

            return false;
        }
    }

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
            throw new InvalidArgumentException('Invalid authorization token: ' . $e->getMessage());
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
