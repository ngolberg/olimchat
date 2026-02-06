<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeetingsController extends Controller
{
    /**
     * Display the calendar page.
     */
    public function index(Request $request): View
    {
        $localUser = Auth::user();
        if (!$localUser) {
            abort(403);
        }

        // Extract tg_id from synthetic email
        // $email = 'tg_' . $user->tg_id . '@olimchat.me'
        if (preg_match('/^tg_(\d+)@/', $localUser->email, $matches)) {
            $tgId = $matches[1];
        } else {
            // Fallback if not a TG user
            return view('calendar', ['days' => []]);
        }

        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $tgId)
            ->first();

        if (!$botUser) {
            return view('calendar', ['days' => []]);
        }

        // Get user levels
        $myLevels = DB::connection('mysql_bot')
            ->table('user_levels')
            ->where('user_id', $botUser->id)
            ->pluck('level')
            ->toArray();

        if (empty($myLevels)) {
            return view('calendar', ['days' => []]);
        }

        // Find other active users with same levels
        $matchingUserIds = DB::connection('mysql_bot')
            ->table('user_levels')
            ->whereIn('level', $myLevels)
            ->where('user_id', '!=', $botUser->id)
            ->distinct()
            ->pluck('user_id');

        $activeMatchingUsers = DB::connection('mysql_bot')
            ->table('users')
            ->whereIn('id', $matchingUserIds)
            ->where('active', 1)
            ->get()
            ->keyBy('id');

        if ($activeMatchingUsers->isEmpty()) {
            return view('calendar', ['days' => []]);
        }

        // Get availability for these users
        $availabilities = DB::connection('mysql_bot')
            ->table('availability_recurring')
            ->whereIn('user_id', $activeMatchingUsers->keys())
            ->get();

        // Calculate slots for the next 7 days (Israel Time)
        $now = Carbon::now('Asia/Jerusalem');
        $end = $now->copy()->addDays(7);

        $slots = [];

        foreach ($availabilities as $avail) {
            $user = $activeMatchingUsers[$avail->user_id];

            // Generate dates for this weekday in the next 7 days
            $date = $now->copy();
            for ($i = 0; $i < 8; $i++) {
                if ($date->dayOfWeekIso == $avail->weekday_iso) {
                    $slotTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $avail->time_local, 'Asia/Jerusalem');

                    if ($slotTime->greaterThanOrEqualTo($now) && $slotTime->lessThan($end)) {
                        $dayKey = $slotTime->format('Y-m-d');
                        $timeKey = $slotTime->format('H:i');

                        $slots[$dayKey][$timeKey][] = [
                            'user' => $user,
                            'time' => $slotTime,
                        ];
                    }
                }
                $date->addDay();
            }
        }

        // Sort by day and time
        ksort($slots);
        foreach ($slots as &$daySlots) {
            ksort($daySlots);
        }

        return view('calendar', ['days' => $slots]);
    }
}
