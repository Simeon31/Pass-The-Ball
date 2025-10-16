<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostReactionController;
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

// Post Reactions
Route::post('/post/{post}/reaction', [PostReactionController::class, 'toggle'])
    ->middleware(['auth', 'verified'])->name('post.reaction.toggle');

// Comments
Route::post('/post/{post}/comment', [CommentController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('post.comment.store');

Route::get('/post/{post}/comments', [CommentController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('post.comment.index');

Route::delete('/comment/{comment}', [CommentController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('comment.destroy');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
