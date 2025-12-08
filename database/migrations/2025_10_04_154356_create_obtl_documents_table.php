<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('obtl_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type', 50)->default('pdf');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->timestamp('uploaded_at');
            $table->timestamps();
            
            $table->index(['course_id', 'uploaded_at']);
        });

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('course_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('file_path');
            $table->string('file_type', 50)->default('pdf');
            $table->unsignedBigInteger('file_size')->default(0);
            $table->text('content_summary')->nullable();
            $table->text('short_content_summary')->nullable();
            $table->timestamp('uploaded_at');
            $table->timestamps();
            
            $table->index(['course_id', 'uploaded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
        Schema::dropIfExists('obtl_documents');
    }
};