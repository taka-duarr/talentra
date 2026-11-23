<?php

namespace App\Livewire\Admin\Materials;

use App\Models\Category;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts::admin')]
#[Title('Materi')]
class MaterialIndex extends Component
{
    use WithPagination;

    #[Url(as: 'kategori')]
    public string $category = '';

    #[Url(as: 'cari')]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        Material::query()->findOrFail($id)->delete();
        session()->flash('status', 'Materi dihapus.');
    }

    public function render(): View
    {
        $materials = Material::query()
            ->with('chapter.subject.category')
            ->when($this->category !== '', fn ($query) => $query->whereHas('chapter.subject', fn ($subject) => $subject->where('category_id', $this->category)))
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->latest('updated_at')
            ->paginate(20);

        return view('livewire.admin.materials.index', [
            'materials' => $materials,
            'categories' => Category::query()->ordered()->get(['id', 'name']),
        ]);
    }
}
