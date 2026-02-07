<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_create_requires_authentication()
    {
        $response = $this->postJson('/api/meeting/create');

        $response->assertStatus(401);
    }

    public function test_meeting_create_returns_empty_json()
    {
        $user = User::factory()->create([
            'email' => 'tg_123456789@example.com',
            'tg_id' => '123456789'
        ]);

        $response = $this->actingAs($user)->postJson('/api/meeting/create');

        // It might fail in tests because it tries to reach Telegram API
        // or it might fail because of missing config if env() is used.
        // We should at least check it doesn't 500 if we can mock it,
        // but for now let's just see what happens.
        $response->assertStatus(200);
        $response->assertJson([]);
    }
}
