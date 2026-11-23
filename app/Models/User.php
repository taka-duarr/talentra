<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

#[Fillable(['name', 'display_name', 'email', 'password', 'target_category_id', 'hide_from_ranking', 'email_notifications'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Category ids the user can access, memoised per request.
     *
     * @var Collection<int, int>|null
     */
    protected ?Collection $accessibleCategoryIdsCache = null;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'role' => 'user',
        'hide_from_ranking' => false,
        'email_notifications' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'hide_from_ranking' => 'boolean',
            'blocked_at' => 'datetime',
            'forum_guidelines_accepted_at' => 'datetime',
            'forum_banned_until' => 'datetime',
            'ranking_excluded_at' => 'datetime',
            'email_notifications' => 'boolean',
        ];
    }

    public function isBannedFromForum(): bool
    {
        return $this->forum_banned_until !== null && $this->forum_banned_until->isFuture();
    }

    public function hasAcceptedForumGuidelines(): bool
    {
        return $this->forum_guidelines_accepted_at !== null;
    }

    public function isExcludedFromRanking(): bool
    {
        return $this->ranking_excluded_at !== null;
    }

    /**
     * @return HasMany<UserPoint, $this>
     */
    public function points(): HasMany
    {
        return $this->hasMany(UserPoint::class);
    }

    public function isAdmin(): bool
    {
        return $this->role->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isBlocked(): bool
    {
        return $this->blocked_at !== null;
    }

    public function publicName(): string
    {
        return $this->display_name ?: $this->name;
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function targetCategory(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }

    /**
     * @return HasMany<Subscription, $this>
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * @return HasMany<Order, $this>
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * @return HasMany<ExamAttempt, $this>
     */
    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    /**
     * @return HasMany<MaterialProgress, $this>
     */
    public function materialProgress(): HasMany
    {
        return $this->hasMany(MaterialProgress::class);
    }

    /**
     * @return Collection<int, Subscription>
     */
    public function activeSubscriptions(): Collection
    {
        return $this->subscriptions()->active()->with('plan.categories')->orderByDesc('ends_at')->get();
    }

    public function hasActiveSubscription(): bool
    {
        return $this->subscriptions()->active()->exists();
    }

    /**
     * @return Collection<int, int>
     */
    public function accessibleCategoryIds(): Collection
    {
        if ($this->accessibleCategoryIdsCache !== null) {
            return $this->accessibleCategoryIdsCache;
        }

        $subscriptions = $this->activeSubscriptions();

        if ($subscriptions->contains(fn (Subscription $subscription) => $subscription->plan->is_all_access)) {
            return $this->accessibleCategoryIdsCache = Category::query()->pluck('id');
        }

        return $this->accessibleCategoryIdsCache = $subscriptions
            ->flatMap(fn (Subscription $subscription) => $subscription->plan->categories->pluck('id'))
            ->unique()
            ->values();
    }

    public function canAccessCategory(int $categoryId): bool
    {
        return $this->isAdmin() || $this->accessibleCategoryIds()->contains($categoryId);
    }
}
