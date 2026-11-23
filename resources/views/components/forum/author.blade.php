@props(['user', 'badges' => []])

<span {{ $attributes->merge(['class' => 'inline-flex flex-wrap items-center gap-1.5']) }}>
    <span class="grid size-6 place-items-center rounded-full bg-navy-600 text-[11px] font-bold text-white" aria-hidden="true">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($user->publicName(), 0, 1)) }}</span>
    <span class="text-sm font-semibold text-ink">{{ $user->publicName() }}</span>
    @if ($user->isAdmin())
        <span class="badge bg-navy-600 text-white">Tim Talentra</span>
    @endif
    @foreach ($badges as $badge)
        <span class="badge bg-sun-100 text-sun-600">🏆 {{ $badge }}</span>
    @endforeach
</span>
