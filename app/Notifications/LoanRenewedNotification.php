<?php

namespace App\Notifications;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LoanRenewedNotification extends Notification implements ShouldQueue
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

        return [
            'type'    => 'loan_renewed',
            'message' => "\"" . $book->title . "\" renewed. New due date: " . $this->loan->due_date->toFormattedDateString() . '.',
            'loan_id' => $this->loan->id,
            'url'     => '/member/loans',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $book       = $this->loan->bookCopy->book;
        $newDueDate = $this->loan->due_date->toFormattedDateString();
        $remaining  = config('library.max_renewals', 2) - $this->loan->renewals_count;

        return (new MailMessage)
            ->subject("Loan renewed: \"{$book->title}\"")
            ->greeting("Hello {$notifiable->name},")
            ->line("Your loan has been successfully renewed.")
            ->line("**{$book->title}** by {$book->author_name}")
            ->line("New Due Date: **{$newDueDate}**")
            ->line("Renewals remaining: **{$remaining}**")
            ->action('View My Loans', url('/member/loans'));
    }
}
