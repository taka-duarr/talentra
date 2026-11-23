<?php

namespace App\Models;

use Database\Factories\UserPointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'points', 'reason', 'source_key', 'created_at'])]
class UserPoint extends Model
{
    /** @use HasFactory<UserPointFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reasonLabel(): string
    {
        return match ($this->reason) {
            'tryout_completed' => 'Menyelesaikan try out',
            'tryout_passed' => 'Lolos ambang batas',
            'chapter_completed' => 'Menyelesaikan satu bab materi',
            'best_answer' => 'Jawaban terbaik di forum',
            default => $this->reason,
        };
    }
}
