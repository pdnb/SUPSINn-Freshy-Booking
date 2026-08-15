<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @if (request()->routeIs('orders.confirmation'))
            <meta name="robots" content="noindex, nofollow">
            <meta name="referrer" content="no-referrer">
        @endif
        <title>{{ $title ?? config('app.name') }}</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body
        class="min-h-dvh bg-bg font-sans text-fg antialiased"
        @if (filled(config('services.line.liff_id')))
            data-liff-id="{{ config('services.line.liff_id') }}"
            data-line-session-url="{{ route('line.session') }}"
        @endif
    >
        {{ $slot }}
        @livewireScripts
    </body>
</html>
