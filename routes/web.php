<?php


use App\Http\Controllers\BotLoginController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\AppConfigController;
use App\Http\Controllers\Api\LanguageController;
use App\Http\Controllers\Api\PhotoController;
use Illuminate\Support\Facades\Route;

Route::prefix('api')->group(function () {
    Route::get('/config', [AppConfigController::class, 'index']);
    Route::middleware('auth')->post('/user/language', [LanguageController::class, 'update']);
    Route::middleware('auth')->get('/meetings', [MeetingController::class, 'index']);
    Route::middleware('auth')->get('/my-meetings', [MeetingController::class, 'myMeetings']);
    Route::middleware('auth')->post('/meeting/create', [MeetingController::class, 'store']);
    Route::middleware('auth')->post('/meeting/accept', [MeetingController::class, 'accept']);
    Route::middleware('auth')->post('/meeting/decline', [MeetingController::class, 'decline']);
    Route::middleware('auth')->post('/meeting/cancel', [MeetingController::class, 'cancel']);
    Route::middleware('auth')->post('/user/photo', [PhotoController::class, 'store']);
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
    Route::get('/meetings', [MeetingsController::class, 'index'])->name('meetings');
});*/

require __DIR__.'/auth.php';
