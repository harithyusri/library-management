<?php

namespace App\Jobs;

use App\Models\Report;
use App\Models\Loan;
use App\Models\RoomBooking;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Exception;

class GenerateReportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Report $report)
    {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $this->report->update(['status' => Report::STATUS_PROCESSING]);

            $filename = $this->report->type . '_' . now()->format('Ymd_His') . '_' . $this->report->id . '.csv';
            $filepath = 'reports/' . $filename;
            
            if (!Storage::disk('public')->exists('reports')) {
                Storage::disk('public')->makeDirectory('reports');
            }

            // Ensure we use the full path for fopen
            $fullPath = storage_path('app/public/' . $filepath);
            $handle = fopen($fullPath, 'w');

            if ($this->report->type === Report::TYPE_LOAN) {
                $this->generateLoanReport($handle);
            } elseif ($this->report->type === Report::TYPE_ROOM_RESERVATION) {
                $this->generateRoomReservationReport($handle);
            }

            fclose($handle);

            $this->report->update([
                'status' => Report::STATUS_COMPLETED,
                'file_path' => $filepath,
                'file_name' => $filename,
            ]);
        } catch (Exception $e) {
            $this->report->update([
                'status' => Report::STATUS_FAILED,
                'error_message' => $e->getMessage(),
            ]);
        }
    }

    private function generateLoanReport($handle): void
    {
        // Header
        fputcsv($handle, [
            'ID', 
            'Book Title', 
            'Borrower', 
            'Borrowed Date', 
            'Due Date', 
            'Returned Date', 
            'Status', 
            'Fine Amount'
        ]);

        $query = Loan::with(['bookCopy.book', 'user']);

        if (!empty($this->report->filters['start_date'])) {
            $query->where('borrowed_date', '>=', $this->report->filters['start_date']);
        }

        if (!empty($this->report->filters['end_date'])) {
            $query->where('borrowed_date', '<=', $this->report->filters['end_date']);
        }

        $query->chunk(100, function ($loans) use ($handle) {
            foreach ($loans as $loan) {
                fputcsv($handle, [
                    $loan->id,
                    $loan->bookCopy->book->title ?? 'N/A',
                    $loan->user->name ?? 'N/A',
                    $loan->borrowed_date ? $loan->borrowed_date->format('Y-m-d') : 'N/A',
                    $loan->due_date ? $loan->due_date->format('Y-m-d') : 'N/A',
                    $loan->returned_date ? $loan->returned_date->format('Y-m-d') : 'Not Returned',
                    $loan->status,
                    $loan->fine_amount ?? 0,
                ]);
            }
        });
    }

    private function generateRoomReservationReport($handle): void
    {
        // Header
        fputcsv($handle, ['ID', 'Room Name', 'User', 'Booking Date', 'Start Time', 'End Time', 'Status', 'Purpose']);

        $query = RoomBooking::with(['room', 'user']);

        if (!empty($this->report->filters['start_date'])) {
            $query->where('booking_date', '>=', $this->report->filters['start_date']);
        }

        if (!empty($this->report->filters['end_date'])) {
            $query->where('booking_date', '<=', $this->report->filters['end_date']);
        }

        $query->chunk(100, function ($bookings) use ($handle) {
            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->id,
                    $booking->room->name ?? 'N/A',
                    $booking->user->name ?? 'N/A',
                    $booking->booking_date ? $booking->booking_date->format('Y-m-d') : 'N/A',
                    $booking->start_time,
                    $booking->end_time,
                    $booking->status,
                    $booking->purpose,
                ]);
            }
        });
    }
}
