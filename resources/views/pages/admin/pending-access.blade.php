<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('รออนุญาต')]
class extends Component
{
    public function mount(): void
    {
        $user = Auth::user();

        if ($user instanceof User && $user->canAccessAdmin()) {
            $this->redirect(route('admin.dashboard'), navigate: false);
        }
    }
};
?>

<div class="admin-login relative min-h-dvh bg-bg text-fg">
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
                        <x-icon name="clock" size="md" />
                    </span>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-balance text-brand">รออนุญาต</h1>
                        <p class="mt-1 text-sm leading-relaxed text-muted">
                            บัญชีถูกสร้างแล้ว รอเจ้าหน้าที่เปิดสิทธิ์เข้าคอนโซล
                        </p>
                    </div>
                </div>

                <p class="mt-6 text-sm text-fg">{{ Auth::user()?->email }}</p>

                <form method="POST" action="{{ route('admin.logout') }}" class="mt-8">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex min-h-11 w-full cursor-pointer items-center justify-center gap-2 rounded-brand bg-accent px-4 text-sm font-medium text-brand-fg transition-colors duration-200 hover:bg-accent-press active:scale-[0.99]"
                    >
                        ออกจากระบบ
                    </button>
                </form>
            </div>
        </main>
    </div>
</div>
