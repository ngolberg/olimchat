<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request as TelegramRequest;

class MeetingController extends Controller
{
    /**
     * @var Telegram
     */
    private Telegram $telegram;

    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Create a new meeting.
     */
    public function store(Request $request): JsonResponse
    {
        $localUser = Auth::user();
        $tgId = $localUser->tg_id;

        try {
            $result = TelegramRequest::sendMessage([
                'chat_id' => $tgId,
                'text'    => 'This is a test message from the MeetingController!',
            ]);

            if (!$result->isOk()) {
                return response()->json(['error' => 'Failed to send Telegram message: ' . $result->getDescription()], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Telegram Bot error: ' . $e->getMessage()], 500);
        }

        return response()->json([]);
    }

    /**
     * Get the calendar data for the current user.
     */
    public function index(Request $request): JsonResponse
    {
        $localUser = Auth::user();
        if (!$localUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Use tg_id field from user model
        $tgId = $localUser->tg_id;
        if (!$tgId) {
            return response()->json(['days' => []]);
        }

        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $tgId)
            ->first();

        if (!$botUser) {
            return response()->json(['days' => []]);
        }

        // Get user levels
        $myLevels = DB::connection('mysql_bot')
            ->table('user_levels')
            ->where('user_id', $botUser->id)
            ->pluck('level')
            ->toArray();

        if (empty($myLevels)) {
            return response()->json(['days' => []]);
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
            return response()->json(['days' => []]);
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
                            'user' => [
                                'first_name' => $user->first_name,
                                'last_name' => $user->last_name,
                                'username' => $user->username,
                                'image' => $user->image ? '/photos/' . $user->image : '/img/logo.jpeg',
                            ],
                            'time' => $timeKey,
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

        // Add localized labels
        $formattedDays = [];
        foreach ($slots as $date => $times) {
            $carbonDate = Carbon::parse($date);
            if ($carbonDate->isToday()) {
                $dayLabel = __('messages.day_today');
            } elseif ($carbonDate->isTomorrow()) {
                $dayLabel = __('messages.day_tomorrow');
            } else {
                $dayLabel = $carbonDate->translatedFormat('l, j F');
            }

            $formattedDays[] = [
                'date' => $date,
                'label' => $dayLabel,
                'times' => $times
            ];
        }

        return response()->json(['days' => $formattedDays]);
    }
}
