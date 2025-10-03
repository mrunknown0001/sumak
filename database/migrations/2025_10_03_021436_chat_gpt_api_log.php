<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('chat_gpt_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('request_type'); // content_analysis, tos_generation, quiz_generation, etc.
            $table->string('model', 50); // gpt-4o-mini, gpt-4o, etc.
            $table->integer('total_tokens')->default(0);
            $table->integer('prompt_tokens')->default(0);
            $table->integer('completion_tokens')->default(0);
            $table->float('response_time_ms')->default(0); // Response time in milliseconds
            $table->decimal('estimated_cost', 10, 6)->default(0); // Cost in USD
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('created_at');
            
            // Indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['request_type', 'created_at']);
            $table->index(['success', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chat_gpt_api_logs');
    }
};