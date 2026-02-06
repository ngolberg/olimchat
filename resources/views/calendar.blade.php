@extends('layouts.layout')

@section('content')
    <section class="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow py-12">
        <div class="max-w-6xl mx-auto px-6">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-extrabold text-slate-900 mb-4">{{ __('messages.calendar_title') }}</h1>
                <p class="text-lg text-slate-600">{{ __('messages.calendar_welcome', ['name' => Auth::user()->name]) }}</p>
            </div>

            @if(empty($days))
                <div class="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
                    <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <p class="text-xl text-slate-600">{{ __('messages.no_matches_found') }}</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($days as $date => $times)
                        @php
                            $carbonDate = \Carbon\Carbon::parse($date);
                            $dayLabel = $carbonDate->isToday() ? __('messages.day_today') : ($carbonDate->isTomorrow() ? __('messages.day_tomorrow') : $carbonDate->translatedFormat('l, j F'));
                        @endphp
                        <div class="relative" x-data="{ openDay: {{ $loop->first ? 'true' : 'false' }} }">
                            <div class="sticky top-20 z-10 bg-slate-50/90 backdrop-blur-md py-3 mb-2 cursor-pointer group rounded-xl" @click="openDay = !openDay">
                                <div class="flex items-center justify-between">
                                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                                        <span class="w-2 h-8 bg-sky-500 rounded-full transition-all group-hover:scale-y-110"></span>
                                        {{ $dayLabel }}
                                    </h2>
                                    <svg class="w-6 h-6 text-slate-400 transition-transform duration-300 mr-2" :class="{ 'rotate-180': openDay }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </div>
                            </div>

                            <div class="space-y-4 mt-4" x-show="openDay" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0">
                                @foreach($times as $time => $slots)
                                    <div class="grid grid-cols-1 md:grid-cols-[100px_1fr] gap-4 items-start">
                                        <div class="pt-4">
                                            <span class="text-lg font-semibold text-sky-600 bg-sky-50 px-3 py-1 rounded-lg">
                                                {{ $time }}
                                            </span>
                                        </div>
                                        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                            @foreach($slots as $slot)
                                                <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-200 hover:shadow-md transition-shadow flex items-center gap-3">
                                                    <!-- User Image -->
                                                    <div class="relative group cursor-pointer flex-shrink-0" x-data="{ open: false }">
                                                        @php
                                                            $userImage = $slot['user']->image ? '/photos/' . $slot['user']->image : '/img/logo.jpeg';
                                                        @endphp
                                                        <img src="{{ $userImage }}"
                                                             @click="open = true"
                                                             alt="{{ $slot['user']->first_name }}"
                                                             class="w-12 h-12 rounded-full object-cover border border-slate-100 group-hover:border-sky-400 transition-colors" />

                                                        <!-- Modal for bigger image -->
                                                        <div x-show="open"
                                                             x-transition:enter="transition ease-out duration-300"
                                                             x-transition:enter-start="opacity-0 scale-95"
                                                             x-transition:enter-end="opacity-100 scale-100"
                                                             x-transition:leave="transition ease-in duration-200"
                                                             x-transition:leave-start="opacity-100 scale-100"
                                                             x-transition:leave-end="opacity-0 scale-95"
                                                             @click="open = false"
                                                             class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4"
                                                             style="display: none;">
                                                            <div class="relative max-w-2xl w-full" @click.stop>
                                                                <img src="{{ $userImage }}" class="w-full h-auto rounded-lg shadow-2xl" />
                                                                <button @click="open = false" class="absolute -top-12 right-0 text-white text-3xl font-bold hover:text-slate-300 transition-colors">&times;</button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <!-- User Info & Action -->
                                                    <div class="flex-grow min-w-0">
                                                        <h3 class="font-bold text-slate-800 text-sm truncate">
                                                            @if($slot['user']->username)
                                                                <a href="https://t.me/{{ $slot['user']->username }}" target="_blank" class="text-sky-600 hover:underline">
                                                                    {{ $slot['user']->first_name }} {{ $slot['user']->last_name }}
                                                                </a>
                                                            @else
                                                                {{ $slot['user']->first_name }} {{ $slot['user']->last_name }}
                                                            @endif
                                                        </h3>
                                                        <button class="mt-1 text-sky-600 hover:text-sky-700 font-semibold text-xs transition-colors flex items-center gap-1">
                                                            <span>{{ __('messages.schedule_meeting') }}</span>
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
