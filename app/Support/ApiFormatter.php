<?php

namespace App\Support;

use App\Models\Announcement;
use App\Models\Book;
use App\Models\Loan;
use App\Models\MaintenanceReport;
use App\Models\Room;
use App\Models\RoomBooking;

class ApiFormatter
{
    public static function mediaUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        // Already a full URL (e.g. external image)
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        // Strip leading /storage/ or storage/ prefix to avoid double-prefixing
        $path = ltrim($path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return asset('storage/'.$path);
    }

    public static function book(Book $book, bool $detailed = false): array
    {
        $data = [
            'id' => $book->id,
            'title' => $book->title,
            'author_name' => $book->author_name,
            'isbn' => $book->isbn,
            'format' => $book->format,
            'language' => $book->language,
            'published_year' => $book->published_year,
            'cover_image' => $book->cover_image_url,
            'total_copies' => $book->total_copies ?? $book->copies()->count(),
            'available_copies' => $book->available_copies ?? $book->copies()->where('status', 'available')->count(),
        ];

        if ($detailed) {
            $book->loadMissing(['genres', 'category', 'publisher', 'copies.library']);

            $data = array_merge($data, [
                'pages' => $book->pages,
                'description' => $book->description,
                'genres' => $book->genres->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])->values(),
                'category' => $book->category ? ['id' => $book->category->id, 'name' => $book->category->name] : null,
                'publisher' => $book->publisher ? ['id' => $book->publisher->id, 'name' => $book->publisher->name] : null,
                'copies' => $book->copies->map(fn ($c) => [
                    'id' => $c->id,
                    'barcode' => $c->barcode,
                    'status' => $c->status,
                    'library' => $c->library ? ['id' => $c->library->id, 'name' => $c->library->name] : null,
                ])->values(),
            ]);
        }

        return $data;
    }

    public static function loan(Loan $loan): array
    {
        $loan->loadMissing(['bookCopy.book']);

        $status = $loan->status;
        if (! $loan->returned_date && $loan->due_date < now()) {
            $status = Loan::STATUS_OVERDUE;
        } elseif ($loan->returned_date) {
            $status = Loan::STATUS_RETURNED;
        } elseif (! $loan->returned_date) {
            $status = Loan::STATUS_ACTIVE;
        }

        return [
            'id' => $loan->id,
            'book_title' => $loan->bookCopy?->book?->title ?? 'Unknown Book',
            'cover_image' => self::mediaUrl($loan->bookCopy?->book?->cover_image_url),
            'borrowed_date' => $loan->borrowed_date?->toDateString(),
            'due_date' => $loan->due_date?->toDateString(),
            'returned_date' => $loan->returned_date?->toDateString(),
            'status' => $status,
            'fine_amount' => (float) $loan->fine_amount,
            'fine_paid' => (bool) $loan->fine_paid,
            'fine_paid_amount' => (float) ($loan->fine_paid_amount ?? 0),
        ];
    }

    public static function fine(Loan $loan, bool $detailed = false): array
    {
        $remaining = (float) ($loan->fine_amount - ($loan->fine_paid_amount ?? 0));

        $data = [
            'id' => $loan->id,
            'book_title' => $loan->bookCopy?->book?->title ?? 'Unknown Book',
            'due_date' => $loan->due_date?->format('d M Y'),
            'fine_amount' => (float) $loan->fine_amount,
            'fine_paid' => (bool) $loan->fine_paid,
            'fine_paid_amount' => (float) ($loan->fine_paid_amount ?? 0),
            'remaining_amount' => $remaining,
            'status' => self::fineStatus($loan),
        ];

        if ($detailed) {
            $loan->loadMissing(['bookCopy.book.publisher', 'payments']);

            $data = array_merge($data, [
                'book' => [
                    'title' => $loan->bookCopy?->book?->title,
                    'cover' => self::mediaUrl($loan->bookCopy?->book?->cover_image_url),
                    'publisher' => $loan->bookCopy?->book?->publisher?->name,
                ],
                'borrowed_date' => $loan->borrowed_date?->format('d M Y'),
                'returned_date' => $loan->returned_date?->format('d M Y'),
                'payments' => $loan->payments
                    ->where('status', 'paid')
                    ->map(fn ($p) => [
                        'id' => $p->id,
                        'amount' => (float) $p->amount,
                        'method' => $p->payment_method,
                        'date' => $p->paid_at?->format('d M Y, h:i A'),
                    ])->values(),
            ]);
        }

        return $data;
    }

    public static function fineStatus(Loan $loan): string
    {
        if ($loan->fine_paid) {
            return 'settled';
        }

        if (($loan->fine_paid_amount ?? 0) > 0) {
            return 'partial';
        }

        return 'unpaid';
    }

    public static function roomBooking(RoomBooking $booking): array
    {
        $booking->loadMissing(['room.library']);

        return [
            'id' => $booking->id,
            'room' => self::room($booking->room),
            'booking_date' => $booking->booking_date->toDateString(),
            'start_time' => substr((string) $booking->start_time, 0, 5),
            'end_time' => substr((string) $booking->end_time, 0, 5),
            'status' => $booking->status,
            'purpose' => $booking->purpose,
            'duration_hours' => $booking->duration_in_hours,
            'total_cost' => (float) ($booking->total_cost ?? 0),
        ];
    }

    public static function room(Room $room): array
    {
        $room->loadMissing('library');

        return [
            'id' => $room->id,
            'name' => $room->name,
            'room_number' => $room->room_number,
            'type' => $room->type,
            'type_display' => $room->type_display,
            'capacity' => $room->capacity,
            'floor' => $room->floor,
            'hourly_rate' => (float) $room->hourly_rate,
            'status' => $room->status,
            'description' => $room->description,
            'image_url' => $room->image_url,
            'library' => $room->library ? ['id' => $room->library->id, 'name' => $room->library->name] : null,
        ];
    }

    public static function announcement(Announcement $announcement, bool $detailed = false): array
    {
        $announcement->loadMissing('creator:id,name');

        $data = [
            'id' => $announcement->id,
            'title' => $announcement->title,
            'image_path' => self::mediaUrl($announcement->image_path),
            'is_active' => $announcement->is_active,
            'expires_at' => $announcement->expires_at?->toIso8601String(),
            'created_at' => $announcement->created_at?->toIso8601String(),
            'creator' => $announcement->creator ? ['id' => $announcement->creator->id, 'name' => $announcement->creator->name] : null,
        ];

        if ($detailed) {
            $data['content'] = $announcement->content;
        }

        return $data;
    }

    public static function maintenanceReport(MaintenanceReport $report): array
    {
        return [
            'id' => $report->id,
            'title' => $report->title,
            'category' => $report->category,
            'description' => $report->description,
            'status' => $report->status,
            'priority' => $report->priority,
            'image_path' => self::mediaUrl($report->image_path),
            'admin_notes' => $report->admin_notes,
            'library_id' => $report->library_id,
            'created_at' => $report->created_at?->toIso8601String(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function maintenanceStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'assigned' => 'Assigned',
            'in_progress' => 'In Progress',
            'resolved' => 'Resolved',
            'rejected' => 'Rejected',
        ];
    }

    /**
     * @return list<string>
     */
    public static function maintenanceCategories(): array
    {
        return [
            MaintenanceReport::CATEGORY_BUILDING,
            MaintenanceReport::CATEGORY_FURNITURE,
            MaintenanceReport::CATEGORY_BOOKS,
            MaintenanceReport::CATEGORY_ELECTRONICS,
            MaintenanceReport::CATEGORY_OTHERS,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function maintenancePriorities(): array
    {
        return [
            MaintenanceReport::PRIORITY_LOW => 'Low',
            MaintenanceReport::PRIORITY_MEDIUM => 'Medium',
            MaintenanceReport::PRIORITY_HIGH => 'High',
        ];
    }
}
