<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\FollowerController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\PostReactionController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [WelcomeController::class, 'index'])
    ->middleware('auth', 'verified')->name('home');

Route::get('/welcome', [WelcomeController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('post.index');

Route::get('/api/posts', [WelcomeController::class, 'getPosts'])
    ->middleware('auth', 'verified')->name('posts.api');

Route::get('/api/users/search', [ProfileController::class, 'search'])
    ->middleware('auth', 'verified')->name('users.search');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Redirect /profile to current user's profile
Route::get('/profile', function () {
    $user = auth()->user();

    if (!$user) {
        return redirect()->route('login');
    }

    // If username is missing, trigger Spatie Sluggable to regenerate it
    if (empty($user->username)) {
        $user->save(); // This uses the existing slug generation in User model
    }

    return redirect("/profile/{$user->username}");
})->middleware(['auth', 'verified'])->name('profile');// Public Profile View Route (Facebook-like)
Route::get('/profile/{username}', [ProfileController::class, 'show'])
    ->middleware(['auth', 'verified'])->name('profile.show');

// Photo Gallery Routes (Profile-based)
Route::middleware(['auth', 'verified'])->group(function () {
    // Album List (Gallery Home)
    Route::get('/profile/{username}/gallery', [AlbumController::class, 'index'])
        ->name('gallery.index');

    // Album CRUD
    Route::post('/gallery/albums', [AlbumController::class, 'store'])
        ->name('gallery.albums.store');

    Route::get('/profile/{username}/gallery/{album}', [AlbumController::class, 'show'])
        ->name('gallery.albums.show');

    Route::put('/profile/{username}/gallery/{album}', [AlbumController::class, 'update'])
        ->name('gallery.albums.update');

    Route::delete('/profile/{username}/gallery/{album}', [AlbumController::class, 'destroy'])
        ->name('gallery.albums.destroy');

    // Photo Management
    Route::post('/gallery/albums/{album}/photos', [PhotoController::class, 'store'])
        ->name('gallery.photos.store');

    Route::get('/profile/{username}/gallery/{album}/{photo}', [PhotoController::class, 'show'])
        ->name('gallery.photos.show');

    Route::put('/gallery/photos/{photo}', [PhotoController::class, 'update'])
        ->name('gallery.photos.update');

    Route::delete('/gallery/photos/{photo}', [PhotoController::class, 'destroy'])
        ->name('gallery.photos.destroy');

    // Photo Actions
    Route::get('/gallery/photos/{photo}/download', [PhotoController::class, 'download'])
        ->name('gallery.photos.download');

    Route::post('/gallery/photos/{photo}/view', [PhotoController::class, 'incrementView'])
        ->name('gallery.photos.view');
});

Route::post('/post', [PostController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('post.create');

Route::post('/api/post/suggest-content', [PostController::class, 'suggestContent'])
    ->middleware(['auth', 'verified'])->name('post.suggest-content');

Route::put('/post/{post}', [PostController::class, 'update'])
    ->middleware(['auth', 'verified'])->name('post.update');

Route::delete('/post/{post}', [PostController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('post.destroy');

Route::get('/post/attachment/{attachment}/download', [PostController::class, 'downloadAttachment'])
    ->middleware(['auth', 'verified'])->name('post.attachment.download');

// Reactions (Polymorphic - Posts and Comments)
Route::post('/{type}/{id}/reaction', [ReactionController::class, 'toggle'])
    ->middleware(['auth', 'verified'])
    ->where(['type' => 'post|comment', 'id' => '[0-9]+'])
    ->name('reaction.toggle');

// Comments
Route::post('/post/{post}/comment', [CommentController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('post.comment.store');

Route::get('/post/{post}/comments', [CommentController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('post.comment.index');

Route::get('/comment/{comment}/replies', [CommentController::class, 'replies'])
    ->middleware(['auth', 'verified'])->name('comment.replies');

Route::put('/comment/{comment}', [CommentController::class, 'update'])
    ->middleware(['auth', 'verified'])->name('comment.update');

Route::delete('/comment/{comment}', [CommentController::class, 'destroy'])
    ->middleware(['auth', 'verified'])->name('comment.destroy');

// Notifications
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::delete('/notifications/delete-read', [NotificationController::class, 'deleteRead'])->name('notifications.deleteRead');
});

// Followers
Route::post('/users/{user}/follow', [FollowerController::class, 'toggle'])
    ->middleware(['auth', 'verified'])->name('users.follow.toggle');

Route::get('/users/{user}/followers', [FollowerController::class, 'followers'])
    ->middleware(['auth', 'verified'])->name('users.followers');

Route::get('/users/{user}/following', [FollowerController::class, 'following'])
    ->middleware(['auth', 'verified'])->name('users.following');

// Groups
Route::middleware(['auth', 'verified'])->group(function () {
    // Group Discovery & Listing
    Route::get('/groups', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/groups/create', [GroupController::class, 'create'])->name('groups.create');
    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');

    // Group Invitations (must be before /groups/{group} to avoid route collision)
    Route::get('/groups/invitations', [GroupController::class, 'invitations'])->name('groups.invitations');
    Route::get('/groups/invitations/{token}/accept', [GroupController::class, 'acceptInvitationFromEmail'])->name('groups.invitations.accept');
    Route::post('/groups/invitations/{token}/respond', [GroupController::class, 'respondToInvitation'])->name('groups.invitations.respond');

    // Group Profile & Details
    Route::get('/groups/{group}', [GroupController::class, 'show'])->name('groups.show');
    Route::get('/groups/{group}/edit', [GroupController::class, 'edit'])->name('groups.edit');
    Route::put('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::delete('/groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');

    // Group Images
    Route::post('/groups/{group}/images', [GroupController::class, 'updateImages'])->name('groups.updateImages');

    // Group Members
    Route::get('/groups/{group}/members', [GroupController::class, 'members'])->name('groups.members');
    Route::post('/groups/{group}/invite', [GroupController::class, 'inviteMember'])->name('groups.invite');
    Route::post('/groups/{group}/members/{user}/role', [GroupController::class, 'updateMemberRole'])->name('groups.members.updateRole');
    Route::delete('/groups/{group}/members/{user}', [GroupController::class, 'removeMember'])->name('groups.members.remove');

    // Join/Leave Group
    Route::post('/groups/{group}/join', [GroupController::class, 'join'])->name('groups.join');
    Route::post('/groups/{group}/leave', [GroupController::class, 'leave'])->name('groups.leave');

    // Admin: Pending Join Requests
    Route::get('/groups/{group}/admin/requests', [GroupController::class, 'pendingRequests'])->name('groups.admin.requests');
    Route::post('/groups/{group}/admin/approve', [GroupController::class, 'approveRequest'])->name('groups.admin.approve');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
