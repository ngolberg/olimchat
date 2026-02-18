<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class AppConfigController extends Controller
{
    /**
     * Get the application configuration and translations.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->lang) {
            app()->setLocale($user->lang);
        }

        $userImage = null;
        if ($user && $user->tg_id) {
            $botUser = DB::connection('mysql_bot')
                ->table('users')
                ->where('tg_id', $user->tg_id)
                ->first(['image']);
            if ($botUser && $botUser->image) {
                $userImage = '/photos/' . $botUser->image;
            }
        }

        return response()->json([
            'user' => Auth::user(),
            'user_image' => $userImage,
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
                'upload_photo_title' => __('messages.upload_photo_title'),
                'upload_photo_message' => __('messages.upload_photo_message'),
                'upload_photo_done' => __('messages.upload_photo_done'),
                'upload_photo_another' => __('messages.upload_photo_another'),
                'not_found_title' => __('messages.not_found_title'),
                'not_found_message' => __('messages.not_found_message'),
                'not_found_go_calendar' => __('messages.not_found_go_calendar'),
                'filter_users' => __('messages.filter_users'),
                'filter_select_all' => __('messages.filter_select_all'),
                'my_meetings_title' => __('messages.my_meetings_title'),
                'my_meetings_welcome' => __('messages.my_meetings_welcome', ['name' => Auth::user() ? Auth::user()->name : '']),
                'my_meetings_none' => __('messages.my_meetings_none'),
                'my_meetings_status_waiting' => __('messages.my_meetings_status_waiting'),
                'my_meetings_status_accepted' => __('messages.my_meetings_status_accepted'),
                'my_meetings_btn_accept' => __('messages.my_meetings_btn_accept'),
                'my_meetings_btn_decline' => __('messages.my_meetings_btn_decline'),
                'my_meetings_btn_cancel' => __('messages.my_meetings_btn_cancel'),
                'my_meetings_role_invited_you' => __('messages.my_meetings_role_invited_you'),
                'my_meetings_role_you_invited' => __('messages.my_meetings_role_you_invited'),
            ],
            'auth' => Auth::check(),
        ]);
    }
}
