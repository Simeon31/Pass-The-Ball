<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display all notifications for the authenticated user.
     */
    public function index(Request $request): Response
    {
        $user = auth()->user();

        // Get filter from query parameter
        $filter = $request->input('filter', 'all'); // all, unread, invitations, posts, groups

        $query = $user->notifications();

        // Apply filters
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'invitations') {
            $query->where('type', 'App\\Notifications\\GroupInvitationNotification');
        } elseif ($filter === 'posts') {
            $query->whereIn('type', [
                'App\\Notifications\\PostCommentNotification',
                'App\\Notifications\\PostReactionNotification',
            ]);
        } elseif ($filter === 'groups') {
            $query->whereIn('type', [
                'App\\Notifications\\GroupInvitationNotification',
                'App\\Notifications\\GroupJoinRequestNotification',
            ]);
        }

        $notifications = $query->latest()->paginate(20);

        // Get counts for each category
        $counts = [
            'all' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'invitations' => $user->notifications()
                ->where('type', 'App\\Notifications\\GroupInvitationNotification')
                ->count(),
            'posts' => $user->notifications()
                ->whereIn('type', [
                    'App\\Notifications\\PostCommentNotification',
                    'App\\Notifications\\PostReactionNotification',
                ])
                ->count(),
            'groups' => $user->notifications()
                ->whereIn('type', [
                    'App\\Notifications\\GroupInvitationNotification',
                    'App\\Notifications\\GroupJoinRequestNotification',
                ])
                ->count(),
        ];

        return Inertia::render('Notifications', [
            'notifications' => NotificationResource::collection($notifications),
            'counts' => $counts,
            'currentFilter' => $filter,
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id): RedirectResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return back()->with('status', 'Notification marked as read.');
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(): RedirectResponse
    {
        auth()->user()->unreadNotifications->markAsRead();

        return back()->with('status', 'All notifications marked as read.');
    }

    /**
     * Delete a notification.
     */
    public function destroy(string $id): RedirectResponse
    {
        $notification = auth()->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->delete();

        return back()->with('status', 'Notification deleted.');
    }

    /**
     * Delete all read notifications.
     */
    public function deleteRead(): RedirectResponse
    {
        auth()->user()
            ->notifications()
            ->whereNotNull('read_at')
            ->delete();

        return back()->with('status', 'All read notifications deleted.');
    }
}
