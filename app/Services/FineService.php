<?php

namespace App\Services;

use App\Models\FinePayment;
use App\Models\Loan;
use Stripe\Checkout\Session;
use Stripe\Stripe;

class FineService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Determine the fine status string for a loan.
     */
    public function getStatus(Loan $loan): string
    {
        if ($loan->fine_paid) return 'settled';
        if (($loan->fine_paid_amount ?? 0) > 0) return 'partial';
        return 'unpaid';
    }

    /**
     * Get the remaining unpaid amount for a loan.
     */
    public function remaining(Loan $loan): float
    {
        return (float) ($loan->fine_amount - ($loan->fine_paid_amount ?? 0));
    }

    /**
     * Create a Stripe Checkout session for a fine payment.
     */
    public function createStripeSession(Loan $loan, float $amount): Session
    {
        $session = Session::create([
            'payment_method_types' => ['card', 'fpx'],
            'line_items' => [[
                'price_data' => [
                    'currency'     => 'myr',
                    'product_data' => [
                        'name'        => 'Fine Payment: ' . $loan->bookCopy->book->title,
                        'description' => 'Partial payment for loan Ref #LOAN-' . $loan->id,
                    ],
                    'unit_amount'  => (int) round($amount * 100),
                ],
                'quantity' => 1,
            ]],
            'mode'        => 'payment',
            'success_url' => route('member.fines.success', $loan->id) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'  => route('member.fines.show', $loan->id),
        ]);

        FinePayment::create([
            'loan_id'           => $loan->id,
            'user_id'           => $loan->user_id,
            'amount'            => $amount,
            'stripe_session_id' => $session->id,
            'status'            => 'pending',
            'library_id'        => $loan->library_id,
        ]);

        return $session;
    }

    /**
     * Handle a successful Stripe callback — verify, record, and update loan.
     */
    public function handleSuccess(Loan $loan, string $sessionId): ?FinePayment
    {
        $session = Session::retrieve([
            'id'     => $sessionId,
            'expand' => ['payment_intent.payment_method'],
        ]);

        $payment = FinePayment::where('stripe_session_id', $sessionId)->first();

        if (! $payment || $session->payment_status !== 'paid' || $payment->status === 'paid') {
            return null;
        }

        $methodType = $session->payment_intent->payment_method->type ?? 'card';

        $payment->update([
            'status'                    => 'paid',
            'stripe_payment_intent_id'  => $session->payment_intent->id,
            'payment_method'            => $methodType,
            'paid_at'                   => now(),
        ]);

        $totalPaid = $loan->payments()->where('status', 'paid')->sum('amount');
        $loan->update([
            'fine_paid_amount' => $totalPaid,
            'fine_paid'        => $totalPaid >= $loan->fine_amount,
        ]);

        return $payment->fresh();
    }
}
