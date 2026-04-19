<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Fine Payment Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #1e293b;
            background: #ffffff;
            font-size: 13px;
            line-height: 1.5;
        }

        .page {
            width: 700px;
            margin: 0 auto;
            padding: 0;
        }

        /* ── Accent Bar ── */
        .accent-bar {
            background-color: #4f46e5;
            height: 7px;
            width: 100%;
            font-size: 0;
        }

        /* ── Header ── */
        .header {
            padding: 28px 40px 22px;
            border-bottom: 1px solid #e2e8f0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
            padding: 0;
        }

        .brand-name {
            font-size: 16px;
            font-weight: bold;
            color: #1e293b;
            letter-spacing: -0.3px;
        }

        .brand-sub {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .receipt-no-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            text-align: right;
        }

        .receipt-no-value {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            text-align: right;
            letter-spacing: -0.5px;
        }

        /* ── Status Banner ── */
        .status-banner {
            margin: 22px 40px;
            background-color: #f0fdf4;
            border: 1.5px solid #bbf7d0;
            border-radius: 10px;
            padding: 14px 20px;
        }

        .status-table {
            width: 100%;
            border-collapse: collapse;
        }

        .status-table td { padding: 0; vertical-align: middle; }

        .status-text {
            font-size: 13px;
            font-weight: 700;
            color: #15803d;
        }

        .status-subtext {
            font-size: 11px;
            font-weight: normal;
            color: #4ade80;
        }

        .status-date {
            font-size: 11px;
            color: #86efac;
            font-weight: bold;
            text-align: right;
        }

        /* ── Info Blocks ── */
        .info-section {
            margin: 0 40px 22px;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            vertical-align: top;
            padding: 0;
        }

        .info-block {
            background-color: #f8fafc;
            border-radius: 10px;
            padding: 16px 20px;
        }

        .info-block-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
            margin-bottom: 8px;
        }

        .info-block-name {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .info-block-detail {
            font-size: 11px;
            color: #64748b;
            font-weight: normal;
            line-height: 1.8;
        }

        /* ── Section Label ── */
        .section-label {
            margin: 0 40px 10px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #94a3b8;
        }

        /* ── Details Table ── */
        .details-section {
            margin: 0 40px 24px;
            border: 1.5px solid #e2e8f0;
            border-radius: 10px;
        }

        .details-table {
            width: 100%;
            border-collapse: collapse;
        }

        .details-table th {
            background-color: #f8fafc;
            padding: 11px 16px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            text-align: left;
            border-bottom: 1.5px solid #e2e8f0;
        }

        .details-table th.right { text-align: right; }

        .details-table td {
            padding: 16px 16px;
            font-size: 12px;
            color: #1e293b;
            vertical-align: top;
        }

        .details-table td.right {
            text-align: right;
            font-weight: 700;
        }

        .book-title {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 3px;
        }

        .book-sub {
            font-size: 10px;
            color: #94a3b8;
            font-weight: normal;
        }

        /* ── Totals ── */
        .totals-section {
            margin: 0 40px 32px;
            text-align: right;
        }

        .totals-box {
            width: 260px;
            background-color: #f8fafc;
            border-radius: 10px;
            padding: 18px 22px;
            display: inline-block;
        }

        .totals-inner {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-inner td {
            padding: 4px 0;
            font-size: 12px;
            vertical-align: middle;
        }

        .t-label { color: #64748b; font-weight: bold; text-align: left; }
        .t-value { font-weight: 700; color: #1e293b; text-align: right; }

        .totals-divider {
            border: none;
            border-top: 1.5px solid #e2e8f0;
            margin: 10px 0;
        }

        .grand-label {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            text-align: left;
        }

        .grand-value {
            font-size: 20px;
            font-weight: bold;
            color: #4f46e5;
            text-align: right;
        }

        /* ── Footer ── */
        .footer {
            margin: 0 40px 40px;
            padding-top: 20px;
            border-top: 1px solid #f1f5f9;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }

        .footer-table td { vertical-align: middle; padding: 0; }

        .footer-note {
            font-size: 11px;
            color: #94a3b8;
            font-weight: normal;
            line-height: 1.7;
        }

        .footer-note strong {
            color: #64748b;
            font-weight: 700;
        }

        .footer-generated {
            font-size: 10px;
            color: #cbd5e1;
            font-weight: bold;
            text-align: right;
            line-height: 1.7;
        }
    </style>
</head>
<body>
<div class="page">

    <!-- Accent Bar -->
    <div class="accent-bar">&nbsp;</div>

    <!-- Header -->
    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand-name">Library Management System</div>
                    <div class="brand-sub">Official Payment Receipt</div>
                </td>
                <td width="200">
                    <div class="receipt-no-label">Receipt No.</div>
                    <div class="receipt-no-value">RCPT-{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Status Banner -->
    <div class="status-banner">
        <table class="status-table">
            <tr>
                <td>
                    <span class="status-text">Payment Settled</span>
                    <span class="status-subtext">&nbsp;&nbsp;Fine fully cleared</span>
                </td>
                <td width="200">
                    <div class="status-date">{{ now()->format('d M Y, h:i A') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Member & Loan Info -->
    <div class="info-section">
        <table class="info-table">
            <tr>
                <td width="48%">
                    <div class="info-block">
                        <div class="info-block-label">Member Details</div>
                        <div class="info-block-name">{{ $loan->user->name }}</div>
                        <div class="info-block-detail">
                            {{ $loan->user->email }}<br>
                            {{ $loan->user->phone ?? 'No phone on record' }}
                        </div>
                    </div>
                </td>
                <td width="4%"></td>
                <td width="48%">
                    <div class="info-block">
                        <div class="info-block-label">Loan Reference</div>
                        <div class="info-block-name">LOAN-{{ str_pad($loan->id, 6, '0', STR_PAD_LEFT) }}</div>
                        <div class="info-block-detail">
                            Due: {{ $loan->due_date->format('d M Y') }}<br>
                            Returned: {{ $loan->returned_date ? $loan->returned_date->format('d M Y') : 'N/A' }}
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Transaction Details -->
    <div class="section-label">Fine Information</div>
    <div class="details-section" style="margin-bottom: 20px;">
        <table class="details-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Borrow Date</th>
                    <th>Return Date</th>
                    <th class="right">Fine (RM)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="book-title">{{ $loan->bookCopy->book->title }}</div>
                        <div class="book-sub">Fine due to late return</div>
                    </td>
                    <td>{{ $loan->borrowed_date->format('d M Y') }}</td>
                    <td>{{ $loan->returned_date ? $loan->returned_date->format('d M Y') : 'N/A' }}</td>
                    <td class="right">{{ number_format($loan->fine_amount, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Payment History -->
    <div class="section-label">Payment Breakdown</div>
    <div class="details-section">
        <table class="details-table">
            <thead>
                <tr>
                    <th>Date & Time</th>
                    <th>Method</th>
                    <th>Reference (Stripe)</th>
                    <th class="right">Amount (RM)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loan->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at->format('d M Y, h:i A') }}</td>
                    <td style="text-transform: capitalize;">{{ $payment->payment_method }}</td>
                    <td style="font-family: monospace; font-size: 10px;">{{ $payment->stripe_payment_intent_id }}</td>
                    <td class="right">{{ number_format($payment->amount, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Totals -->
    <div class="totals-section">
        <div class="totals-box">
            <table class="totals-inner">
                <tr>
                    <td class="t-label">Original Fine</td>
                    <td class="t-value">RM {{ number_format($loan->fine_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="t-label">Total Payments</td>
                    <td class="t-value">RM {{ number_format($loan->fine_paid_amount, 2) }}</td>
                </tr>
            </table>
            <hr class="totals-divider">
            <table class="totals-inner">
                <tr>
                    <td class="grand-label">Balance Due</td>
                    <td class="grand-value">RM {{ number_format(max(0, $loan->fine_amount - $loan->fine_paid_amount), 2) }}</td>
                </tr>
            </table>
            <div style="font-size: 9px; color: #15803d; font-weight: bold; margin-top: 8px; text-align: center; text-transform: uppercase; letter-spacing: 1px;">
                Account Fully Settled
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <table class="footer-table">
            <tr>
                <td>
                    <div class="footer-note">
                        <strong>Thank you for your payment.</strong><br>
                        Please retain this receipt for your records.
                    </div>
                </td>
                <td width="220">
                    <div class="footer-generated">
                        &copy; {{ date('Y') }} Library Management System<br>
                        Generated {{ now()->format('d M Y, h:i A') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

</div>
</body>
</html>