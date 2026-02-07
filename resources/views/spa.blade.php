<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="OlimChat.me - Your intelligent Telegram Bot assistant." />
    <link rel="icon" href="/favicon.ico" sizes="any"/>
    <title>{{ config('app.name', 'OlimChat.me') }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Styles / Scripts -->
    @viteReactRefresh
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
</head>
<body class="min-h-screen bg-gradient-to-br from-indigo-500 to-purple-600 text-gray-800 flex flex-col">
    <div id="app"></div>
</body>
</html>
