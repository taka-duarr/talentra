<?php

namespace App\Models;

use App\Enums\AttemptStatus;
use Database\Factories\ExamAttemptFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id', 'exam_package_id', 'status', 'started_at', 'ends_at', 'submitted_at', 'question_order',
    'option_order', 'score', 'max_score', 'correct_count', 'wrong_count', 'blank_count', 'section_scores',
    'passed', 'is_first_attempt', 'tab_switches',
])]
class ExamAttempt extends Model
{
    /** @use HasFactory<ExamAttemptFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'ends_at' => 'datetime',
            'submitted_at' => 'datetime',
            'question_order' => 'array',
            'option_order' => 'array',
            'section_scores' => 'array',
            'score' => 'float',
            'max_score' => 'float',
            'passed' => 'boolean',
            'is_first_attempt' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<ExamPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    /**
     * @return HasMany<AttemptAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    /**
     * @return HasOne<AiAnalysis, $this>
     */
    public function analysis(): HasOne
    {
        return $this->hasOne(AiAnalysis::class);
    }

    public function isInProgress(): bool
    {
        return $this->status === AttemptStatus::InProgress;
    }

    public function isExpired(): bool
    {
        return $this->ends_at->isPast();
    }

    public function remainingSeconds(): int
    {
        return max(0, (int) now()->diffInSeconds($this->ends_at, false));
    }

    public function percentage(): float
    {
        return $this->max_score > 0 ? round($this->score / $this->max_score * 100, 1) : 0.0;
    }

    public function durationMinutes(): int
    {
        $end = $this->submitted_at ?? $this->ends_at;

        return (int) ceil($this->started_at->diffInSeconds($end) / 60);
    }
}
