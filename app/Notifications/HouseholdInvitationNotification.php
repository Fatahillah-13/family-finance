<?php

namespace App\Notifications;

use App\Models\HouseholdInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HouseholdInvitationNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public HouseholdInvitation $invitation)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invitations.show', ['token' => $this->invitation->token]);

        return (new MailMessage)
            ->subject('Invitation to join household')
            ->line("You have been invited to join a household.")
            ->line("Household: " . ($this->invitation->household->name ?? ('#' . $this->invitation->household_id)))
            ->line("Role: " . ($this->invitation->role->name ?? 'Member'))
            ->action('Review Invitation', $url)
            ->line('This invitation expires in 2 days.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
