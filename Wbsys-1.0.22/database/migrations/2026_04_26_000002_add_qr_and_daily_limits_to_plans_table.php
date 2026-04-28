<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('plans', 'qr_codes_per_office')) {
                $table->integer('qr_codes_per_office')->nullable()->after('max_offices');
            }

            if (! Schema::connection('central')->hasColumn('plans', 'daily_service_limit')) {
                $table->integer('daily_service_limit')->nullable()->after('qr_codes_per_office');
            }

            if (! Schema::connection('central')->hasColumn('plans', 'qr_description')) {
                $table->text('qr_description')->nullable()->after('daily_service_limit');
            }

            if (! Schema::connection('central')->hasColumn('plans', 'daily_service_description')) {
                $table->text('daily_service_description')->nullable()->after('qr_description');
            }
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            if (Schema::connection('central')->hasColumn('plans', 'daily_service_description')) {
                $table->dropColumn('daily_service_description');
            }
            if (Schema::connection('central')->hasColumn('plans', 'qr_description')) {
                $table->dropColumn('qr_description');
            }
            if (Schema::connection('central')->hasColumn('plans', 'daily_service_limit')) {
                $table->dropColumn('daily_service_limit');
            }
            if (Schema::connection('central')->hasColumn('plans', 'qr_codes_per_office')) {
                $table->dropColumn('qr_codes_per_office');
            }
        });
    }
};
