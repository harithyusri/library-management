<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanDueReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Loan $loan) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toArray(object $notifiable): array
    {
        $book = $this->loan->bookCopy->book;
        $days = now()->diffInDays($this->loan->due_date, false);

        return [
            'type'    => 'loan_due',
            'message' => $days <= 0
                ? "\"" . $book->title . "\" is due today!"
                : "\"" . $book->title . "\" is due in {$days} day" . ($days > 1 ? 's' : '') . '.',
            'loan_id' => $this->loan->id,
            'url'     => '/member/loans',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book    = $this->loan->bookCopy->book;
        $dueDate = $this->loan->due_date->toFormattedDateString();
        $days    = now()->diffInDays($this->loan->due_date, false);

        $urgency = $days <= 0 ? 'is due TODAY' : "is due in {$days} day" . ($days > 1 ? 's' : '');

        return (new MailMessage)
            ->subject("Reminder: \"{$book->title}\" {$urgency}")
            ->greeting("Hello {$notifiable->name},")
            ->line("This is a reminder that your borrowed book is due soon.")
            ->line("**{$book->title}** by {$book->author_name}")
            ->line("Due Date: **{$dueDate}**")
            ->action('View My Loans', url('/member/loans'))
            ->line('You can renew your loan from the My Loans page if you need more time.');
    }
}
