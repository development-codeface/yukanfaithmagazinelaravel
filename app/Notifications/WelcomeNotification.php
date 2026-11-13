<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to YuKanFaith')
            ->greeting('Welcome, '.$notifiable->name.'!')
            ->line('Thank you for joining YuKanFaith. Your account is ready.')
            ->line('Explore our magazines, choose your subscription, or browse the apparel collection.')
            ->action('Explore subscriptions', route('user.subscriptions'))
            ->line('We look forward to sharing stories of faith, leadership, and impact with you.');
    }
}
