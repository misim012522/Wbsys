<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Set email_verified_at for users who were approved by admin but never had it set (so they can log in).
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNotNull('approved_at')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => DB::raw('approved_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Cannot safely reverse; leave data as is.
    }
};
