<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FineDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paidAmount = (float) ($this->fine_paid_amount ?? 0);

        return [
            'id'                => $this->id,
            'book' => [
                'title'     => $this->bookCopy->book->title,
                'cover'     => $this->bookCopy->book->cover_image_path ?? null,
                'publisher' => $this->bookCopy->book->publisher->name,
            ],
            'borrowed_date'     => $this->borrowed_date->format('d M Y'),
            'due_date'          => $this->due_date->format('d M Y'),
            'returned_date'     => $this->returned_date?->format('d M Y'),
            'fine_amount'       => (float) $this->fine_amount,
            'fine_paid'         => (bool) $this->fine_paid,
            'fine_paid_amount'  => $paidAmount,
            'remaining_amount'  => (float) ($this->fine_amount - $paidAmount),
            'status'            => $this->fineStatus(),
            'payments'          => $this->whenLoaded('payments', fn () =>
                $this->payments->map(fn ($p) => [
                    'id'        => $p->id,
                    'amount'    => (float) $p->amount,
                    'method'    => $p->payment_method,
                    'date'      => $p->paid_at->format('d M Y, h:i A'),
                    'stripe_id' => $p->stripe_payment_intent_id ?? '',
                ])
            ),
        ];
    }

    private function fineStatus(): string
    {
        if ($this->fine_paid) return 'settled';
        if (($this->fine_paid_amount ?? 0) > 0) return 'partial';
        return 'unpaid';
    }
}
