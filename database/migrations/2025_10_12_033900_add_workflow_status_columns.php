<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->string('workflow_stage')->default('draft')->after('description');
            $table->timestamp('obtl_uploaded_at')->nullable()->after('workflow_stage');
            $table->timestamp('materials_uploaded_at')->nullable()->after('obtl_uploaded_at');
        });

        Schema::table('obtl_documents', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->after('uploaded_at');
            $table->timestamp('processed_at')->nullable()->after('processing_status');
            $table->text('error_message')->nullable()->after('processed_at');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->string('processing_status')->default('pending')->after('uploaded_at');
            $table->timestamp('processed_at')->nullable()->after('processing_status');
            $table->text('processing_error')->nullable()->after('processed_at');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn([
                'workflow_stage',
                'obtl_uploaded_at',
                'materials_uploaded_at',
            ]);
        });

        Schema::table('obtl_documents', function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'processed_at',
                'error_message',
            ]);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn([
                'processing_status',
                'processed_at',
                'processing_error',
            ]);
        });
    }
};