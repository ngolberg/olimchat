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

            $inviteText = $invLink . __('messages.meet.invite_wants_to_meet_prefix', [], $inviteeLang) . e($slotLabel) . '. ' . __('messages.meet.confirm_question', [], $inviteeLang);

            $kb = new InlineKeyboard([
                new InlineKeyboardButton(['text' => __('messages.buttons.confirm', [], $inviteeLang), 'callback_data' => 'meetans|' . $meetingId . '|ok']),
                new InlineKeyboardButton(['text' => __('messages.buttons.decline', [], $inviteeLang), 'callback_data' => 'meetans|' . $meetingId . '|no']),
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
                'text' => str_replace('{{slot}}', e($slotLabelInviter), __('messages.meet.request_sent', [], $inviterLang)),
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

    private function getWeekdayNames(string $lang): array
    {
        return [
            1 => __('messages.weekdays.1', [], $lang),
            2 => __('messages.weekdays.2', [], $lang),
            3 => __('messages.weekdays.3', [], $lang),
            4 => __('messages.weekdays.4', [], $lang),
            5 => __('messages.weekdays.5', [], $lang),
            6 => __('messages.weekdays.6', [], $lang),
            7 => __('messages.weekdays.7', [], $lang),
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
            ->where('status', '!=', 'declined')
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
     * List the current user's upcoming meetings.
     */
    public function myMeetings(Request $request): JsonResponse
    {
        $localUser = Auth::user();
        if (!$localUser) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        if ($localUser->lang) {
            app()->setLocale($localUser->lang);
        }

        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $localUser->tg_id)
            ->first();

        if (!$botUser) {
            return response()->json(['meetings' => []]);
        }

        $now = Carbon::now('Asia/Jerusalem');

        $rows = DB::connection('mysql_bot')
            ->table('meetings as m')
            ->join('users as u1', 'u1.id', '=', 'm.inviter_id')
            ->join('users as u2', 'u2.id', '=', 'm.invitee_id')
            ->where(function ($q) use ($botUser) {
                $q->where('m.inviter_id', $botUser->id)
                  ->orWhere('m.invitee_id', $botUser->id);
            })
            ->whereNotNull('m.meeting_date')
            ->whereIn('m.status', ['accepted', 'waiting'])
            ->whereRaw("CONCAT(m.meeting_date, ' ', m.time_local) >= ?", [$now->format('Y-m-d H:i')])
            ->orderBy('m.meeting_date')
            ->orderBy('m.time_local')
            ->select([
                'm.*',
                'u1.first_name as inviter_name', 'u1.username as inviter_username', 'u1.image as inviter_image',
                'u2.first_name as invitee_name', 'u2.username as invitee_username', 'u2.image as invitee_image',
            ])
            ->get();

        $meetings = $rows->map(function ($row) use ($botUser) {
            $isInviter = (int)$row->inviter_id === (int)$botUser->id;
            $partnerName = $isInviter ? $row->invitee_name : $row->inviter_name;
            $partnerUsername = $isInviter ? $row->invitee_username : $row->inviter_username;
            $partnerImage = $isInviter ? $row->invitee_image : $row->inviter_image;

            $carbonDate = Carbon::parse($row->meeting_date, 'Asia/Jerusalem');
            if ($carbonDate->isToday()) {
                $dayLabel = __('messages.day_today');
            } elseif ($carbonDate->isTomorrow()) {
                $dayLabel = __('messages.day_tomorrow');
            } else {
                $dayLabel = $carbonDate->translatedFormat(__('messages.date_format'));
            }

            return [
                'id' => (int)$row->id,
                'date' => $row->meeting_date,
                'time' => $row->time_local,
                'day_label' => $dayLabel,
                'status' => $row->status,
                'role' => $isInviter ? 'inviter' : 'invitee',
                'partner' => [
                    'name' => (string)$partnerName,
                    'username' => (string)$partnerUsername,
                    'image' => $partnerImage ? '/photos/' . (string)$partnerImage : '/img/logo.jpeg',
                ],
            ];
        });

        return response()->json(['meetings' => $meetings]);
    }

    /**
     * Accept a meeting invitation.
     */
    public function accept(Request $request): JsonResponse
    {
        $request->validate([
            'meeting_id' => 'required|integer',
        ]);

        $localUser = Auth::user();
        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $localUser->tg_id)
            ->first();

        if (!$botUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $meeting = DB::connection('mysql_bot')
            ->table('meetings as m')
            ->join('users as u1', 'u1.id', '=', 'm.inviter_id')
            ->join('users as u2', 'u2.id', '=', 'm.invitee_id')
            ->where('m.id', $request->input('meeting_id'))
            ->select([
                'm.*',
                'u1.lang as inviter_lang', 'u1.tg_id as inviter_tg',
                'u1.username as inviter_username', 'u1.first_name as inviter_name',
                'u2.lang as invitee_lang', 'u2.tg_id as invitee_tg',
                'u2.username as invitee_username', 'u2.first_name as invitee_name',
            ])
            ->first();

        if (!$meeting) {
            return response()->json(['error' => 'Meeting not found'], 404);
        }

        if ((int)$meeting->invitee_id !== (int)$botUser->id) {
            return response()->json(['error' => 'Only the invitee can accept'], 403);
        }

        if ($meeting->status !== 'waiting') {
            return response()->json(['error' => 'Meeting is not in waiting status'], 409);
        }

        DB::connection('mysql_bot')
            ->table('meetings')
            ->where('id', $meeting->id)
            ->update(['status' => 'accepted']);

        $this->logMeetingActivity('invite_accepted', (int)$meeting->invitee_id, (int)$meeting->inviter_id);

        $dateHuman = !empty($meeting->meeting_date) ? Carbon::parse($meeting->meeting_date, 'Asia/Jerusalem')->format('d.m.Y') : '';

        // Build user links
        $inviterName = $meeting->inviter_name ?: ($meeting->inviter_username ? '@' . $meeting->inviter_username : 'Participant');
        $inviteeName = $meeting->invitee_name ?: ($meeting->invitee_username ? '@' . $meeting->invitee_username : 'Participant');
        $inviterLink = $meeting->inviter_username
            ? '<a href="https://t.me/' . e($meeting->inviter_username) . '">@' . e($meeting->inviter_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->inviter_tg . '">' . e($inviterName) . '</a>';
        $inviteeLink = $meeting->invitee_username
            ? '<a href="https://t.me/' . e($meeting->invitee_username) . '">@' . e($meeting->invitee_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->invitee_tg . '">' . e($inviteeName) . '</a>';

        // Notify inviter: meeting accepted
        $inviterLang = $meeting->inviter_lang ?: 'ru';
        $weekdayNamesInv = $this->getWeekdayNames($inviterLang);
        $slotLabelInv = ($weekdayNamesInv[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInviter = str_replace(['{{slot}}', '{{user}}'], [e($slotLabelInv), $inviteeLink], __('messages.meet.inviter_notif_accepted', [], $inviterLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->inviter_tg,
            'text' => $msgInviter,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('accept_notified_inviter', (int)$meeting->inviter_id, (int)$meeting->invitee_id);

        // Notify invitee: you confirmed
        $inviteeLang = $meeting->invitee_lang ?: 'ru';
        $weekdayNamesIne = $this->getWeekdayNames($inviteeLang);
        $slotLabelIne = ($weekdayNamesIne[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInvitee = str_replace(['{{slot}}', '{{user}}'], [e($slotLabelIne), $inviterLink], __('messages.meet.you_confirmed', [], $inviteeLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->invitee_tg,
            'text' => $msgInvitee,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('accept_notified_invitee', (int)$meeting->invitee_id, (int)$meeting->inviter_id);

        return response()->json(['success' => true]);
    }

    /**
     * Decline a meeting invitation.
     */
    public function decline(Request $request): JsonResponse
    {
        $request->validate([
            'meeting_id' => 'required|integer',
        ]);

        $localUser = Auth::user();
        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $localUser->tg_id)
            ->first();

        if (!$botUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $meeting = DB::connection('mysql_bot')
            ->table('meetings as m')
            ->join('users as u1', 'u1.id', '=', 'm.inviter_id')
            ->join('users as u2', 'u2.id', '=', 'm.invitee_id')
            ->where('m.id', $request->input('meeting_id'))
            ->select([
                'm.*',
                'u1.lang as inviter_lang', 'u1.tg_id as inviter_tg',
                'u1.username as inviter_username', 'u1.first_name as inviter_name',
                'u2.lang as invitee_lang', 'u2.tg_id as invitee_tg',
                'u2.username as invitee_username', 'u2.first_name as invitee_name',
            ])
            ->first();

        if (!$meeting) {
            return response()->json(['error' => 'Meeting not found'], 404);
        }

        if ((int)$meeting->invitee_id !== (int)$botUser->id) {
            return response()->json(['error' => 'Only the invitee can decline'], 403);
        }

        if ($meeting->status !== 'waiting') {
            return response()->json(['error' => 'Meeting is not in waiting status'], 409);
        }

        DB::connection('mysql_bot')
            ->table('meetings')
            ->where('id', $meeting->id)
            ->update(['status' => 'declined']);

        $this->logMeetingActivity('invite_declined', (int)$meeting->invitee_id, (int)$meeting->inviter_id);

        $dateHuman = !empty($meeting->meeting_date) ? Carbon::parse($meeting->meeting_date, 'Asia/Jerusalem')->format('d.m.Y') : '';

        // Build user links
        $inviterName = $meeting->inviter_name ?: ($meeting->inviter_username ? '@' . $meeting->inviter_username : 'Participant');
        $inviteeName = $meeting->invitee_name ?: ($meeting->invitee_username ? '@' . $meeting->invitee_username : 'Participant');
        $inviterLink = $meeting->inviter_username
            ? '<a href="https://t.me/' . e($meeting->inviter_username) . '">@' . e($meeting->inviter_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->inviter_tg . '">' . e($inviterName) . '</a>';
        $inviteeLink = $meeting->invitee_username
            ? '<a href="https://t.me/' . e($meeting->invitee_username) . '">@' . e($meeting->invitee_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->invitee_tg . '">' . e($inviteeName) . '</a>';

        // Notify inviter: meeting declined
        $inviterLang = $meeting->inviter_lang ?: 'ru';
        $weekdayNamesInv = $this->getWeekdayNames($inviterLang);
        $slotLabelInv = ($weekdayNamesInv[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInviter = str_replace(['{{slot}}', '{{user}}'], [e($slotLabelInv), $inviteeLink], __('messages.meet.inviter_notif_declined', [], $inviterLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->inviter_tg,
            'text' => $msgInviter,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('decline_notified_inviter', (int)$meeting->inviter_id, (int)$meeting->invitee_id);

        // Notify invitee: you declined
        $inviteeLang = $meeting->invitee_lang ?: 'ru';
        $weekdayNamesIne = $this->getWeekdayNames($inviteeLang);
        $slotLabelIne = ($weekdayNamesIne[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInvitee = str_replace(['{{slot}}', '{{user}}'], [e($slotLabelIne), $inviterLink], __('messages.meet.you_declined', [], $inviteeLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->invitee_tg,
            'text' => $msgInvitee,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('decline_notified_invitee', (int)$meeting->invitee_id, (int)$meeting->inviter_id);

        return response()->json(['success' => true]);
    }

    /**
     * Cancel a meeting (by either participant).
     */
    public function cancel(Request $request): JsonResponse
    {
        $request->validate([
            'meeting_id' => 'required|integer',
        ]);

        $localUser = Auth::user();
        $botUser = DB::connection('mysql_bot')
            ->table('users')
            ->where('tg_id', $localUser->tg_id)
            ->first();

        if (!$botUser) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $meeting = DB::connection('mysql_bot')
            ->table('meetings as m')
            ->join('users as u1', 'u1.id', '=', 'm.inviter_id')
            ->join('users as u2', 'u2.id', '=', 'm.invitee_id')
            ->where('m.id', $request->input('meeting_id'))
            ->select([
                'm.*',
                'u1.lang as inviter_lang', 'u1.tg_id as inviter_tg',
                'u1.username as inviter_username', 'u1.first_name as inviter_name',
                'u2.lang as invitee_lang', 'u2.tg_id as invitee_tg',
                'u2.username as invitee_username', 'u2.first_name as invitee_name',
            ])
            ->first();

        if (!$meeting) {
            return response()->json(['error' => 'Meeting not found'], 404);
        }

        if ((int)$meeting->inviter_id !== (int)$botUser->id && (int)$meeting->invitee_id !== (int)$botUser->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        DB::connection('mysql_bot')
            ->table('meetings')
            ->where('id', $meeting->id)
            ->update(['status' => 'declined']);

        $actorId = (int)$botUser->id;
        $otherId = ((int)$meeting->inviter_id === $actorId) ? (int)$meeting->invitee_id : (int)$meeting->inviter_id;
        $this->logMeetingActivity('meeting_canceled', $actorId, $otherId);

        $actorIsInviter = ((int)$meeting->inviter_id === $actorId);
        $dateHuman = !empty($meeting->meeting_date) ? Carbon::parse($meeting->meeting_date, 'Asia/Jerusalem')->format('d.m.Y') : '';

        // Build user links
        $inviterName = $meeting->inviter_name ?: ($meeting->inviter_username ? '@' . $meeting->inviter_username : 'Participant');
        $inviteeName = $meeting->invitee_name ?: ($meeting->invitee_username ? '@' . $meeting->invitee_username : 'Participant');
        $inviterLink = $meeting->inviter_username
            ? '<a href="https://t.me/' . e($meeting->inviter_username) . '">@' . e($meeting->inviter_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->inviter_tg . '">' . e($inviterName) . '</a>';
        $inviteeLink = $meeting->invitee_username
            ? '<a href="https://t.me/' . e($meeting->invitee_username) . '">@' . e($meeting->invitee_username) . '</a>'
            : '<a href="tg://user?id=' . $meeting->invitee_tg . '">' . e($inviteeName) . '</a>';

        // Notify inviter
        $inviterLang = $meeting->inviter_lang ?: 'ru';
        $weekdayNamesInv = $this->getWeekdayNames($inviterLang);
        $slotLabelInv = ($weekdayNamesInv[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInviter = $actorIsInviter
            ? str_replace(['{{slot}}', '{{user}}'], [e($slotLabelInv), $inviteeLink], __('messages.meeting.you_canceled', [], $inviterLang))
            : str_replace(['{{slot}}', '{{user}}'], [e($slotLabelInv), $inviteeLink], __('messages.meeting.canceled_by_user', [], $inviterLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->inviter_tg,
            'text' => $msgInviter,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('cancellation_notified_inviter', (int)$meeting->inviter_id, $actorId);

        // Notify invitee
        $inviteeLang = $meeting->invitee_lang ?: 'ru';
        $weekdayNamesIne = $this->getWeekdayNames($inviteeLang);
        $slotLabelIne = ($weekdayNamesIne[(int)$meeting->weekday_iso] ?? $meeting->weekday_iso) . ($dateHuman ? ', ' . $dateHuman : '') . ' ' . $meeting->time_local;
        $msgInvitee = !$actorIsInviter
            ? str_replace(['{{slot}}', '{{user}}'], [e($slotLabelIne), $inviterLink], __('messages.meeting.you_canceled', [], $inviteeLang))
            : str_replace(['{{slot}}', '{{user}}'], [e($slotLabelIne), $inviterLink], __('messages.meeting.canceled_by_user', [], $inviteeLang));
        TelegramRequest::sendMessage([
            'chat_id' => (string)$meeting->invitee_tg,
            'text' => $msgInvitee,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);
        $this->logMeetingActivity('cancellation_notified_invitee', (int)$meeting->invitee_id, $actorId);

        return response()->json(['success' => true]);
    }
}
