<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\IntakeController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/starthere', function () {
    return Inertia::render('IntakeForm'); 
})->name('starthere');

Route::post('/intake/store', [IntakeController::class, 'store'])->name('intake.store');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
