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
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->json('temporary_answers')->nullable()->after('question_item_ids');
            $table->json('skipped_item_ids')->nullable()->after('temporary_answers');
            $table->integer('current_question_index')->default(0)->after('skipped_item_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropColumn(['temporary_answers', 'skipped_item_ids', 'current_question_index']);
        });
    }
};
