<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('queue_entries', function (Blueprint $table) {
                $table->string('guest_name')->nullable()->after('user_id');
                $table->string('guest_contact')->nullable()->after('guest_name');
            });

            Schema::table('appointments', function (Blueprint $table) {
                $table->string('guest_name')->nullable()->after('user_id');
                $table->string('guest_contact')->nullable()->after('guest_name');
            });

            return;
        }

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE queue_entries MODIFY user_id BIGINT UNSIGNED NULL');
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_contact')->nullable()->after('guest_name');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE appointments MODIFY user_id BIGINT UNSIGNED NULL');
        Schema::table('appointments', function (Blueprint $table) {
            $table->string('guest_name')->nullable()->after('user_id');
            $table->string('guest_contact')->nullable()->after('guest_name');
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('queue_entries', function (Blueprint $table) {
                $table->dropColumn(['guest_name', 'guest_contact']);
            });

            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn(['guest_name', 'guest_contact']);
            });

            return;
        }

        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['guest_name', 'guest_contact']);
        });
        DB::statement('ALTER TABLE queue_entries MODIFY user_id BIGINT UNSIGNED NOT NULL');
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn(['guest_name', 'guest_contact']);
        });
        DB::statement('ALTER TABLE appointments MODIFY user_id BIGINT UNSIGNED NOT NULL');
        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
