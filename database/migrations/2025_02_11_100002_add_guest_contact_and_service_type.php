<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('guest_contact');
            $table->string('guest_phone')->nullable()->after('guest_email');
            $table->string('service_type')->nullable()->after('guest_phone'); // e.g. Transcript, Payment, Counseling
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->string('guest_email')->nullable()->after('guest_contact');
            $table->string('guest_phone')->nullable()->after('guest_email');
            $table->string('appointment_type')->nullable()->after('guest_phone'); // e.g. Transcript, Payment, Counseling
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_phone', 'service_type']);
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->dropColumn(['guest_email', 'guest_phone', 'appointment_type']);
        });
    }
};
