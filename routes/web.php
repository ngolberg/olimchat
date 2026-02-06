<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\MeetingsController;
use App\Http\Controllers\BotLoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('index');
});

// Bot login endpoint: /botlogin?auth=TOKEN&to=PATH
Route::get('/botlogin', [BotLoginController::class, 'login'])->name('botlogin');

Route::get('/403', function () {
    return response()->view('errors.403', [], 403);
})->name('forbidden');

Route::middleware('auth')->group(function() {
    Route::get('/calendar', [MeetingsController::class, 'index'])->name('calendar');
});

/*Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});*/

require __DIR__.'/auth.php';
