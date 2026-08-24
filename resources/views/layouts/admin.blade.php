<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? 'แอดมิน' }} — {{ config('app.name') }}</title>
        @fonts
        @vite(['resources/css/admin.css'])
        @livewireStyles
    </head>
    <body class="admin-app">
        <a href="#admin-main" class="sr-only">ข้ามไปเนื้อหาหลัก</a>
        <x-admin.sidebar />
        <div class="admin-frame">
            <header class="topbar">
                <p class="muted"></p>
                <div class="topbar-actions">
                    <span class="muted">{{ auth()->user()?->name }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">
                            <x-icon name="arrow-right-start-on-rectangle" size="sm" />
                            ออกจากระบบ
                        </button>
                    </form>
                </div>
            </header>
            <main class="content" id="admin-main">
                {{ $slot }}
            </main>
        </div>
        <x-admin.toast />
        @livewireScripts
    </body>
</html>
