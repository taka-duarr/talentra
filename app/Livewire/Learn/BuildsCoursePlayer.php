<?php

namespace App\Livewire\Learn;

use App\Enums\AttemptStatus;
use App\Models\Category;
use App\Models\ExamPackage;
use App\Models\Material;
use App\Models\MaterialProgress;
use App\Models\User;
use App\Services\Community\PointService;
use Illuminate\Support\Collection;

trait BuildsCoursePlayer
{
    /**
     * Builds the curriculum tree, progress metrics, and active stage state for a course.
     *
     * @return array<string, mixed>
     */
    protected function buildCoursePlayerData(
        User $user,
        Category $category,
        ?Material $activeMaterial = null,
        ?ExamPackage $activePackage = null,
    ): array {
        $category->load([
            'subjects.chapters.materials' => fn ($query) => $query->published(),
        ]);

        $hasAccess = $user->canAccessCategory($category->id);

        $packages = ExamPackage::query()
            ->listed()
            ->where('category_id', $category->id)
            ->with(['category', 'sections' => fn ($query) => $query->withCount('questions')])
            ->orderByDesc('is_free')
            ->orderBy('id')
            ->get();

        /** @var Collection<int, Material> $allMaterials */
        $allMaterials = $category->subjects
            ->flatMap(fn ($subject) => $subject->chapters->flatMap(fn ($chapter) => $chapter->materials))
            ->values();

        $completedMaterialIds = MaterialProgress::query()
            ->where('user_id', $user->id)
            ->whereIn('material_id', $allMaterials->pluck('id'))
            ->pluck('material_id');

        $userAttempts = $user->examAttempts()
            ->whereIn('exam_package_id', $packages->pluck('id'))
            ->latest('id')
            ->get();

        $submittedPackageIds = $userAttempts
            ->where('status', AttemptStatus::Submitted)
            ->pluck('exam_package_id')
            ->unique()
            ->values();

        $accessibleMaterials = $allMaterials->filter(fn (Material $m) => $hasAccess || $m->is_free)->values();
        $uncompletedAccessibleMaterials = $accessibleMaterials
            ->reject(fn (Material $m) => $completedMaterialIds->contains($m->id))
            ->values();

        $areAccessibleMaterialsDone = $uncompletedAccessibleMaterials->isEmpty();

        // Determine default active item when opening /belajar/{category} without explicit target
        if ($activeMaterial === null && $activePackage === null) {
            if ($uncompletedAccessibleMaterials->isNotEmpty()) {
                $activeMaterial = $uncompletedAccessibleMaterials->first();
            } elseif ($accessibleMaterials->isNotEmpty() && $packages->isNotEmpty()) {
                $firstUnlockedPkg = $packages->first(fn (ExamPackage $pkg) => $pkg->isAccessibleBy($user));
                if ($firstUnlockedPkg) {
                    $activePackage = $firstUnlockedPkg;
                } else {
                    $activeMaterial = $allMaterials->first();
                }
            } elseif ($allMaterials->isNotEmpty()) {
                $activeMaterial = $allMaterials->first();
            } elseif ($packages->isNotEmpty()) {
                $activePackage = $packages->first();
            }
        }

        // Previous / Next navigation when viewing a material
        $previousMaterial = null;
        $nextMaterial = null;
        $nextTryoutAfterMaterials = null;

        if ($activeMaterial !== null) {
            $activeMaterial->loadMissing('chapter.subject.category');
            $idx = $allMaterials->search(fn (Material $m) => $m->id === $activeMaterial->id);
            if ($idx !== false && $idx > 0) {
                $previousMaterial = $allMaterials->get($idx - 1);
            }
            if ($idx !== false && $idx < $allMaterials->count() - 1) {
                $nextMaterial = $allMaterials->get($idx + 1);
            }
            if ($packages->isNotEmpty()) {
                $nextTryoutAfterMaterials = $packages->first(fn (ExamPackage $pkg) => $pkg->isAccessibleBy($user))
                    ?? $packages->first();
            }
        }

        // Active Try Out details
        $packageState = null;
        if ($activePackage !== null) {
            $pkgAttempts = $userAttempts->where('exam_package_id', $activePackage->id)->values();
            $uncompletedPrereqs = $activePackage->uncompletedMaterialsFor($user);
            $totalPrereqs = $activePackage->prerequisiteMaterialsFor($user)->count();

            $packageState = [
                'unlocked' => $activePackage->isAccessibleBy($user),
                'prerequisitesMet' => $uncompletedPrereqs->isEmpty(),
                'uncompletedMaterials' => $uncompletedPrereqs,
                'totalPrerequisites' => $totalPrereqs,
                'completedPrerequisites' => max(0, $totalPrereqs - $uncompletedPrereqs->count()),
                'attempts' => $pkgAttempts,
                'running' => $pkgAttempts->first(fn ($attempt) => $attempt->isInProgress() && ! $attempt->isExpired()),
                'attemptsLeft' => $activePackage->max_attempts === null ? null : max(0, $activePackage->max_attempts - $pkgAttempts->count()),
                'questionCount' => $activePackage->sections->sum('questions_count'),
            ];
        }

        $accessiblePackages = $packages->filter(fn (ExamPackage $pkg) => $pkg->isAccessibleBy($user));
        $totalTrackable = $accessibleMaterials->count() + $accessiblePackages->count();
        $completedTrackable = $accessibleMaterials->filter(fn (Material $m) => $completedMaterialIds->contains($m->id))->count()
            + $accessiblePackages->filter(fn (ExamPackage $pkg) => $submittedPackageIds->contains($pkg->id))->count();

        return [
            'category' => $category,
            'hasAccess' => $hasAccess,
            'packages' => $packages,
            'allMaterials' => $allMaterials,
            'completedMaterialIds' => $completedMaterialIds,
            'submittedPackageIds' => $submittedPackageIds,
            'uncompletedAccessibleMaterials' => $uncompletedAccessibleMaterials,
            'areAccessibleMaterialsDone' => $areAccessibleMaterialsDone,
            'completedTrackable' => $completedTrackable,
            'totalTrackable' => $totalTrackable,
            'progressPercent' => $totalTrackable > 0 ? (int) round($completedTrackable / $totalTrackable * 100) : 0,
            'completedFreeCount' => $allMaterials->where('is_free', true)->filter(fn (Material $m) => $completedMaterialIds->contains($m->id))->count(),
            'demoPackage' => $packages->firstWhere('is_free', true),
            'activeMaterial' => $activeMaterial,
            'activeSubjectId' => $activeMaterial?->chapter?->subject_id,
            'materialUnlocked' => $activeMaterial ? $activeMaterial->isAccessibleBy($user) : false,
            'materialCompleted' => $activeMaterial ? $completedMaterialIds->contains($activeMaterial->id) : false,
            'previousMaterial' => $previousMaterial,
            'nextMaterial' => $nextMaterial,
            'nextTryoutAfterMaterials' => $nextTryoutAfterMaterials,
            'activePackage' => $activePackage,
            'packageState' => $packageState,
        ];
    }

    protected function completeMaterialAndAdvance(User $user, Material $material, PointService $points): void
    {
        abort_unless($material->isAccessibleBy($user), 403);

        MaterialProgress::query()->firstOrCreate(
            ['user_id' => $user->id, 'material_id' => $material->id],
            ['completed_at' => now()],
        );

        $material->loadMissing('chapter.subject.category');
        $points->awardIfChapterCompleted($user, $material->chapter);

        $category = $material->chapter->subject->category;
        $data = $this->buildCoursePlayerData($user, $category, $material);

        /** @var Collection<int, Material> $remainingUnlocked */
        $remainingUnlocked = $data['uncompletedAccessibleMaterials'];

        // Find next unlocked material after current position if any
        /** @var Collection<int, Material> $allMaterials */
        $allMaterials = $data['allMaterials'];
        $currentIndex = $allMaterials->search(fn (Material $m) => $m->id === $material->id);

        $nextUnlockedAfterCurrent = $allMaterials
            ->slice(($currentIndex !== false ? $currentIndex : 0) + 1)
            ->first(fn (Material $m) => $m->isAccessibleBy($user));

        if ($nextUnlockedAfterCurrent) {
            $this->redirectRoute('learn.material', $nextUnlockedAfterCurrent, navigate: true);

            return;
        }

        if ($remainingUnlocked->isNotEmpty()) {
            $this->redirectRoute('learn.material', $remainingUnlocked->first(), navigate: true);

            return;
        }

        // All accessible materials are completed! Advance straight to the Try Out if available, or show next locked module paywall
        /** @var ExamPackage|null $nextTryout */
        $nextTryout = $data['nextTryoutAfterMaterials'];
        if ($nextTryout && $nextTryout->isAccessibleBy($user)) {
            $this->redirectRoute('learn.category', ['category' => $category, 'tryout' => $nextTryout->slug], navigate: true);

            return;
        }

        if ($data['nextMaterial']) {
            $this->redirectRoute('learn.material', $data['nextMaterial'], navigate: true);
        }
    }
}
