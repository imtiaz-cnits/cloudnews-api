<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingHostSession extends Model
{
    use HasFactory;

    protected $table = 'meeting_host_sessions';

    protected $fillable = [
        'user_id',
        'meeting_id',
        'meeting_code',
        'room_name',
        'session_token',
        'last_seen_at',
        'expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     * Prevents accidental leakage in logs or default JSON models.
     */
    protected $hidden = [
        'session_token',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * User hosting the meeting.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Meeting associated with this host session.
     */
    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Check whether this host session lease has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }
}
