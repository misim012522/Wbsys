<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'central';

    public function up(): void
    {
        if (! Schema::connection('central')->hasTable('support_threads')) {
            Schema::connection('central')->create('support_threads', function (Blueprint $table) {
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

        if (! Schema::connection('central')->hasTable('support_messages')) {
            Schema::connection('central')->create('support_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('thread_id')->constrained('support_threads')->cascadeOnDelete();
                $table->string('sender_type', 32);
                $table->unsignedBigInteger('sender_id')->nullable();
                $table->string('sender_name');
                $table->string('sender_role', 64)->nullable();
                $table->text('message');
                $table->timestamps();

                $table->index(['thread_id', 'created_at']);
                $table->index(['sender_type', 'sender_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('central')->dropIfExists('support_messages');
        Schema::connection('central')->dropIfExists('support_threads');
    }
};
