@extends('layouts.layout')

@section('content')
    <!-- Hero Section -->
    <section class="text-white">
        <div class="max-w-6xl mx-auto px-6 py-16 text-center">
            <div class="grid md:grid-cols-2 gap-12 items-center">
                <div class="order-2 md:order-1">
                    <h1 class="text-4xl md:text-6xl font-extrabold leading-tight mb-4">{{ __('messages.hero_title') }}</h1>
                    <p class="text-lg md:text-xl opacity-95">{{ __('messages.hero_subtitle') }}</p>
                    <p class="text-lg md:text-xl opacity-95 mt-2">
                        {{ __('messages.hero_description') }}
                    </p>
                    <div class="mt-8 flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ config('services.telegram.bot_url') }}" class="inline-block px-8 py-3 rounded-full bg-white text-indigo-600 font-semibold shadow hover:shadow-lg hover:-translate-y-0.5 transition">{{ __('messages.start_now') }}</a>
                    </div>
                </div>
                <div class="order-1 md:order-2 flex justify-center">
                    <img src="/img/logo.jpeg" alt="OlimChat.me" class="w-full max-w-xs md:max-w-sm rounded-2xl shadow-hero" />
                </div>
            </div>
        </div>
    </section>

    <!-- Gallery -->
    <section id="how-it-works" class="bg-gradient-to-br from-slate-50 to-slate-200/70">
        <div class="max-w-6xl mx-auto px-6 py-16">
            <h2 class="text-3xl md:text-4xl font-bold text-center text-gray-800">{{ __('messages.how_it_works') }}</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mt-12">
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/1.jpeg" alt="Morning Workflow" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_1') }}</figcaption>
                </figure>
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/6.jpeg" alt="AI in Action" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_2') }}</figcaption>
                </figure>
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/5.jpeg" alt="Seamless Automation" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_3') }}</figcaption>
                </figure>
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/4.jpeg" alt="Instant Answers" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_4') }}</figcaption>
                </figure>
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/3.jpeg" alt="Smart Productivity" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_5') }}</figcaption>
                </figure>
                <figure class="bg-white rounded-xl shadow overflow-hidden group">
                    <img src="/img/2.jpeg" alt="Always On" class="w-full h-auto object-contain" />
                    <figcaption class="p-4 text-center text-gray-700 font-medium">{{ __('messages.step_6') }}</figcaption>
                </figure>
            </div>
        </div>
    </section>

@endsection
