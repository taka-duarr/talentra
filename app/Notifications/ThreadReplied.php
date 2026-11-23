<?php

namespace App\Notifications;

use App\Models\ForumReply;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class ThreadReplied extends Notification
{
    use Queueable;

    public function __construct(public ForumReply $reply) {}

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
            ->subject('Balasan baru: '.$this->reply->thread->title)
            ->greeting('Halo, '.$notifiable->publicName())
            ->line($this->reply->author->publicName().' membalas diskusi "'.$this->reply->thread->title.'":')
            ->line('"'.Str::limit($this->reply->body, 200).'"')
            ->action('Buka diskusi', route('forum.thread', $this->reply->thread))
            ->line('Kamu bisa mematikan email notifikasi di halaman profil.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->reply->author->publicName().' membalas "'.Str::limit($this->reply->thread->title, 60).'"',
            'excerpt' => Str::limit($this->reply->body, 120),
            'url' => route('forum.thread', $this->reply->thread).'#balasan-'.$this->reply->id,
        ];
    }
}
