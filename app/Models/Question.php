<?php

namespace App\Models;

use App\Enums\ContentStatus;
use App\Enums\QuestionType;
use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['subject_id', 'topic_id', 'type', 'body', 'explanation', 'answer_text', 'difficulty', 'status', 'source', 'created_by'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'status' => ContentStatus::class,
            'difficulty' => 'integer',
        ];
    }

    /**
     * @param  Builder<Question>  $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', ContentStatus::Published);
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsTo<Topic, $this>
     */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<QuestionOption, $this>
     */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order');
    }

    /**
     * @return BelongsToMany<ExamSection, $this>
     */
    public function sections(): BelongsToMany
    {
        return $this->belongsToMany(ExamSection::class, 'exam_section_question')->withPivot('sort_order');
    }

    /**
     * Highest score a single question can award, used for max_score.
     */
    public function maxPoints(ExamPackage $package): float
    {
        if ($this->type === QuestionType::Weighted) {
            return (float) $this->options->max('weight');
        }

        return (float) $package->score_correct;
    }
}
