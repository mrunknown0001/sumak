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
        Schema::table('table_of_specifications', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('term', ['midterm', 'final'])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('table_of_specifications', function (Blueprint $table) {
            $table->dropForeign(['course_id']);
            $table->dropColumn('course_id');
            $table->string('term')->nullable()->change();
        });
    }
};
