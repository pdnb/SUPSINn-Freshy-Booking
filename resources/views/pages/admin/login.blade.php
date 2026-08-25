<?php
use App\Models\User;
use App\Services\Auth\Auth0Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('เข้าสู่ระบบแอดมิน')]
class extends Component
{
    public string $email = '';

    public string $password = '';

    public function authenticate(): void
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = $this->throttleKey();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);

            $this->addError('email', "ลองใหม่ในอีก {$seconds} วินาที");

            return;
        }

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            RateLimiter::increment($throttleKey);

            $this->addError('email', 'อีเมลหรือรหัสผ่านไม่ถูกต้อง');

            return;
        }

        RateLimiter::clear($throttleKey);
        session()->regenerate();

        $user = Auth::user();

        if (! $user instanceof User || ! $user->canAccessAdmin()) {
            $this->redirect(route('admin.pending'));

            return;
        }

        $this->redirectIntended(route('admin.dashboard'));
    }

    public function render(Auth0Config $auth0)
    {
        return $this->view([
            'auth0Configured' => $auth0->isConfigured(),
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div class="admin-login relative min-h-dvh bg-bg text-fg">
    <a
        href="#admin-login-form"
        class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-50 focus:rounded-brand focus:bg-surface focus:px-4 focus:py-2 focus:text-sm focus:font-medium focus:shadow-sm"
    >
        ข้ามไปแบบฟอร์มเข้าสู่ระบบ
    </a>

    <div class="grid min-h-dvh lg:grid-cols-2">
        <aside
            class="admin-login__brand relative flex flex-col justify-between overflow-hidden bg-brand px-6 py-10 text-brand-fg sm:px-10 lg:px-14 lg:py-16"
            aria-label="แบรนด์"
        >
            <div class="pointer-events-none absolute inset-0" aria-hidden="true">
                <div class="absolute -left-24 -top-28 h-72 w-72 rounded-full bg-brand-fg/10 blur-2xl"></div>
                <div class="absolute -bottom-32 right-[-20%] h-96 w-96 rounded-full bg-brand-press/50 blur-3xl"></div>
                <div
                    class="absolute inset-0 opacity-[0.14]"
                    style="background-image: radial-gradient(circle at 1px 1px, currentColor 1px, transparent 0); background-size: 22px 22px;"
                ></div>
            </div>

            <div class="relative">
                <p class="mt-5 max-w-md text-4xl font-semibold tracking-tight text-balance sm:text-5xl lg:text-[3.25rem] lg:leading-[1.1]">
                    {{ config('app.name') }}
                </p>
                <p class="mt-4 max-w-sm text-base leading-relaxed text-brand-fg/85">
                    Admin Console
                </p>
            </div>

            <p class="relative mt-10 text-sm text-brand-fg/70 lg:mt-0">Made with ❤️ by <a href="https://cc.sru.ac.th" target="_blank">SRU Computer Center</a></p>
        </aside>

        <main class="relative flex items-center justify-center px-4 py-10 sm:px-8 lg:px-12">
            <div class="admin-login__panel w-full max-w-md">
                <div class="flex items-start gap-3">
                    <span class="mt-1 inline-flex size-10 shrink-0 items-center justify-center rounded-brand bg-accent/10 text-brand" aria-hidden="true">
                        <x-icon name="lock-closed" size="md" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-balance text-brand">เข้าสู่ระบบแอดมิน</h1>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            @if ($auth0Configured)
                                ใช้อีเมลและรหัสผ่านของเจ้าหน้าที่ หรือเข้าด้วย Google
                            @else
                                ใช้อีเมลและรหัสผ่านของเจ้าหน้าที่
                            @endif
                        </p>
                    </div>
                </div>

                <form id="admin-login-form" class="mt-8 space-y-5" wire:submit="authenticate">
                    <div>
                        <label for="email" class="block text-sm font-medium">อีเมล</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            autocomplete="username"
                            wire:model="email"
                            class="mt-1.5 min-h-11 w-full rounded-brand border border-border bg-surface px-3 text-base text-fg transition-colors duration-200 placeholder:text-muted/70 focus:border-accent focus:ring-2 focus:ring-accent"
                            placeholder="email@sru.ac.th"
                        >
                        @error('email')
                            <p role="alert" class="mt-2 rounded-brand border border-danger/30 bg-danger/10 px-3 py-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium">รหัสผ่าน</label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            autocomplete="current-password"
                            wire:model="password"
                            class="mt-1.5 min-h-11 w-full rounded-brand border border-border bg-surface px-3 text-base text-fg transition-colors duration-200 focus:border-accent focus:ring-2 focus:ring-accent"
                        >
                        @error('password')
                            <p role="alert" class="mt-2 rounded-brand border border-danger/30 bg-danger/10 px-3 py-2 text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-brand bg-accent px-4 text-sm font-medium text-brand-fg transition-colors duration-200 hover:bg-accent-press active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-60"
                        wire:loading.attr="disabled"
                        wire:target="authenticate"
                    >
                        <span wire:loading.remove wire:target="authenticate">เข้าสู่ระบบ</span>
                        <span wire:loading wire:target="authenticate" class="inline-flex items-center gap-2">
                            <x-icon name="arrow-path" size="sm" class="animate-spin" />
                        </span>
                    </button>
                </form>

                @if ($auth0Configured)
                    <div class="mt-5">
                        <p class="mb-3 text-center text-sm text-muted">หรือ</p>
                        <a
                            href="{{ route('admin.auth0.redirect') }}"
                            class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-brand border border-border bg-surface px-4 text-sm font-medium text-fg transition-colors duration-200 hover:bg-accent/5 active:scale-[0.99]"
                        >
                            เข้าสู่ระบบด้วย Google
                        </a>
                    </div>
                @endif

                <p class="mt-8 text-sm text-muted">
                    <a href="{{ route('home') }}" wire:navigate class="font-medium text-brand underline-offset-4 transition-colors duration-200 hover:underline">
                        กลับหน้าร้าน
                    </a>
                </p>
            </div>
        </main>
    </div>
</div>
