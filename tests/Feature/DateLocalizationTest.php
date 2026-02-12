<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DateLocalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_calendar_dates_are_localized()
    {
        $user = User::factory()->create([
            'tg_id' => '123456789',
            'lang' => 'ru'
        ]);

        // Mock bot DB for MeetingController::index
        DB::shouldReceive('connection')->with('mysql_bot')->andReturnSelf();
        DB::shouldReceive('table')->with('users')->andReturnSelf();
        DB::shouldReceive('where')->with('tg_id', '123456789')->andReturnSelf();
        DB::shouldReceive('first')->andReturn((object)['id' => 1, 'tg_id' => '123456789']);

        DB::shouldReceive('table')->with('user_levels')->andReturnSelf();
        DB::shouldReceive('whereIn')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('pluck')->andReturn(collect([1]));
        DB::shouldReceive('distinct')->andReturnSelf();

        DB::shouldReceive('table')->with('users')->andReturnSelf();
        DB::shouldReceive('whereIn')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([(object)['id' => 2, 'first_name' => 'Partner', 'last_name' => '', 'username' => 'partner', 'active' => 1, 'image' => '']]));

        DB::shouldReceive('table')->with('availability_recurring')->andReturnSelf();
        DB::shouldReceive('whereIn')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([
            (object)['user_id' => 2, 'weekday_iso' => Carbon::now('Asia/Jerusalem')->addDays(2)->dayOfWeekIso, 'time_local' => '10:00']
        ]));

        DB::shouldReceive('table')->with('meetings')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf();
        DB::shouldReceive('get')->andReturn(collect([]));

        $response = $this->actingAs($user)->getJson('/api/calendar');

        $response->assertStatus(200);
        $data = $response->json();

        // Check for today/tomorrow
        $hasToday = false;
        $hasTomorrow = false;
        $hasLocalizedDate = false;

        foreach ($data['days'] as $day) {
            if ($day['label'] === 'Сегодня') $hasToday = true;
            if ($day['label'] === 'Завтра') $hasTomorrow = true;
            // The 3rd day should be localized using the format 'l, j F' in Russian
            // e.g. "среда, 11 февраля"
            if (!in_array($day['label'], ['Сегодня', 'Завтра'])) {
                $hasLocalizedDate = true;
                // Simple regex check for russian characters in the label
                $this->assertTrue(preg_match('/[а-яА-Я]/', $day['label']) === 1, "Label '{$day['label']}' should contain Russian characters");
            }
        }

        $this->assertTrue($hasLocalizedDate, "Should have localized dates beyond today/tomorrow");
    }
}
