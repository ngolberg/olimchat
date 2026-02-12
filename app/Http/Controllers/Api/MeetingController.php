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
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\InlineKeyboardButton;

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
        $request->validate([
            'user_id' => 'required|integer',
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);

        $localUser = Auth::user();
        $inviterBotUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $localUser->tg_id)
            ->first();

        $partnerId = $request->input('user_id');
        $inviteeBotUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('id', $partnerId)
            ->first();

        $dateStr = $request->input('date');
        $timeStr = $request->input('time');
        $carbonDate = Carbon::parse($dateStr, 'Asia/Jerusalem');
        $weekdayIso = $carbonDate->dayOfWeekIso;

        // Reproduce logic: Calculate next calendar date
        $nowLocal = Carbon::now('Asia/Jerusalem');
        $todayWd = $nowLocal->dayOfWeekIso;
        $delta = ($weekdayIso - $todayWd + 7) % 7;

        if ($delta === 0) {
            [$hNow, $mNow] = [(int)$nowLocal->format('H'), (int)$nowLocal->format('i')];
            [$hSel, $mSel] = array_map('intval', explode(':', $timeStr));
            if ($hSel < $hNow || ($hSel === $hNow && $mSel <= $mNow)) {
                $delta = 7;
            }
        }
        $meetingDate = $nowLocal->copy()->addDays($delta)->format('Y-m-d');

        try {
            DB::connection('mysql_bot')->table('meetings')->updateOrInsert(
                [
                    'inviter_id' => $inviterBotUser->id,
                    'invitee_id' => $inviteeBotUser->id,
                    'meeting_date' => $meetingDate,
                    'time_local' => $timeStr,
                ],
                [
                    'weekday_iso' => $weekdayIso,
                    'status' => 'waiting',
                    'updated_at' => now(),
                ]
            );

            $meeting = DB::connection('mysql_bot')->table('meetings')
                ->where('inviter_id', $inviterBotUser->id)
                ->where('invitee_id', $inviteeBotUser->id)
                ->where('meeting_date', $meetingDate)
                ->where('time_local', $timeStr)
                ->first();

            $meetingId = $meeting->id;

            // Notifications
            $invName = $inviterBotUser->first_name ?: ($inviterBotUser->username ? '@' . $inviterBotUser->username : 'Участник');
            if ($inviterBotUser->username) {
                $invLink = '<a href="https://t.me/' . e($inviterBotUser->username) . '">@' . e($inviterBotUser->username) . '</a>';
            } else {
                $invLink = '<a href="tg://user?id=' . $inviterBotUser->tg_id . '">' . e($invName) . '</a>';
            }

            $dateHuman = Carbon::parse($meetingDate)->format('d.m.Y');
            $inviteeLang = $inviteeBotUser->lang ?: 'ru';

            // Localized slot label
            $weekdayNamesInvitee = $this->getWeekdayNames($inviteeLang);
            $slotLabel = ($weekdayNamesInvitee[$weekdayIso] ?? $weekdayIso) . ', ' . $dateHuman . ' ' . $timeStr;

            $inviteText = $invLink . $this->getMsg('meet.invite_wants_to_meet_prefix', $inviteeLang) . e($slotLabel) . '. ' . $this->getMsg('meet.confirm_question', $inviteeLang);

            $kb = new InlineKeyboard([
                new InlineKeyboardButton(['text' => $this->getMsg('buttons.confirm', $inviteeLang), 'callback_data' => 'meetans|' . $meetingId . '|ok']),
                new InlineKeyboardButton(['text' => $this->getMsg('buttons.decline', $inviteeLang), 'callback_data' => 'meetans|' . $meetingId . '|no']),
            ]);

            // Send to invitee
            TelegramRequest::sendMessage([
                'chat_id' => $inviteeBotUser->tg_id,
                'text' => $inviteText,
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
                'reply_markup' => $kb,
            ]);
            $this->logMeetingActivity('invite_sent_to_invitee', $inviteeBotUser->id, $inviterBotUser->id);

            // Notify inviter
            $inviterLang = $inviterBotUser->lang ?: 'ru';
            $weekdayNamesInviter = $this->getWeekdayNames($inviterLang);
            $slotLabelInviter = ($weekdayNamesInviter[$weekdayIso] ?? $weekdayIso) . ', ' . $dateHuman . ' ' . $timeStr;

            TelegramRequest::sendMessage([
                'chat_id' => $inviterBotUser->tg_id,
                'text' => str_replace('{{slot}}', e($slotLabelInviter), $this->getMsg('meet.request_sent', $inviterLang)),
                'parse_mode' => 'HTML',
                'disable_web_page_preview' => true,
            ]);
            $this->logMeetingActivity('invite_notify_inviter', $inviterBotUser->id, $inviteeBotUser->id);
            $this->logMeetingActivity('invite_created', $inviterBotUser->id, $inviteeBotUser->id);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error creating meeting: ' . $e->getMessage()], 500);
        }

        return response()->json(['status' => 'success']);
    }

    private function getMsg(string $key, string $lang): string
    {
        $file = base_path('../hebrewPeer2Peer/locales/' . $lang . '/messages.php');
        if (!file_exists($file)) {
            $file = base_path('../hebrewPeer2Peer/locales/ru/messages.php');
        }
        $messages = require $file;
        return $messages[$key] ?? $key;
    }

    private function getWeekdayNames(string $lang): array
    {
        return [
            1 => $this->getMsg('weekdays.1', $lang),
            2 => $this->getMsg('weekdays.2', $lang),
            3 => $this->getMsg('weekdays.3', $lang),
            4 => $this->getMsg('weekdays.4', $lang),
            5 => $this->getMsg('weekdays.5', $lang),
            6 => $this->getMsg('weekdays.6', $lang),
            7 => $this->getMsg('weekdays.7', $lang),
        ];
    }

    private function logMeetingActivity(string $action, int $userId, ?int $addresseeId = null): void
    {
        try {
            DB::connection('mysql_bot')->table('logs')->insert([
                'action' => $action,
                'user_id' => $userId,
                'addressee_id' => $addresseeId,
                'created_at' => now(),
            ]);
        } catch (\Exception $e) {
            // ignore
        }
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

        if ($localUser->lang) {
            app()->setLocale($localUser->lang);
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
            ->pluck('user_id')
            ->toArray();

        $activeMatchingUsers = DB::connection('mysql_bot')
            ->table('users')
            ->whereIn('id', $matchingUserIds)
            ->where('active', 1)
            ->get()
            ->keyBy('id');

        if ($activeMatchingUsers->isEmpty()) {
            return response()->json(['days' => []]);
        }

        // Calculate slots for the next 7 days (Israel Time)
        $now = Carbon::now('Asia/Jerusalem');
        $end = $now->copy()->addDays(7);

        // Get availability for these users
        $availabilities = DB::connection('mysql_bot')
            ->table('availability_recurring')
            ->whereIn('user_id', $activeMatchingUsers->keys())
            ->get();

        // Get meetings involving the current user in the next 7 days
        $meetings = DB::connection('mysql_bot')
            ->table('meetings')
            ->where(function ($query) use ($botUser) {
                $query->where('inviter_id', $botUser->id)
                    ->orWhere('invitee_id', $botUser->id);
            })
            ->where('meeting_date', '>=', $now->format('Y-m-d'))
            ->where('meeting_date', '<=', $end->format('Y-m-d'))
            ->get();

        $meetingsBySlot = [];
        foreach ($meetings as $m) {
            $key = $m->meeting_date . ' ' . $m->time_local;
            $meetingsBySlot[$key][$m->inviter_id][$m->invitee_id] = $m;
        }

        $slots = [];

        foreach ($availabilities as $avail) {
            $availUserId = (int)($avail->user_id ?? 0);
            if (!$availUserId || !isset($activeMatchingUsers[$availUserId])) {
                continue;
            }
            $user = $activeMatchingUsers[$availUserId];

            // Generate dates for this weekday in the next 7 days
            $date = $now->copy();
            for ($i = 0; $i < 8; $i++) {
                if ($date->dayOfWeekIso == $avail->weekday_iso) {
                    $slotTime = Carbon::createFromFormat('Y-m-d H:i', $date->format('Y-m-d') . ' ' . $avail->time_local, 'Asia/Jerusalem');

                    if ($slotTime->greaterThanOrEqualTo($now) && $slotTime->lessThan($end)) {
                        $dayKey = $slotTime->format('Y-m-d');
                        $timeKey = $slotTime->format('H:i');

                        $meetingStatus = null;
                        $meetingRole = null;
                        $meetingId = null;

                        // Check if current user is inviter
                        if (isset($meetingsBySlot[$dayKey . ' ' . $timeKey][$botUser->id][$user->id])) {
                            $m = $meetingsBySlot[$dayKey . ' ' . $timeKey][$botUser->id][$user->id];
                            $meetingStatus = $m->status;
                            $meetingRole = 'inviter';
                            $meetingId = $m->id;
                        }
                        // Check if current user is invitee
                        elseif (isset($meetingsBySlot[$dayKey . ' ' . $timeKey][$user->id][$botUser->id])) {
                            $m = $meetingsBySlot[$dayKey . ' ' . $timeKey][$user->id][$botUser->id];
                            $meetingStatus = $m->status;
                            $meetingRole = 'invitee';
                            $meetingId = $m->id;
                        }

                        $slotData = [
                            'user' => [
                                'id' => (int)$user->id,
                                'first_name' => (string)$user->first_name,
                                'last_name' => (string)$user->last_name,
                                'username' => (string)$user->username,
                                'image' => $user->image ? '/photos/' . (string)$user->image : '/img/logo.jpeg',
                            ],
                            'time' => (string)$timeKey,
                            'meeting' => $meetingStatus ? [
                                'id' => $meetingId,
                                'status' => $meetingStatus,
                                'role' => $meetingRole,
                            ] : null,
                        ];
                        $slots[$dayKey][$timeKey][] = $slotData;
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
                $dayLabel = $carbonDate->translatedFormat(__('messages.date_format'));
            }

            $formattedDays[] = [
                'date' => $date,
                'label' => $dayLabel,
                'times' => $times
            ];
        }

        return response()->json(['days' => $formattedDays]);
    }

    /**
     * Accept a meeting invitation.
     */
    public function accept(Request $request): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    /**
     * Decline a meeting invitation.
     */
    public function decline(Request $request): JsonResponse
    {
        return response()->json(['success' => true]);
    }

    /**
     * Cancel a meeting invitation.
     */
    public function cancel(Request $request): JsonResponse
    {
        return response()->json(['success' => true]);
    }
}
