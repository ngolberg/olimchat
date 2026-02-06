<?php

namespace Tests\Feature;

use Tests\TestCase;

class ForbiddenPageTest extends TestCase
{
    /** @test */
    public function unauthenticated_calendar_redirects_to_403(): void
    {
        $response = $this->get('/calendar');
        $response->assertRedirect('/403');
    }

    /** @test */
    public function forbidden_page_shows_localized_message_en(): void
    {
        $response = $this->get('/403?lang=en');
        $response->assertStatus(403);
        $response->assertSee(__('messages.forbidden_title', [], 'en'));
        $response->assertSee(__('messages.forbidden_message', [], 'en'));
        $response->assertSee('HebrewPeer2Peer_bot');
    }

    /** @test */
    public function forbidden_page_shows_localized_message_ru(): void
    {
        $response = $this->get('/403?lang=ru');
        $response->assertStatus(403);
        $response->assertSee(__('messages.forbidden_title', [], 'ru'));
        $response->assertSee(__('messages.forbidden_message', [], 'ru'));
        $response->assertSee('HebrewPeer2Peer_bot');
    }
}
