<?php

namespace App\Notifications;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public Order $order) {}

    /**
     * Payment results are important, so they are always emailed.
     *
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $message = (new MailMessage)->greeting('Halo, '.$notifiable->publicName());

        if ($this->order->status === OrderStatus::Paid) {
            return $message
                ->subject('Pembayaran diterima · '.$this->order->code)
                ->line('Pembayaran '.Money::rupiah($this->order->total).' untuk '.$this->order->plan->name.' sudah kami terima.')
                ->line('Langganan aktif sampai '.$this->order->subscription?->ends_at->translatedFormat('d F Y').'.')
                ->action('Mulai belajar', route('dashboard'));
        }

        return $message
            ->subject('Pembayaran ditolak · '.$this->order->code)
            ->line('Pembayaran untuk pesanan '.$this->order->code.' belum bisa kami verifikasi.')
            ->line('Alasan: '.($this->order->admin_note ?: '-'))
            ->action('Lihat pesanan', route('orders.show', $this->order));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->order->status === OrderStatus::Paid
                ? 'Pembayaran '.$this->order->code.' diterima. Langganan aktif!'
                : 'Pembayaran '.$this->order->code.' ditolak.',
            'excerpt' => $this->order->status === OrderStatus::Paid ? $this->order->plan->name : $this->order->admin_note,
            'url' => route('orders.show', $this->order),
        ];
    }
}
