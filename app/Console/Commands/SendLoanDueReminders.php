<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Notifications\LoanDueReminder;
use Illuminate\Console\Command;

class SendLoanDueReminders extends Command
{
    protected $signature   = 'loans:send-due-reminders';
    protected $description = 'Send due date reminder emails to members with loans due in 3 days or 1 day';

    public function handle(): void
    {
        $dueDays = [3, 1];

        foreach ($dueDays as $days) {
            $loans = Loan::with(['bookCopy.book', 'user'])
                ->whereNull('returned_date')
                ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
                ->whereDate('due_date', now()->addDays($days)->toDateString())
                ->get();

            foreach ($loans as $loan) {
                $loan->user->notify(new LoanDueReminder($loan));
            }

            $this->info("Sent reminders for {$loans->count()} loans due in {$days} day(s).");
        }
    }
}
