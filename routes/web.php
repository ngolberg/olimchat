<?php


use App\Http\Controllers\BotLoginController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\AppConfigController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/config', [AppConfigController::class, 'index']);
    Route::middleware('auth')->get('/calendar', [MeetingController::class, 'index']);
    Route::middleware('auth')->post('/meeting/create', [MeetingController::class, 'store']);
});

Route::get('/botlogin', [BotLoginController::class, 'login'])->name('botlogin');

Route::get('/403', function () {
    return response()->view('errors.403', [], 403);
})->name('forbidden');

Route::get('/{any}', function () {
    return view('spa');
})->where('any', '.*');

/*Route::get('/', function () {
    return view('index');
});

Route::middleware('auth')->group(function() {
    Route::get('/calendar', [MeetingsController::class, 'index'])->name('calendar');
});*/

require __DIR__.'/auth.php';
