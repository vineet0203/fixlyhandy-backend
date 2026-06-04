<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 24px; }
        .card { max-width: 900px; margin: 0 auto; background: #fff; padding: 24px; border-radius: 10px; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .title { margin: 0; color: #2e74d0; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border-bottom: 1px solid #e5e7eb; text-align: left; padding: 10px; font-size: 13px; }
        th { color: #6b7280; text-transform: uppercase; font-size: 11px; }
        .totals { margin-top: 18px; display: flex; justify-content: flex-end; }
        .totals table { width: 320px; }
        .totals td { border-bottom: none; }
        .total { font-weight: 700; border-top: 1px solid #d1d5db; }
    </style>
</head>
<body>
<div class="card">
    <div class="header">
        <div>
            <h1 class="title">Invoice</h1>
            <div>{{ $invoice->invoice_number }}</div>
        </div>
        <div>
            <div><strong>Bill Date:</strong> {{ optional($invoice->bill_date)->toDateString() }}</div>
            <div><strong>Due Date:</strong> {{ optional($invoice->payment_deadline)->toDateString() }}</div>
        </div>
    </div>

    <div>
        <strong>Employee:</strong> {{ trim(($invoice->employee->first_name ?? '') . ' ' . ($invoice->employee->last_name ?? '')) }}
    </div>

    <table>
        <thead>
        <tr>
            <th>No</th>
            <th>Job ID</th>
            <th>Job Name</th>
            <th>Mileage</th>
            <th>Other Expense</th>
            <th>Amount</th>
            <th>VAT</th>
            <th>Final</th>
        </tr>
        </thead>
        <tbody>
        @foreach($invoice->items as $index => $item)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->job_id ?: '-' }}</td>
                <td>{{ $item->job_name }}</td>
                <td>{{ number_format((float) $item->mileage, 2) }}</td>
                <td>{{ number_format((float) $item->other_expense, 2) }}</td>
                <td>{{ number_format((float) $item->amount, 2) }}</td>
                <td>{{ number_format((float) $item->vat, 2) }}%</td>
                <td>{{ number_format((float) $item->final_amount, 2) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @php
        $subtotal = (float) $invoice->items->sum('amount');
        $mileage = (float) $invoice->items->sum('mileage');
        $other = (float) $invoice->items->sum('other_expense');
        $vat = (float) $invoice->items->sum(function ($item) {
            return round(((float) $item->amount * (float) $item->vat) / 100, 2);
        });
        $total = (float) $invoice->items->sum('final_amount');
    @endphp

    <div class="totals">
        <table>
            <tr><td>Subtotal</td><td>{{ number_format($subtotal, 2) }}</td></tr>
            <tr><td>Mileage</td><td>{{ number_format($mileage, 2) }}</td></tr>
            <tr><td>Other Expense</td><td>{{ number_format($other, 2) }}</td></tr>
            <tr><td>VAT</td><td>{{ number_format($vat, 2) }}</td></tr>
            <tr class="total"><td>Total</td><td>{{ number_format($total, 2) }}</td></tr>
        </table>
    </div>
</div>
</body>
</html>
