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
        Schema::create('meeting_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->string('meeting_code')->index();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_name')->default('Participant');
            $table->enum('type', ['text', 'image', 'video', 'audio', 'document'])->default('text');
            $table->text('text')->nullable();
            $table->string('file_name')->nullable();
            $table->string('file_size')->nullable();
            $table->text('media_url')->nullable();
            $table->string('duration')->nullable();
            $table->string('client_msg_id')->nullable()->index();
            $table->timestamps();

            $table->index(['meeting_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meeting_messages');
    }
};
