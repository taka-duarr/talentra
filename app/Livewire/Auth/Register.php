<?php

namespace App\Livewire\Auth;

use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts::guest')]
#[Title('Daftar')]
class Register extends Component
{
    public string $name = '';

    public string $email = '';

    public ?int $target_category_id = null;

    public string $password = '';

    public string $password_confirmation = '';

    public bool $accepted_terms = false;

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'target_category_id' => ['required', Rule::exists(Category::class, 'id')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
            'accepted_terms' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'target_category_id.required' => 'Pilih ujian yang sedang kamu persiapkan.',
            'accepted_terms.accepted' => 'Kamu perlu menyetujui syarat & ketentuan.',
        ];
    }

    #[Computed]
    public function categories()
    {
        return Category::query()->active()->ordered()->get(['id', 'name']);
    }

    public function register(): void
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'target_category_id' => $validated['target_category_id'],
        ]);

        event(new Registered($user));
        Auth::login($user);
        session()->regenerate();

        session()->flash('status', 'Akun berhasil dibuat. Selamat datang di Talentra!');
        $this->redirectIntended(route('dashboard'), navigate: false);
    }

    public function render(): View
    {
        return view('livewire.auth.register');
    }
}
