<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

class LanguageControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_update_language()
    {
        $user = User::factory()->create([
            'tg_id' => '123456789',
            'lang' => 'ru'
        ]);

        // Mock bot DB
        DB::shouldReceive('connection')->with('mysql_bot')->andReturnSelf();
        DB::shouldReceive('table')->with('users')->andReturnSelf();
        DB::shouldReceive('where')->with('tg_id', '123456789')->andReturnSelf();
        DB::shouldReceive('update')->with(['lang' => 'en'])->andReturn(1);

        $response = $this->actingAs($user)->postJson('/api/user/language', [
            'lang' => 'en'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'lang' => 'en']);

        $user->refresh();
        $this->assertEquals('en', $user->lang);
    }

    public function test_guest_cannot_update_language()
    {
        $response = $this->postJson('/api/user/language', [
            'lang' => 'en'
        ]);

        $response->assertStatus(401);
    }

    public function test_invalid_language_rejected()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/user/language', [
            'lang' => 'fr'
        ]);

        $response->assertStatus(422);
    }
}
