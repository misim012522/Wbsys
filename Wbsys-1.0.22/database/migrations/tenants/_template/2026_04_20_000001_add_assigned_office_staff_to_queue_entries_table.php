<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('queue_entries', 'assigned_staff_user_id')) {
                $table->foreignId('assigned_staff_user_id')
                    ->nullable()
                    ->after('user_id')
                    ->constrained('users')
                    ->nullOnDelete();

                $table->index(['office_id', 'assigned_staff_user_id', 'queue_date'], 'queue_entries_office_staff_date_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::table('queue_entries', function (Blueprint $table) {
            if (Schema::hasColumn('queue_entries', 'assigned_staff_user_id')) {
                $table->dropIndex('queue_entries_office_staff_date_idx');
                $table->dropConstrainedForeignId('assigned_staff_user_id');
            }
        });
    }
};
