<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Registrar, Cashier, Guidance, Clinic
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_daily_queue')->default(100);
            $table->unsignedInteger('serving_time_minutes')->default(15); // avg time per person
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offices');
    }
};
