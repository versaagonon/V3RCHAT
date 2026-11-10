<?php

use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', LandingPageController::class)->name('home');

Route::get('/chatify', function () {
    return redirect('/v3rchat');
});

Route::get('/dashboard', function () {
    return redirect('/v3rchat');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

if (app()->environment('local')) {
    Route::get('/node_modules/{any}', function (string $any) {
        return redirect("http://127.0.0.1:5173/node_modules/{$any}");
    })->where('any', '.*');

    Route::get('/@vite/{any}', function (string $any) {
        return redirect("http://127.0.0.1:5173/@vite/{$any}");
    })->where('any', '.*');

    Route::get('/src/{any}', function (string $any) {
        return redirect("http://127.0.0.1:5173/src/{$any}");
    })->where('any', '.*');
}
