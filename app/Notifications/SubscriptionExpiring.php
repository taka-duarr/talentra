<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiring extends Notification
{
    use Queueable;

    public function __construct(public Subscription $subscription, public int $daysLeft) {}

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return $notifiable->email_notifications ? ['database', 'mail'] : ['database'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Langganan '.$this->subscription->plan->name.' berakhir '.$this->whenLabel())
            ->greeting('Halo, '.$notifiable->publicName())
            ->line('Langganan '.$this->subscription->plan->name.' kamu berakhir pada '.$this->subscription->ends_at->translatedFormat('d F Y').'.')
            ->line('Perpanjang sekarang agar latihan dan analisis hasilmu tidak terputus.')
            ->action('Perpanjang langganan', route('plans.checkout', $this->subscription->plan));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => 'Langganan '.$this->subscription->plan->name.' berakhir '.$this->whenLabel(),
            'excerpt' => 'Perpanjang agar akses materi dan try out tidak terputus.',
            'url' => route('plans.checkout', $this->subscription->plan),
        ];
    }

    private function whenLabel(): string
    {
        return $this->daysLeft <= 1 ? 'besok' : "dalam {$this->daysLeft} hari";
    }
}
