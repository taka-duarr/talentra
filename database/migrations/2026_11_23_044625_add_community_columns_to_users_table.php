<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('forum_guidelines_accepted_at')->nullable()->after('blocked_at');
            $table->timestamp('forum_banned_until')->nullable()->after('forum_guidelines_accepted_at');
            $table->timestamp('ranking_excluded_at')->nullable()->after('forum_banned_until');
            $table->boolean('email_notifications')->default(true)->after('ranking_excluded_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['forum_guidelines_accepted_at', 'forum_banned_until', 'ranking_excluded_at', 'email_notifications']);
        });
    }
};
