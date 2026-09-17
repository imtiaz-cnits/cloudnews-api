<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix all existing host and admin accounts so is_guest is strictly false
        DB::table('users')
            ->whereIn('role', ['host', 'admin'])
            ->update(['is_guest' => false]);

        // Fix any accounts that have a password set and are not explicitly guest role
        DB::table('users')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->where('role', '!=', 'guest')
            ->update(['is_guest' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
