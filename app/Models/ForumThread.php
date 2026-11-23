<?php

namespace App\Models;

use App\Enums\ThreadScope;
use App\Enums\ThreadTag;
use Database\Factories\ForumThreadFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable(['user_id', 'scope', 'category_id', 'material_id', 'title', 'body', 'tag', 'last_activity_at'])]
class ForumThread extends Model
{
    /** @use HasFactory<ForumThreadFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scope' => ThreadScope::class,
            'tag' => ThreadTag::class,
            'is_pinned' => 'boolean',
            'is_locked' => 'boolean',
            'hidden_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<ForumThread>  $query
     */
    public function scopeVisible(Builder $query): void
    {
        $query->whereNull('hidden_at');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return BelongsTo<Material, $this>
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * @return HasMany<ForumReply, $this>
     */
    public function replies(): HasMany
    {
        return $this->hasMany(ForumReply::class)->orderBy('created_at');
    }

    /**
     * @return BelongsTo<ForumReply, $this>
     */
    public function bestReply(): BelongsTo
    {
        return $this->belongsTo(ForumReply::class, 'best_reply_id');
    }

    /**
     * @return MorphMany<ForumVote, $this>
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(ForumVote::class, 'votable');
    }

    /**
     * @return MorphMany<ForumReport, $this>
     */
    public function reports(): MorphMany
    {
        return $this->morphMany(ForumReport::class, 'reportable');
    }

    public function isHidden(): bool
    {
        return $this->hidden_at !== null;
    }

    /**
     * The category whose subscription decides access, if any.
     */
    public function accessCategoryId(): ?int
    {
        return match ($this->scope) {
            ThreadScope::Material => $this->material?->categoryId(),
            default => null,
        };
    }

    public function isReadableBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isHidden()) {
            return false;
        }

        return $this->scope !== ThreadScope::Material || $this->material?->isAccessibleBy($user);
    }
}
