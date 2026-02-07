<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\User as AppUser;

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
            ]
        );

        if ($localUser->wasRecentlyCreated === false && isset($user->tg_id) && $localUser->tg_id !== (string)$user->tg_id) {
            $localUser->update(['tg_id' => $user->tg_id]);
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
}
