<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;

class LanguageController extends Controller
{
    /**
     * Update the user's language preference.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'lang' => 'required|string|in:en,ru',
        ]);

        $lang = $request->input('lang');
        $user = Auth::user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Update local user
        $user->update(['lang' => $lang]);

        // Sync with bot database if tg_id exists
        if ($user->tg_id) {
            try {
                DB::connection('mysql_bot')
                    ->table('users')
                    ->where('tg_id', $user->tg_id)
                    ->update(['lang' => $lang]);
            } catch (\Exception $e) {
                // Log error or ignore if bot DB is not reachable
            }
        }

        app()->setLocale($lang);
        session(['locale' => $lang]);

        return response()->json([
            'status' => 'success',
            'lang' => $lang
        ]);
    }
}
