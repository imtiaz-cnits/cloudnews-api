<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
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
            'host_id' => User::factory(),
            'room_name' => Meeting::generateRoomName(),
            'meeting_code' => Meeting::generateMeetingCode(),
            'title' => fake()->sentence(3),
            'passcode' => null,
            'is_active' => true,
            'is_locked' => false,
            'max_participants' => 12,
            'started_at' => now(),
            'ended_at' => null,
        ];
    }
}
