@extends('layouts.layout')

@section('content')
    <section class="bg-gradient-to-br from-slate-50 to-slate-200/70 flex-grow flex items-center justify-center">
        <div class="max-w-6xl mx-auto px-6 py-16 text-center">
            <div class="bg-white border border-slate-200 text-slate-900 p-6 md:p-8 rounded-2xl shadow-xl mb-8 w-full max-w-2xl">
                <div class="flex justify-center mb-6">
                    <div class="bg-sky-50 p-4 rounded-full">
                        <svg class="w-12 h-12 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m0 0v2m0-2h2m-2 0H10m12-3V7a2 2 0 00-2-2H4a2 2 0 00-2 2v10a2 2 0 002 2h8m10-10l-10 8L2 7"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                        </svg>
                    </div>
                </div>
                <h1 class="text-3xl md:text-4xl font-extrabold mb-4 text-slate-800">{{ __('messages.forbidden_title') }}</h1>
                <p class="text-lg md:text-xl mb-8 leading-relaxed text-slate-600">{{ __('messages.forbidden_message') }}</p>
                <a href="{{ config('services.telegram.bot_url') }}" class="inline-flex items-center gap-2 px-6 md:px-10 py-3 md:py-4 rounded-full bg-sky-600 text-white font-bold shadow-lg hover:bg-sky-700 hover:shadow-xl hover:-translate-y-0.5 transition-all">
                    <span>@HebrewPeer2Peer_bot</span>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </a>
            </div>
        </div>
    </section>
@endsection
