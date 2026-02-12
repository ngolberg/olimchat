<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(\Longman\TelegramBot\Telegram::class, function ($app) {
            $config = $app['config']['services.telegram'];
            return new \Longman\TelegramBot\Telegram(
                $config['bot_token'] ?? '',
                $config['bot_username'] ?? ''
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
