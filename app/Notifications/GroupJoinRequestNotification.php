<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupJoinRequestNotification extends Notification // Removed ShouldQueue to send immediately
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Group $group,
        public User $requester
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
        $groupName = $this->group->name;
        $requesterName = $this->requester->name;
        $manageUrl = url('/groups/' . $this->group->slug . '/admin/requests');

        return (new MailMessage)
            ->subject("New join request for {$groupName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$requesterName} has requested to join \"{$groupName}\".")
            ->line('As an administrator of this group, you can approve or reject this request.')
            ->action('Manage Join Requests', $manageUrl)
            ->line('Review the request and take appropriate action.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_join_request',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_slug' => $this->group->slug,
            'requester_id' => $this->requester->id,
            'requester_name' => $this->requester->name,
            'requester_username' => $this->requester->username,
            'action_url' => '/groups/' . $this->group->slug . '/admin/requests', // Relative path for Inertia.js router
            'message' => "{$this->requester->name} requested to join {$this->group->name}",
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
