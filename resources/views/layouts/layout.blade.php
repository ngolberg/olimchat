<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OlimChat.me - Your intelligent Telegram Bot assistant. Get instant answers, automate tasks, and boost productivity." />
    <link rel="icon" href="/web/public/img/favicon.ico" sizes="any"/>
    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Styles / Scripts -->
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600 text-gray-800 flex flex-col">
<!-- Navigation -->
<nav class="sticky top-0 z-50 bg-white/95 backdrop-blur shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <a href="/" class="flex items-center gap-2 text-xl font-bold text-sky-600">
            <img src="/img/logo.jpeg" alt="OlimChat.me Logo" class="w-10 h-10 rounded-full" />
            <span>OlimChat.me</span>
        </a>
        <ul class="hidden sm:flex items-center gap-6 text-gray-700 font-medium">
            @if (url()->current() === url('/'))
                <li><a href="/#how-it-works" class="hover:text-sky-600 transition-colors">{{ __('messages.how_it_works') }}</a></li>
            @endif
            <li class="flex gap-2 ml-4">
                <a href="?lang=en" class="{{ app()->getLocale() === 'en' ? 'text-sky-600 font-bold' : 'hover:text-sky-600' }}">EN</a>
                <span class="text-gray-300">|</span>
                <a href="?lang=ru" class="{{ app()->getLocale() === 'ru' ? 'text-sky-600 font-bold' : 'hover:text-sky-600' }}">RU</a>
            </li>
        </ul>
    </div>
</nav>

<main class="flex-grow flex flex-col">
    @yield('content')
</main>

<!-- Footer -->
<footer class="bg-neutral-900 text-white">
    <div class="max-w-6xl mx-auto px-6 pb-6">
        <div class="border-t border-neutral-700 pt-6 text-neutral-400 text-center">
            <p>&copy; 2026 OlimChat.me. {{ __('messages.all_rights_reserved') }}</p>
        </div>
    </div>
</footer>
</body>
</html>
