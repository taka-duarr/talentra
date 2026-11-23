<?php

namespace App\Models;

use Database\Factories\ExamSectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['exam_package_id', 'subject_id', 'name', 'passing_score', 'sort_order'])]
class ExamSection extends Model
{
    /** @use HasFactory<ExamSectionFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<ExamPackage, $this>
     */
    public function package(): BelongsTo
    {
        return $this->belongsTo(ExamPackage::class, 'exam_package_id');
    }

    /**
     * @return BelongsTo<Subject, $this>
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /**
     * @return BelongsToMany<Question, $this>
     */
    public function questions(): BelongsToMany
    {
        return $this->belongsToMany(Question::class, 'exam_section_question')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }
}
