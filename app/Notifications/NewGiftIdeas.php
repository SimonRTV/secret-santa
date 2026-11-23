<?php

namespace App\Notifications;

use App\Models\FamilyMember;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells a giver that the person they drew added gift ideas, without ever
 * naming that person: the e-mail may be read before the envelope is opened,
 * or over someone's shoulder.
 */
class NewGiftIdeas extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    /** @var list<int> */
    public array $backoff = [10, 60, 300];

    public bool $deleteWhenMissingModels = true;

    public function __construct(public string $recipientId)
    {
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * The draw may have been cancelled, or the address removed, while the e-mail was waiting.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $notifiable instanceof FamilyMember
            && $notifiable->email !== null
            && $notifiable->recipient_id === $this->recipientId;
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var FamilyMember $notifiable */
        return (new MailMessage)
            ->subject('De nouvelles idées de cadeaux vous attendent')
            ->greeting('Bonjour '.$notifiable->name.' !')
            ->line('La personne à qui vous offrez un cadeau pour « '.$notifiable->family->name.' » vient d’ajouter des idées à sa liste.')
            ->action('Voir ses idées', route('members.show', $notifiable))
            ->line('Ce lien est personnel : il révèle votre tirage, ne le transférez pas.')
            ->line('Pour ne plus recevoir ces messages, retirez votre adresse e-mail depuis votre invitation.');
    }
}
