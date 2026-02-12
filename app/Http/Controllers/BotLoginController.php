<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User as AppUser;
use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request as TelegramRequest;

class BotLoginController extends Controller
{
    /**
     * Authenticate a user via bot token and redirect to a relative path.
     */
    public function login(Request $request): RedirectResponse
    {
        $authToken = $request->query('auth');
        $to = (string) $request->query('to', '/');

        $user = null;
        if ($authToken) {
            $user = DB::connection('mysql_bot')
                ->table('users')
                ->where('auth', $authToken)
                ->first();
        }

        if (!$user) {
            return redirect('/403');
        }

        // Authenticate the user in the Laravel app using a local User record
        $email = isset($user->tg_id)
            ? ('tg_' . $user->tg_id . '@olimchat.me')
            : ('bot_user_' . md5($authToken) . '@olimchat.me');
        $name = $user->name ?? ($user->username ?? 'User');

        $localUser = AppUser::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Str::random(32),
                'tg_id' => $user->tg_id ?? null,
                'lang' => $user->lang ?? 'ru',
            ]
        );

        if ($localUser->wasRecentlyCreated === false) {
            $updates = [];
            if (isset($user->tg_id) && $localUser->tg_id !== (string)$user->tg_id) {
                $updates['tg_id'] = $user->tg_id;
            }
            if (isset($user->lang) && $localUser->lang !== (string)$user->lang) {
                $updates['lang'] = $user->lang;
            }
            if (!empty($updates)) {
                $localUser->update($updates);
            }
        }

        if ($localUser->wasRecentlyCreated && !$user->image && $user->tg_id) {
            $this->downloadTelegramPhoto((int) $user->tg_id);
        }

        Auth::login($localUser);

        // Sanitize and ensure a relative path for redirect
        $to = trim($to);
        if ($to === '') {
            $to = '/';
        }
        // Disallow absolute URLs or protocol-relative URLs
        if (preg_match('#^([a-z]+:)?//#i', $to)) {
            $to = '/';
        }
        // Ensure starts with '/'
        if ($to[0] !== '/') {
            $to = '/' . ltrim($to, '/');
        }

        return redirect($to);
    }

    private function downloadTelegramPhoto(int $tgId): void
    {
        try {
            app(Telegram::class);

            $response = TelegramRequest::getUserProfilePhotos([
                'user_id' => $tgId,
                'limit'   => 1,
            ]);

            if (!$response->isOk()) {
                return;
            }

            $photos = $response->getResult()->getPhotos();
            if (empty($photos)) {
                return;
            }

            // Get the largest size of the first photo
            $sizes = $photos[0];
            $photo = end($sizes);

            $fileResponse = TelegramRequest::getFile([
                'file_id' => $photo->getFileId(),
            ]);

            if (!$fileResponse->isOk()) {
                return;
            }

            $filePath = $fileResponse->getResult()->getFilePath();
            $botToken = config('services.telegram.bot_token');
            $url = "https://api.telegram.org/file/bot{$botToken}/{$filePath}";

            $imageContent = file_get_contents($url);
            if (!$imageContent) {
                return;
            }

            $filename = $tgId . '.jpg';
            file_put_contents(public_path('photos/' . $filename), $imageContent);

            DB::connection('mysql_bot')
                ->table('users')
                ->where('tg_id', $tgId)
                ->update(['image' => $filename]);
        } catch (\Exception $e) {
            // Don't fail login if photo download fails
        }
    }
}
