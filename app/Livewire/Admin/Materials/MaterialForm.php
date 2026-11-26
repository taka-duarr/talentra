<?php

namespace App\Livewire\Admin\Materials;

use App\Models\Category;
use App\Models\Material;
use App\Support\Html;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts::admin')]
class MaterialForm extends Component
{
    public ?Material $material = null;

    public ?int $chapter_id = null;

    public string $title = '';

    public string $slug = '';

    public string $content = '';

    public string $video_url = '';

    public bool $is_free = false;

    public int $sort_order = 0;

    public bool $is_published = true;

    public string $courseSlug = '';

    public function mount(?Material $material = null): void
    {
        $this->courseSlug = (string) request()->query('kursus', '');

        if ($material?->exists) {
            $material->loadMissing('chapter.subject.category');
            $this->material = $material;
            $this->fill($material->only(['chapter_id', 'title', 'slug', 'content', 'is_free', 'sort_order']));
            $this->video_url = (string) $material->video_url;
            $this->is_published = $material->published_at !== null;
            if ($this->courseSlug === '') {
                $this->courseSlug = $material->chapter->subject->category->slug;
            }

            return;
        }

        $chapterId = (int) request()->query('bab', 0);
        if ($chapterId > 0) {
            $this->chapter_id = $chapterId;
        }
    }

    public function updatedTitle(string $value): void
    {
        if (! $this->material) {
            $this->slug = Str::slug($value);
        }
    }

    public function save(): void
    {
        $validated = $this->validate([
            'chapter_id' => ['required', Rule::exists('chapters', 'id')],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('materials', 'slug')->ignore($this->material?->id)],
            'content' => ['required', 'string'],
            'video_url' => ['nullable', 'url', 'max:255'],
            'is_free' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $attributes = [
            ...$validated,
            'content' => Html::clean($validated['content']),
            'video_url' => $validated['video_url'] ?: null,
            'published_at' => $this->is_published ? ($this->material?->published_at ?? now()) : null,
        ];

        $this->material
            ? $this->material->update($attributes)
            : $this->material = Material::create($attributes);

        session()->flash('status', 'Materi disimpan.');

        if ($this->courseSlug !== '') {
            $this->redirectRoute('admin.courses.show', ['category' => $this->courseSlug, 'tab' => 'kurikulum'], navigate: true);

            return;
        }

        $this->redirectRoute('admin.materials.edit', $this->material, navigate: true);
    }

    public function render(): View
    {
        $course = $this->courseSlug !== '' ? Category::query()->where('slug', $this->courseSlug)->first() : null;

        return view('livewire.admin.materials.form', [
            'course' => $course,
            'categories' => Category::query()
                ->when($course, fn ($query) => $query->whereKey($course->id))
                ->ordered()
                ->with('subjects.chapters')
                ->get(),
            'preview' => Html::clean($this->content),
        ])->title($this->material ? 'Ubah materi' : 'Materi baru');
    }
}
