<?php

namespace App\Models;

use Database\Factories\MeetingFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Meeting extends Model
{
    /** @use HasFactory<MeetingFactory> */
    /** @use HasFactory<MeetingFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'host_id',
        'room_name',
        'meeting_code',
        'title',
        'passcode',
        'is_active',
        'is_locked',
        'max_participants',
        'started_at',
        'ended_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'passcode',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'passcode' => 'encrypted',
            'is_active' => 'boolean',
            'is_locked' => 'boolean',
            'max_participants' => 'integer',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique human-readable 9-digit meeting code formatted as XXX-XXX-XXX.
     */
    public static function generateMeetingCode(): string
    {
        do {
            $digits = str_pad((string) random_int(100000000, 999999999), 9, '0', STR_PAD_LEFT);
            $formatted = substr($digits, 0, 3).'-'.substr($digits, 3, 3).'-'.substr($digits, 6, 3);
        } while (static::where('meeting_code', $formatted)->exists());

        return $formatted;
    }

    /**
     * Generate a unique room name for LiveKit SFU.
     */
    public static function generateRoomName(): string
    {
        do {
            $name = 'cloudnews-'.Str::lower(Str::random(12));
        } while (static::where('room_name', $name)->exists());

        return $name;
    }

    /**
     * Check if meeting requires a passcode.
     */
    public function hasPasscode(): bool
    {
        return ! empty($this->passcode);
    }

    /**
     * Verify if provided passcode matches the meeting's passcode.
     */
    public function verifyPasscode(?string $passcode): bool
    {
        if (! $this->hasPasscode()) {
            return true;
        }

        return (string) $this->passcode === (string) $passcode;
    }

    /**
     * Meeting host user.
     *
     * @return BelongsTo<User, $this>
     */
    public function host(): BelongsTo
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    /**
     * All participant logs for this meeting.
     *
     * @return HasMany<MeetingParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(MeetingParticipant::class, 'meeting_id');
    }

    /**
     * Currently active participants in this meeting.
     *
     * @return HasMany<MeetingParticipant, $this>
     */
    public function activeParticipants(): HasMany
    {
        return $this->participants()->whereNull('left_at');
    }

    /**
     * Participating users.
     *
     * @return BelongsToMany<User, $this>
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'meeting_participants', 'meeting_id', 'user_id')
            ->withPivot(['role', 'joined_at', 'left_at'])
            ->withTimestamps();
    }

    /**
     * Scope a query to only include active meetings.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }
}
