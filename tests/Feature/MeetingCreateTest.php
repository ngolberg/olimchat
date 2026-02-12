<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class MeetingCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_create_requires_authentication()
    {
        $response = $this->postJson('/api/meeting/create');

        $response->assertStatus(401);
    }

    public function test_meeting_create_works_correctly()
    {
        // Mock DB connection and bot database
        DB::shouldReceive('connection')->with('mysql_bot')->andReturnSelf();

        $inviterBotUser = (object)['id' => 1, 'tg_id' => '123456789', 'first_name' => 'Inviter', 'username' => 'inviter', 'lang' => 'ru'];
        $inviteeBotUser = (object)['id' => 2, 'tg_id' => '987654321', 'first_name' => 'Invitee', 'username' => 'invitee', 'lang' => 'en'];

        DB::shouldReceive('table')->with('users')->andReturnSelf();
        DB::shouldReceive('where')->with('tg_id', '123456789')->andReturnSelf();
        DB::shouldReceive('first')->andReturn($inviterBotUser);

        DB::shouldReceive('table')->with('users')->andReturnSelf();
        DB::shouldReceive('where')->with('id', 2)->andReturnSelf();
        DB::shouldReceive('first')->andReturn($inviteeBotUser);

        DB::shouldReceive('table')->with('meetings')->andReturnSelf();
        DB::shouldReceive('updateOrInsert')->andReturn(true);

        DB::shouldReceive('table')->with('meetings')->andReturnSelf();
        DB::shouldReceive('where')->andReturnSelf(); // simplify mocking for sequential wheres
        DB::shouldReceive('first')->andReturn((object)['id' => 100]);

        DB::shouldReceive('table')->with('logs')->andReturnSelf();
        DB::shouldReceive('insert')->andReturn(true);

        $user = User::factory()->create([
            'tg_id' => '123456789'
        ]);

        $response = $this->actingAs($user)->postJson('/api/meeting/create', [
            'user_id' => 2,
            'date' => Carbon::now('Asia/Jerusalem')->addDay()->format('Y-m-d'),
            'time' => '10:00'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success']);
    }
}
