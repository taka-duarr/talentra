<?php

namespace App\Livewire\Admin\Courses;

use App\Models\Category;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Manajemen Kursus')]
class CourseIndex extends Component
{
    public bool $showCreateModal = false;

    /** @var array{name: string, description: string, color: string, sort_order: int, is_active: bool} */
    public array $form = [
        'name' => '',
        'description' => '',
        'color' => '#14b8a6',
        'sort_order' => 0,
        'is_active' => true,
    ];

    public function openCreate(): void
    {
        $this->resetErrorBag();
        $this->form = [
            'name' => '',
            'description' => '',
            'color' => '#14b8a6',
            'sort_order' => Category::query()->count(),
            'is_active' => true,
        ];
        $this->showCreateModal = true;
    }

    public function createCourse(): void
    {
        $validated = $this->validate([
            'form.name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.color' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'form.sort_order' => ['required', 'integer', 'min:0'],
            'form.is_active' => ['boolean'],
        ])['form'];

        $course = Category::create([
            ...$validated,
            'slug' => $this->uniqueSlug($validated['name']),
        ]);

        session()->flash('status', 'Kursus baru berhasil dibuat.');
        $this->redirectRoute('admin.courses.show', $course, navigate: true);
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $counter = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function render(): View
    {
        $courses = Category::query()
            ->ordered()
            ->withCount([
                'subjects',
                'questions',
                'examPackages' => fn ($query) => $query->where('is_practice', false),
            ])
            ->get();

        $materialCounts = Material::query()
            ->selectRaw('subjects.category_id, count(*) as total')
            ->join('chapters', 'chapters.id', '=', 'materials.chapter_id')
            ->join('subjects', 'subjects.id', '=', 'chapters.subject_id')
            ->groupBy('subjects.category_id')
            ->pluck('total', 'subjects.category_id');

        return view('livewire.admin.courses.index', [
            'courses' => $courses,
            'materialCounts' => $materialCounts,
        ]);
    }
}
