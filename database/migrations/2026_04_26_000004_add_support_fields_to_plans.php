<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            if (! Schema::connection('central')->hasColumn('plans', 'support_level')) {
                $table->string('support_level')->nullable()->after('qr_codes_per_office');
            }
            if (! Schema::connection('central')->hasColumn('plans', 'sla_hours')) {
                $table->integer('sla_hours')->nullable()->after('support_level');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::connection('central')->table('plans', function (Blueprint $table) {
            $table->dropColumn(['support_level', 'sla_hours']);
        });
    }
};
