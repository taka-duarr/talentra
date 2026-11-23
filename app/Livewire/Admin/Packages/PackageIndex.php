<?php

namespace App\Livewire\Admin\Packages;

use App\Models\ExamPackage;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::admin')]
#[Title('Paket try out')]
class PackageIndex extends Component
{
    public function delete(int $id): void
    {
        $package = ExamPackage::query()->withCount('attempts')->findOrFail($id);

        if ($package->attempts_count > 0) {
            session()->flash('error', 'Paket sudah pernah dikerjakan peserta. Ubah statusnya menjadi draf agar riwayat nilai tetap tersimpan.');

            return;
        }

        $package->delete();
        session()->flash('status', 'Paket dihapus.');
    }

    public function render(): View
    {
        return view('livewire.admin.packages.index', [
            'packages' => ExamPackage::query()
                ->with(['category', 'sections' => fn ($query) => $query->withCount('questions')])
                ->withCount('attempts')
                ->latest('updated_at')
                ->get(),
        ]);
    }
}
