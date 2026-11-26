<div
    x-data="{
        mode: @js($mode),
        sweep: 0,
        urls: { login: @js(route('login')), register: @js(route('register')) },
        titles: { login: 'Masuk · Talentra', register: 'Daftar · Talentra' },
        focusPane(name) {
            if (! window.matchMedia('(min-width: 1024px)').matches) return;
            this.$root.querySelector(`[data-pane='${name}'] [data-autofocus]`)?.focus({ preventScroll: true });
        },
        switchTo(next) {
            if (next === this.mode || ! this.urls[next]) return;
            this.mode = next;
            this.sweep++;
            history.replaceState(history.state, '', this.urls[next]);
            document.title = this.titles[next];
            setTimeout(() => this.focusPane(next), 760);
        },
    }"
    x-init="focusPane(mode)"
    x-on:auth-switch.window="switchTo($event.detail)"
    :data-mode="mode"
    data-mode="{{ $mode }}"
    class="auth-card"
>
    <section data-pane="login" class="auth-pane auth-pane-login" :inert="mode !== 'login'" :aria-hidden="mode !== 'login'" aria-labelledby="login-heading">
        <livewire:auth.login />
    </section>

    <section data-pane="register" class="auth-pane auth-pane-register" :inert="mode !== 'register'" :aria-hidden="mode !== 'register'" aria-labelledby="register-heading">
        <livewire:auth.register />
    </section>

    {{-- The sweeping blade --}}
    <div class="auth-band">
        <div class="auth-band-body" :class="sweep === 0 ? '' : (sweep % 2 ? 'is-sweep-a' : 'is-sweep-b')">
            <div class="auth-band-skin" aria-hidden="true">
                <span class="auth-band-glow"></span>
                <svg class="auth-band-cap" viewBox="0 0 120 120" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round">
                    <path d="M60 22 8 46l52 24 52-24-52-24Z"/>
                    <path d="M28 56v24c0 9 14.3 17 32 17s32-8 32-17V56"/>
                    <path d="M104 50v30"/>
                    <circle cx="104" cy="84" r="3.5"/>
                </svg>
            </div>

            <div class="auth-band-content">
                <div class="auth-copy" data-copy="login" :inert="mode !== 'login'">
                    <p class="auth-eyebrow"><span></span>Tumbuh · Uji · Berkembang</p>
                    <h2 class="auth-display">Selamat datang <em>kembali.</em></h2>
                    <p class="auth-lede">Lanjutkan try out terakhirmu dan lihat lagi materi yang disarankan dari hasilmu.</p>
                    <button type="button" class="auth-switch" @click="switchTo('register')">
                        <span class="text-white/60">Belum punya akun?</span> Daftar gratis
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14"/><path d="m13 6 6 6-6 6"/></svg>
                    </button>
                </div>

                <div class="auth-copy" data-copy="register" :inert="mode !== 'register'">
                    <p class="auth-eyebrow"><span></span>Gratis untuk mulai</p>
                    <h2 class="auth-display">Mulai <em>tumbuh</em> hari ini.</h2>
                    <p class="auth-lede">Coba try out TKA, UTBK, CPNS, BUMN, dan Kedinasan, lalu dapatkan analisis kelemahanmu.</p>
                    <button type="button" class="auth-switch" @click="switchTo('login')">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m11 6-6 6 6 6"/></svg>
                        <span class="text-white/60">Sudah punya akun?</span> Masuk
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
