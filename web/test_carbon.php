<?php
require __DIR__ . '/vendor/autoload.php';

use Carbon\Carbon;

$locales = ['en', 'ru'];
foreach ($locales as $locale) {
    app()->setLocale($locale);
    Carbon::setLocale($locale);
    echo "Locale: $locale\n";
    echo "Today: " . ($locale == 'en' ? 'Today' : 'Сегодня') . "\n";
    echo "Formatted: " . Carbon::parse('2026-02-09')->translatedFormat('l, j F') . "\n\n";
}
