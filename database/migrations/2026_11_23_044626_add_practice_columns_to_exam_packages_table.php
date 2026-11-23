<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->boolean('is_practice')->default(false)->after('is_free');
            $table->foreignId('topic_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('question_limit')->nullable()->after('duration_minutes');
        });
    }

    public function down(): void
    {
        Schema::table('exam_packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('topic_id');
            $table->dropColumn(['is_practice', 'question_limit']);
        });
    }
};
