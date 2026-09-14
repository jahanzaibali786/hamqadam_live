<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BadgeEarnedNotification extends Notification
{
    public function __construct(
        private readonly string $badge,
        private readonly string $message,
        private readonly string $deepLink = '/dashboard',
    ) {
    }

    public function via($notifiable): array
    {
        return array_values(array_filter([
            'database',
            $notifiable->email ? 'mail' : null,
        ]));
    }

    public function toDatabase($notifiable): array
    {
        return [
            'type' => 'badge_earned',
            'title' => $this->badge,
            'message' => $this->message,
            'deep_link' => $this->deepLink,
            'badge' => strtolower(str_replace(' ', '_', $this->badge)),
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->badge . ' earned')
            ->view('emails.index', [
                'email_body' => '<p>' . e($notifiable->first_name . ' ' . $notifiable->last_name) . ',</p>'
                    . '<p>' . e($this->message) . '</p>'
                    . '<p><a href="' . e(url($this->deepLink)) . '">View your profile</a></p>'
                    . '<p>Regards,<br>' . e(get_setting('website_name') ?: config('app.name')) . '</p>',
            ]);
    }
}
