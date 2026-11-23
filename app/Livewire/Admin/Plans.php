<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Plan;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Paket langganan')]
class Plans extends Component
{
    public ?int $editingId = null;

    /** @var array<string, mixed> */
    public array $form = [];

    /** @var array<int, array{months: int|string, price: int|string, compare_at_price: int|string|null}> */
    public array $prices = [];

    /** @var array<int, int> */
    public array $categoryIds = [];

    public function mount(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $plan = Plan::query()->with(['prices', 'categories'])->findOrFail($id);
        $this->editingId = $plan->id;
        $this->form = $plan->only(['name', 'description', 'is_all_access', 'is_active', 'sort_order']);
        $this->prices = $plan->prices->map(fn ($price) => $price->only(['months', 'price', 'compare_at_price']))->all();
        $this->categoryIds = $plan->categories->pluck('id')->all();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'description' => '', 'is_all_access' => false, 'is_active' => true, 'sort_order' => Plan::query()->count()];
        $this->prices = [['months' => 1, 'price' => '', 'compare_at_price' => null]];
        $this->categoryIds = [];
        $this->resetErrorBag();
    }

    public function addPrice(): void
    {
        $this->prices[] = ['months' => '', 'price' => '', 'compare_at_price' => null];
    }

    public function removePrice(int $index): void
    {
        unset($this->prices[$index]);
        $this->prices = array_values($this->prices);
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:100'],
            'form.description' => ['nullable', 'string', 'max:255'],
            'form.is_all_access' => ['boolean'],
            'form.is_active' => ['boolean'],
            'form.sort_order' => ['integer', 'min:0'],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.months' => ['required', 'integer', 'between:1,36', 'distinct'],
            'prices.*.price' => ['required', 'integer', 'min:0'],
            'prices.*.compare_at_price' => ['nullable', 'integer', 'min:0'],
            'categoryIds' => [$this->form['is_all_access'] ? 'nullable' : 'required', 'array'],
            'categoryIds.*' => ['exists:categories,id'],
        ], ['categoryIds.required' => 'Pilih minimal satu kategori yang dibuka paket ini.']);

        DB::transaction(function () {
            $plan = Plan::query()->updateOrCreate(['id' => $this->editingId], [
                ...$this->form,
                'slug' => $this->editingId ? Plan::query()->find($this->editingId)->slug : $this->uniqueSlug($this->form['name']),
            ]);

            $plan->categories()->sync($this->form['is_all_access'] ? Category::query()->pluck('id') : $this->categoryIds);

            $months = collect($this->prices)->pluck('months')->map(fn ($value) => (int) $value);
            $plan->prices()->whereNotIn('months', $months)->delete();
            foreach ($this->prices as $price) {
                $plan->prices()->updateOrCreate(['months' => (int) $price['months']], [
                    'price' => (int) $price['price'],
                    'compare_at_price' => $price['compare_at_price'] === '' ? null : $price['compare_at_price'],
                ]);
            }
        });

        session()->flash('status', 'Paket langganan disimpan.');
        $this->resetForm();
    }

    private function uniqueSlug(string $name): string
    {
        $slug = Str::slug($name);
        $base = $slug;
        $counter = 2;
        while (Plan::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    public function render(): View
    {
        return view('livewire.admin.plans', [
            'plans' => Plan::query()->with(['prices', 'categories'])->withCount('prices')->orderBy('sort_order')->get(),
            'categories' => Category::query()->ordered()->get(['id', 'name']),
        ]);
    }
}
