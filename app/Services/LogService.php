<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LogService
{
    public function log(string $action, int $userId, ?int $addresseeId = null): void
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
}
