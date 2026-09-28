<?php

namespace App\Services;

use App\Exceptions\HostAlreadyInMeetingException;
use App\Exceptions\HostSessionInvalidException;
use App\Models\Meeting;
use App\Models\MeetingHostSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HostSessionService
{
    /**
     * Default lease duration in seconds (90 seconds).
     */
    public const DEFAULT_LEASE_SECONDS = 90;

    /**
     * Acquire a host session lock for the given user and meeting.
     *
     * Invariants:
     * - A user can have at most one active host session globally across all meetings.
     * - Atomic row-level lock (User::lockForUpdate()) serializes concurrent requests.
     * - Stale/expired sessions (expires_at <= now()) are reclaimed safely.
     * - Idempotent re-entry to the SAME meeting is allowed ONLY if the caller presents
     *   the exact matching session_token.
     * - If another device attempts to join as host (missing/wrong token or different meeting),
     *   it is rejected with HostAlreadyInMeetingException and the existing token is NEVER leaked.
     *
     * @throws HostAlreadyInMeetingException
     */
    public function acquireHostLock(
        User $user,
        Meeting $meeting,
        ?string $existingSessionToken = null,
        ?int $leaseSeconds = null
    ): MeetingHostSession {
        $ttl = $leaseSeconds ?? (int) config('meeting.host_session_ttl', self::DEFAULT_LEASE_SECONDS);

        $executeAcquisition = function () use ($user, $meeting, $existingSessionToken, $ttl) {
            // Lock user row exclusively to serialize concurrent join/start requests for this account
            User::where('id', $user->id)->lockForUpdate()->first();

            $existingSession = MeetingHostSession::where('user_id', $user->id)->first();

            // Case 1: No active or past host session exists
            if (! $existingSession) {
                return MeetingHostSession::create([
                    'user_id' => $user->id,
                    'meeting_id' => $meeting->id,
                    'meeting_code' => $meeting->meeting_code,
                    'room_name' => $meeting->room_name,
                    'session_token' => Str::random(64),
                    'last_seen_at' => now(),
                    'expires_at' => now()->addSeconds($ttl),
                ]);
            }

            // Case 2: Existing host session is expired (stale lease) -> safely reclaim
            if ($existingSession->expires_at->isPast()) {
                $newToken = Str::random(64);
                $existingSession->update([
                    'meeting_id' => $meeting->id,
                    'meeting_code' => $meeting->meeting_code,
                    'room_name' => $meeting->room_name,
                    'session_token' => $newToken,
                    'last_seen_at' => now(),
                    'expires_at' => now()->addSeconds($ttl),
                ]);

                return $existingSession->fresh();
            }

            // Case 3: Existing host session is ACTIVE
            // Subcase 3a: Existing session belongs to a DIFFERENT meeting
            if ($existingSession->meeting_id !== $meeting->id) {
                throw new HostAlreadyInMeetingException(
                    $existingSession->meeting_code,
                    $existingSession->expires_at->toISOString(),
                    'This account is already hosting another meeting.',
                    409
                );
            }

            // Subcase 3b: Existing session belongs to the SAME meeting
            // Only allow idempotent re-entry if the caller presents the matching session_token!
            if ($existingSessionToken && hash_equals($existingSession->session_token, $existingSessionToken)) {
                $existingSession->update([
                    'last_seen_at' => now(),
                    'expires_at' => now()->addSeconds($ttl),
                ]);

                return $existingSession->fresh();
            }

            // Caller does not have the valid session token (e.g. secondary device or lost token).
            // Do NOT leak the existing session_token!
            throw new HostAlreadyInMeetingException(
                $existingSession->meeting_code,
                $existingSession->expires_at->toISOString(),
                'This account is already hosting an active session for this meeting.',
                409
            );
        };

        // If caller is already in a DB transaction, execute directly; otherwise wrap in transaction
        if (DB::transactionLevel() > 0) {
            return $executeAcquisition();
        }

        return DB::transaction($executeAcquisition);
    }

    /**
     * Extend the lease of an active host session.
     * Both user_id, meeting_id, and exact session_token must match and not be expired.
     *
     * @throws HostSessionInvalidException
     */
    public function heartbeat(
        User $user,
        Meeting $meeting,
        string $sessionToken,
        ?int $leaseSeconds = null
    ): MeetingHostSession {
        if (empty($sessionToken)) {
            throw new HostSessionInvalidException('Host session token is required.', 403);
        }

        $ttl = $leaseSeconds ?? (int) config('meeting.host_session_ttl', self::DEFAULT_LEASE_SECONDS);

        $session = MeetingHostSession::where('user_id', $user->id)
            ->where('meeting_id', $meeting->id)
            ->where('session_token', $sessionToken)
            ->first();

        if (! $session || $session->expires_at->isPast()) {
            throw new HostSessionInvalidException('Host session is invalid or has expired.', 403);
        }

        $session->update([
            'last_seen_at' => now(),
            'expires_at' => now()->addSeconds($ttl),
        ]);

        return $session->fresh();
    }

    /**
     * Release a host session lock on clean leave or meeting end.
     * The exact session_token is strictly required to prevent older or secondary devices
     * from releasing an active session.
     */
    public function releaseHostLock(
        User $user,
        Meeting $meeting,
        string $sessionToken
    ): bool {
        if (empty($sessionToken)) {
            return false;
        }

        $deleted = MeetingHostSession::where('user_id', $user->id)
            ->where('meeting_id', $meeting->id)
            ->where('session_token', $sessionToken)
            ->delete();

        return $deleted > 0;
    }

    /**
     * Determine whether the host is currently active in the given meeting.
     * Host is active if and only if an unexpired MeetingHostSession exists for the meeting and host.
     */
    public function isHostActive(Meeting $meeting): bool
    {
        return MeetingHostSession::where('meeting_id', $meeting->id)
            ->where('user_id', $meeting->host_id)
            ->where('expires_at', '>', now())
            ->exists();
    }
}
