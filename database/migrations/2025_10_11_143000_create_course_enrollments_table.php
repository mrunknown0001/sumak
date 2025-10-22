<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->timestamp('enrolled_at');
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']); // Prevent duplicate enrollments
            $table->index(['user_id', 'enrolled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_enrollments');
    }
};