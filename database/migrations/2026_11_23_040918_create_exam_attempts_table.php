<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_package_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('ends_at');
            $table->timestamp('submitted_at')->nullable();
            $table->json('question_order');
            $table->json('option_order')->nullable();
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('blank_count')->default(0);
            $table->json('section_scores')->nullable();
            $table->boolean('passed')->nullable();
            $table->boolean('is_first_attempt')->default(true);
            $table->unsignedSmallInteger('tab_switches')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['exam_package_id', 'is_first_attempt', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
