<?php

namespace App\Notifications;

use App\Models\GroupInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GroupInvitationNotification extends Notification // Removed ShouldQueue to send immediately
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public GroupInvitation $invitation
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
        $groupName = $this->invitation->group->name;
        $inviterName = $this->invitation->inviter->name;
        $acceptUrl = url('/groups/invitations/' . $this->invitation->token . '/accept');

        return (new MailMessage)
            ->subject("You've been invited to join {$groupName}")
            ->greeting("Hello {$notifiable->name}!")
            ->line("{$inviterName} has invited you to join the group \"{$groupName}\".")
            ->line($this->invitation->group->about ?? 'Join this group to connect with other members.')
            ->action('Accept & Join Group', $acceptUrl)
            ->line('Clicking the button above will instantly add you to the group.')
            ->line('This invitation will expire on ' . $this->invitation->token_expires_at->format('F j, Y'))
            ->line('If you did not expect this invitation, you can safely ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'group_invitation',
            'invitation_id' => $this->invitation->id,
            'group_id' => $this->invitation->group_id,
            'group_name' => $this->invitation->group->name,
            'group_slug' => $this->invitation->group->slug,
            'inviter_id' => $this->invitation->invited_by,
            'inviter_name' => $this->invitation->inviter->name,
            'token' => $this->invitation->token,
            'expires_at' => $this->invitation->token_expires_at->toISOString(),
            'action_url' => '/groups/invitations', // Relative path for Inertia.js router
            'message' => "{$this->invitation->inviter->name} invited you to join {$this->invitation->group->name}",
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
