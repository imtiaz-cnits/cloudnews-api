<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'meeting_code',
        'user_id',
        'client_msg_id',
        'sender_name',
        'type',
        'text',
        'file_name',
        'file_size',
        'media_url',
        'duration',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $appends = [
        'sender_id',
        'message',
        'file_url',
        'file_type',
        'timestamp',
    ];

    public function getSenderIdAttribute()
    {
        return $this->user_id;
    }

    public function getMessageAttribute()
    {
        return $this->text;
    }

    public function getFileUrlAttribute()
    {
        return $this->media_url;
    }

    public function getFileTypeAttribute()
    {
        return $this->type;
    }

    public function getTimestampAttribute()
    {
        return $this->created_at?->toISOString() ?? $this->created_at;
    }

    public function meeting(): BelongsTo
    {
        return $this->belongsTo(Meeting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
