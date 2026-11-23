<?php

namespace App\Models;

use App\Enums\ContentStatus;
use Database\Factories\ExamPackageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id', 'topic_id', 'title', 'slug', 'description', 'duration_minutes', 'question_limit', 'is_free', 'is_practice', 'max_attempts',
    'shuffle_questions', 'shuffle_options', 'counts_for_ranking', 'score_correct', 'score_wrong',
    'score_blank', 'passing_score', 'status', 'published_at',
])]
class ExamPackage extends Model
{
    /** @use HasFactory<ExamPackageFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
            'is_practice' => 'boolean',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'counts_for_ranking' => 'boolean',
            'status' => ContentStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * @param  Builder<ExamPackage>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    /**
     * Try out packages shown in the catalog; topic practice packages are hidden.
     *
     * @param  Builder<ExamPackage>  $query
     */
    public function scopeListed(Builder $query): void
    {
        $query->published()->where('is_practice', false);
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<ExamSection, $this>
     */
    public function sections(): HasMany
    {
        return $this->hasMany(ExamSection::class)->orderBy('sort_order');
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function questionCount(): int
    {
        return (int) $this->sections->sum(fn (ExamSection $section) => $section->questions_count ?? $section->questions()->count());
    }

    public function isAccessibleBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->is_free || $user->canAccessCategory($this->category_id);
    }

    public function attemptsUsedBy(User $user): int
    {
        return $this->attempts()->where('user_id', $user->id)->count();
    }

    public function hasAttemptsLeftFor(User $user): bool
    {
        return $this->max_attempts === null || $this->attemptsUsedBy($user) < $this->max_attempts;
    }
}
