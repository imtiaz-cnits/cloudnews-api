<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\MeetingParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeetingParticipant>
 */
class MeetingParticipantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            //
            'meeting_id' => Meeting::factory(),
            'user_id' => User::factory(),
            'role' => 'participant',
            'joined_at' => now(),
            'left_at' => null,
        ];
    }
}
