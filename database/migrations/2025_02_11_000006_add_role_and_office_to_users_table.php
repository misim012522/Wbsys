<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student')->after('email'); // admin, office_staff, student
            $table->foreignId('office_id')->nullable()->after('role')->constrained()->nullOnDelete();
            $table->string('student_id')->nullable()->after('office_id'); // for students
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['office_id']);
            $table->dropColumn(['role', 'office_id', 'student_id']);
        });
    }
};
