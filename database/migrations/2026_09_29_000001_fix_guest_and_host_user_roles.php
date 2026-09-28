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
        // 1. Strictly mark all guest users (device guests, username starting with guest_, or accounts without email/password) as role='guest' and is_guest=true
        DB::table('users')
            ->where('role', '!=', 'admin')
            ->where(function ($query) {
                $query->where('username', 'like', 'guest_%')
                    ->orWhere('is_guest', true)
                    ->orWhereNull('email')
                    ->orWhere('email', '')
                    ->orWhereNull('password')
                    ->orWhere('password', '');
            })
            ->update([
                'role' => 'guest',
                'is_guest' => true,
            ]);

        // 2. Real host accounts with an email and password must have role='host' and is_guest=false
        DB::table('users')
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->whereNotNull('password')
            ->where('password', '!=', '')
            ->where('role', '!=', 'admin')
            ->where(function ($query) {
                $query->whereNull('username')
                    ->orWhere('username', 'not like', 'guest_%');
            })
            ->update([
                'role' => 'host',
                'is_guest' => false,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
