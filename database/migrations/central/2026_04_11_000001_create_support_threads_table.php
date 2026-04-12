<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        Schema::create('support_threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('thread_type', 32)->default('support');
            $table->string('subject');
            $table->string('status', 32)->default('open');
            $table->timestamp('last_message_at')->nullable()->index();
            $table->timestamp('tenant_last_read_at')->nullable();
            $table->timestamp('central_last_read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_threads');
    }
};
