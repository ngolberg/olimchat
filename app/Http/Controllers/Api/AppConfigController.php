<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    /**
     * Get the application configuration and translations.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'user' => Auth::user(),
            'messages' => [
                'hero_title' => __('messages.hero_title'),
                'hero_subtitle' => __('messages.hero_subtitle'),
                'hero_description' => __('messages.hero_description'),
                'start_now' => __('messages.start_now'),
                'how_it_works' => __('messages.how_it_works'),
                'step_1' => __('messages.step_1'),
                'step_2' => __('messages.step_2'),
                'step_3' => __('messages.step_3'),
                'step_4' => __('messages.step_4'),
                'step_5' => __('messages.step_5'),
                'step_6' => __('messages.step_6'),
                'calendar_title' => __('messages.calendar_title'),
                'calendar_welcome' => __('messages.calendar_welcome', ['name' => Auth::user() ? Auth::user()->name : '']),
                'no_matches_found' => __('messages.no_matches_found'),
                'schedule_meeting' => __('messages.schedule_meeting'),
                'day_today' => __('messages.day_today'),
                'day_tomorrow' => __('messages.day_tomorrow'),
            ],
            'auth' => Auth::check(),
        ]);
    }
}
