<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\Settings\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WelcomeController::class, 'index'])
    ->middleware('auth', 'verified')->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::post('/post', [PostController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('post.create');

Route::put('/post/{post}', [PostController::class, 'update'])
    ->middleware(['auth', 'verified'])->name('post.update');

Route::delete('/post/{post}', [PostController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('post.destroy');

Route::get('/post/attachment/{attachment}/download', [PostController::class, 'downloadAttachment'])
    ->middleware(['auth', 'verified'])->name('post.attachment.download');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
