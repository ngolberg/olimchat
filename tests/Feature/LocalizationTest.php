<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;
    public function test_it_displays_english_by_default(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Community of Hebrew students');
        $response->assertSee('How it works');
    }

    public function test_it_switches_to_russian_via_query_param(): void
    {
        $response = $this->get('/?lang=ru');

        $response->assertStatus(200);
        $response->assertSee('Сообщество студентов иврита');
        $response->assertSee('Как это работает');
        $this->assertEquals('ru', session('locale'));
    }

    public function test_it_persists_locale_in_session(): void
    {
        $this->withSession(['locale' => 'ru'])->get('/');
        $this->assertEquals('ru', app()->getLocale());

        $response = $this->get('/');
        $response->assertSee('Сообщество студентов иврита');
    }

    public function test_calendar_page_localization(): void
    {
        // Mock DB connection and user query (by auth token) for Russian
        \Illuminate\Support\Facades\DB::shouldReceive('connection')
            ->with('mysql_bot')
            ->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('table')
            ->with('users')
            ->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('where')
            ->with('auth', 'TOKEN_RU')
            ->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('first')
            ->andReturn((object)['tg_id' => '123', 'name' => 'Test']);

        // Login via bot and redirect to calendar with ru locale
        $login = $this->get('/botlogin?auth=TOKEN_RU&to=/calendar?lang=ru');
        $login->assertRedirect('/calendar?lang=ru');

        $response = $this->get('/calendar?lang=ru');
        $response->assertSee('Расписание');
        $response->assertSee('Привет, Test! Вот твои совпадения для общения');

        // Reset mocks for English
        \Illuminate\Support\Facades\DB::spy();
        \Illuminate\Support\Facades\DB::shouldReceive('connection')->with('mysql_bot')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('table')->with('users')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('where')->with('auth', 'TOKEN_EN')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('first')->andReturn((object)['tg_id' => '456', 'name' => 'User']);

        $login = $this->get('/botlogin?auth=TOKEN_EN&to=/calendar?lang=en');
        $login->assertRedirect('/calendar?lang=en');

        $response = $this->get('/calendar?lang=en');
        $response->assertSee('Timeline');
        $response->assertSee('Hi, User! Here are your matches to talk');
    }

    public function test_calendar_prohibited_access_localization(): void
    {
        // Unauthenticated access should redirect to /403 and show localized content
        $response = $this->get('/calendar?lang=en');
        $response->assertRedirect('/403');
        $page = $this->get('/403?lang=en');
        $page->assertStatus(403);
        $page->assertSee(__('messages.forbidden_title', [], 'en'));
        $page->assertSee(__('messages.forbidden_message', [], 'en'));

        $response = $this->get('/calendar?lang=ru');
        $response->assertRedirect('/403');
        $page = $this->get('/403?lang=ru');
        $page->assertStatus(403);
        $page->assertSee(__('messages.forbidden_title', [], 'ru'));
        $page->assertSee(__('messages.forbidden_message', [], 'ru'));

        // Failed bot login should redirect to /403
        \Illuminate\Support\Facades\DB::shouldReceive('connection')->with('mysql_bot')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('table')->with('users')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('where')->with('auth', 'BAD')->andReturnSelf();
        \Illuminate\Support\Facades\DB::shouldReceive('first')->andReturn(null);

        $login = $this->get('/botlogin?auth=BAD&to=/calendar?lang=en');
        $login->assertRedirect('/403');
    }

    public function test_language_switcher_is_present(): void
    {
        $response = $this->get('/');
        $response->assertSee('?lang=en');
        $response->assertSee('?lang=ru');
    }
}
