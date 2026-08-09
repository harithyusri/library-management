<?php

namespace App\Notifications;

use App\Models\Reservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReservationReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Reservation $reservation) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $book = $this->reservation->book;

        return [
            'type'           => 'reservation_ready',
            'message'        => "\"" . $book->title . "\" is ready for collection!",
            'reservation_id' => $this->reservation->id,
            'url'            => '/member/reservations',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book    = $this->reservation->book;
        $expiry  = $this->reservation->expiry_date->toFormattedDateString();

        return (new MailMessage)
            ->subject("Your reserved book \"{$book->title}\" is ready!")
            ->greeting("Great news, {$notifiable->name}!")
            ->line("The book you reserved is now available for collection.")
            ->line("**{$book->title}** by {$book->author_name}")
            ->line("Please collect it before **{$expiry}** or your reservation will expire.")
            ->action('View My Reservations', url('/member/reservations'))
            ->line('Thank you for using our library!');
    }
}
