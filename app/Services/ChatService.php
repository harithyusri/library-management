<?php

namespace App\Services;

use App\Models\ChatSession;
use App\Models\Loan;
use App\Models\User;
use Generator;
use Illuminate\Support\Facades\Http;

class ChatService
{
    private const MAX_HISTORY = 20;

    public function createSession(User $user): ChatSession
    {
        return ChatSession::create(['user_id' => $user->id]);
    }

    public function stream(ChatSession $session, string $userMessage): Generator
    {
        // Auto-set title from first user message
        if ($session->messages()->doesntExist() && ! $session->title) {
            $session->update(['title' => mb_substr($userMessage, 0, 60)]);
        }

        $session->messages()->create(['role' => 'user', 'content' => $userMessage]);

        $history  = $session->messages()->latest()->take(self::MAX_HISTORY)->get()->reverse()->values();
        $contents = $history->map(fn ($m) => [
            'role'    => $m->role === 'assistant' ? 'assistant' : 'user',
            'content' => $m->content,
        ])->values()->toArray();

        $systemMsg = ['role' => 'system', 'content' => $this->buildSystemPrompt($session->user)];
        array_unshift($contents, $systemMsg);

        $payload = [
            'model'       => config('services.groq.model'),
            'messages'    => $contents,
            'temperature' => 0.7,
            'max_tokens'  => 1024,
            'stream'      => true,
        ];

        $fullReply = '';

        $response = Http::withOptions(['stream' => true])
            ->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . config('services.groq.api_key'),
            ])
            ->post(config('services.groq.api_url'), $payload);

        $body = $response->getBody();

        while (! $body->eof()) {
            $line = $this->readLine($body);

            if (! str_starts_with($line, 'data: ')) continue;

            $json = substr($line, 6);
            if ($json === '[DONE]') break;

            $data  = json_decode($json, true);
            $chunk = $data['choices'][0]['delta']['content'] ?? '';

            if ($chunk !== '') {
                $fullReply .= $chunk;
                yield $chunk;
            }
        }

        if ($fullReply !== '') {
            $session->messages()->create(['role' => 'assistant', 'content' => $fullReply]);
        }
    }

    private function readLine($body): string
    {
        $line = '';
        while (! $body->eof()) {
            $char = $body->read(1);
            if ($char === "\n") break;
            $line .= $char;
        }
        return rtrim($line, "\r");
    }

    private function buildSystemPrompt(User $user): string
    {
        $user->loadMissing('member');
        $member = $user->member;

        $activeLoans = $user->loans()
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->with('bookCopy.book')
            ->latest()->take(10)->get();

        $unpaidFines = $user->loans()
            ->where('fine_paid', false)->where('fine_amount', '>', 0)
            ->with('bookCopy.book')
            ->latest()->take(10)->get();

        $upcomingBookings = $user->roomBookings()
            ->where('booking_date', '>=', now()->toDateString())
            ->whereIn('status', ['pending', 'confirmed'])
            ->with('room')->orderBy('booking_date')->take(5)->get();

        $totalReturned = $user->loans()->where('status', Loan::STATUS_RETURNED)->count();
        $borrowLimit   = $activeLoans->first()?->bookCopy?->library?->getBorrowLimit() ?? \App\Models\Library::DEFAULT_BORROW_LIMIT;
        $activeCount   = $activeLoans->count();

        $ctx  = "MEMBER CONTEXT:\n";
        $ctx .= "Name: {$user->name}\n";
        $ctx .= "Email: {$user->email}\n";

        if ($member) {
            $ctx .= "Card Number: {$member->library_card_number}\n";
            $ctx .= "Membership Type: {$member->membership_type}\n";
            $ctx .= "Membership Expires: {$member->membership_expiry_date?->format('d M Y')}\n";
        }

        $ctx .= "Borrow Limit: {$activeCount}/{$borrowLimit} books currently borrowed\n\n";

        if ($activeLoans->isNotEmpty()) {
            $ctx .= "ACTIVE/OVERDUE LOANS:\n";
            foreach ($activeLoans as $loan) {
                $title  = $loan->bookCopy?->book?->title ?? 'Unknown';
                $due    = $loan->due_date?->format('d M Y') ?? 'N/A';
                $ctx   .= "- \"{$title}\" | Due: {$due} | Status: {$loan->status}\n";
            }
            $ctx .= "\n";
        } else {
            $ctx .= "ACTIVE LOANS: None\n\n";
        }

        if ($unpaidFines->isNotEmpty()) {
            $totalFine = $unpaidFines->sum(fn ($l) => $l->fine_amount - $l->fine_paid_amount);
            $ctx      .= "UNPAID FINES (Total: RM " . number_format($totalFine, 2) . "):\n";
            foreach ($unpaidFines as $loan) {
                $title     = $loan->bookCopy?->book?->title ?? 'Unknown';
                $remaining = number_format($loan->fine_amount - $loan->fine_paid_amount, 2);
                $ctx      .= "- \"{$title}\" | Remaining: RM {$remaining}\n";
            }
            $ctx .= "\n";
        } else {
            $ctx .= "UNPAID FINES: None\n\n";
        }

        if ($upcomingBookings->isNotEmpty()) {
            $ctx .= "UPCOMING ROOM BOOKINGS:\n";
            foreach ($upcomingBookings as $booking) {
                $room  = $booking->room?->name ?? 'Unknown Room';
                $date  = $booking->booking_date?->format('d M Y') ?? 'N/A';
                $start = substr($booking->start_time, 0, 5);
                $end   = substr($booking->end_time, 0, 5);
                $ctx  .= "- {$room} | {$date} {$start}–{$end} | Status: {$booking->status}\n";
            }
            $ctx .= "\n";
        } else {
            $ctx .= "UPCOMING ROOM BOOKINGS: None\n\n";
        }

        $ctx .= "LOAN HISTORY: {$totalReturned} books returned in total\n";

        return <<<PROMPT
You are Athena, a friendly and helpful AI assistant for the Athenaeum Library Management System.

{$ctx}

INSTRUCTIONS:
- You ONLY answer questions related to this library system: books, loans, fines, room bookings, membership, library policies, and the member's own data shown above.
- If asked about anything unrelated to the library, politely decline and redirect to library topics.
- Always use the member's actual data above when answering personal questions (e.g. "what are my loans?").
- Fine rate is RM1.00 per day overdue.
- Default loan period is 14 days.
- Auto-detect the language the member writes in and reply in the SAME language (Bahasa Malaysia or English).
- Be concise, warm, and helpful. Use bullet points for lists.
- Never make up data. If you don't know something, say so honestly.
PROMPT;
    }
}
