<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserFollowedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $follower
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $followerName = $this->follower->name;
        $profileUrl = url('/profile/' . $this->follower->username);

        return (new MailMessage)
            ->subject("{$followerName} started following you")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$followerName} is now following you on Pass The Ball.")
            ->action('View Profile', $profileUrl)
            ->line('Stay connected and share your moments!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'user_followed',
            'follower_id' => $this->follower->id,
            'follower_name' => $this->follower->name,
            'follower_username' => $this->follower->username,
            'follower_profile_picture_url' => $this->follower->profile_picture_path,
            'action_url' => '/profile/' . $this->follower->username,
            'message' => "{$this->follower->name} started following you",
        ];
    }

    /**
     * Get the database representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
