<?php
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

        $this->redirectIntended(route('admin.dashboard'));
    }

    public function render()
    {
        return $this->view();
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
};
?>

<div class="min-h-dvh bg-bg text-fg">
    <header class="border-b border-brand-press bg-brand text-brand-fg">
        <div class="mx-auto flex h-14 max-w-lg items-center px-4">
            <p class="text-sm font-semibold sm:text-base">มรส. ชุดเฟรชชี่</p>
        </div>
    </header>

    <main class="mx-auto max-w-lg px-4 py-8 sm:px-6">
        <div class="overflow-hidden rounded-brand border border-border bg-surface p-4 lg:p-5">
            <h1 class="flex items-center gap-2 text-xl font-semibold">
                <x-icon name="lock-closed" size="md" class="text-muted" />
                เข้าสู่ระบบแอดมิน
            </h1>
            <p class="mt-1 text-sm text-muted">สำหรับเจ้าหน้าที่เท่านั้น</p>

            <form class="mt-6 space-y-4" wire:submit="authenticate">
                <div>
                    <label for="email" class="block text-sm font-medium">อีเมล</label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        autocomplete="username"
                        wire:model="email"
                        class="mt-1 min-h-11 w-full rounded-brand border border-border bg-surface px-3 text-fg transition-colors duration-200 focus:border-accent focus:ring-2 focus:ring-accent"
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
                        class="mt-1 min-h-11 w-full rounded-brand border border-border bg-surface px-3 text-fg transition-colors duration-200 focus:border-accent focus:ring-2 focus:ring-accent"
                    >
                    @error('password')
                        <p role="alert" class="mt-2 rounded-brand border border-danger/30 bg-danger/10 px-3 py-2 text-sm text-danger">{{ $message }}</p>
                    @enderror
                </div>

                <button
                    type="submit"
                    class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-brand bg-accent px-4 text-sm font-medium text-accent-fg transition-colors duration-200 hover:bg-accent-press disabled:cursor-not-allowed disabled:opacity-60"
                    wire:loading.attr="disabled"
                    wire:target="authenticate"
                >
                    <span wire:loading.remove wire:target="authenticate">เข้าสู่ระบบ</span>
                    <span wire:loading wire:target="authenticate" class="inline-flex items-center gap-2">
                        <x-icon name="arrow-path" size="sm" class="animate-spin" />
                        กำลังเข้าสู่ระบบ…
                    </span>
                </button>
            </form>
        </div>
    </main>
</div>
