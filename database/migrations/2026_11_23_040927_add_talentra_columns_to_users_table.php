<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('name');
            $table->string('role', 16)->default('user')->after('password');
            $table->foreignId('target_category_id')->nullable()->after('role')->constrained('categories')->nullOnDelete();
            $table->boolean('hide_from_ranking')->default(false)->after('target_category_id');
            $table->timestamp('blocked_at')->nullable()->after('hide_from_ranking');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('target_category_id');
            $table->dropColumn(['role', 'display_name', 'hide_from_ranking', 'blocked_at']);
        });
    }
};
