<?php

namespace App\Notifications;

use App\Models\Group;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupMemberRemovedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Group $group,
        public User $removedBy
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
        $removedByName = $this->removedBy->name;

        return (new MailMessage)
            ->subject("Removed from group - {$groupName}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been removed from the group \"{$groupName}\" by {$removedByName}.")
            ->action('View Details', url('/notifications'))
            ->line('You no longer have access to this group\'s content and activities.')
            ->line('If you believe this was done in error, please contact the group administrator.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_member_removed',
            'group_id' => $this->group->id,
            'group_name' => $this->group->name,
            'group_slug' => $this->group->slug,
            'removed_by_id' => $this->removedBy->id,
            'removed_by_name' => $this->removedBy->name,
            'message' => "You have been removed from {$this->group->name} by {$this->removedBy->name}",
            'description' => "You no longer have access to this group's content and activities. If you believe this was done in error, please contact the group administrator.",
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
