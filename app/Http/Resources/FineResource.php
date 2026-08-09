<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FineResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $paidAmount = (float) ($this->fine_paid_amount ?? 0);

        return [
            'id'                => $this->id,
            'book_title'        => $this->bookCopy->book->title,
            'due_date'          => $this->due_date->format('d M Y'),
            'fine_amount'       => (float) $this->fine_amount,
            'fine_paid'         => (bool) $this->fine_paid,
            'fine_paid_amount'  => $paidAmount,
            'remaining_amount'  => (float) ($this->fine_amount - $paidAmount),
            'status'            => $this->fineStatus(),
        ];
    }

    private function fineStatus(): string
    {
        if ($this->fine_paid) return 'settled';
        if (($this->fine_paid_amount ?? 0) > 0) return 'partial';
        return 'unpaid';
    }
}
