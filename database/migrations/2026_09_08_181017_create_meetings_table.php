<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('host_id')->constrained('users')->cascadeOnDelete();
            $table->string('room_name')->unique();
            $table->string('meeting_code', 20)->unique()->index();
            $table->string('title');
            $table->text('passcode')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_locked')->default(false);
            $table->unsignedSmallInteger('max_participants')->default(12);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meetings');
    }
};
