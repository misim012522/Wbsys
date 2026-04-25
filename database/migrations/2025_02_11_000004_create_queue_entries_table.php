<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('queue_number'); // e.g. 1, 2, 3...
            $table->date('queue_date');
            $table->string('status')->default('waiting'); // waiting, called, serving, completed, cancelled, no_show
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('reference_code', 12)->unique(); // for QR / student lookup
            $table->timestamps();

            $table->index(['office_id', 'queue_date', 'status']);
            $table->unique(['office_id', 'queue_date', 'queue_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_entries');
    }
};
