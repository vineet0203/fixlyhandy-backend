<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice Link</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7fa; font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 32px; box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08); }
        .title { color: #1e3a5f; font-size: 24px; margin: 0 0 16px; }
        .text { color: #444; line-height: 1.6; margin: 0 0 12px; }
        .invoice-box { background: #f6f9ff; border-left: 4px solid #3574BB; padding: 12px 16px; margin: 18px 0; }
        .btn { display: inline-block; background: #3574BB; color: #fff !important; text-decoration: none; padding: 12px 24px; border-radius: 8px; font-weight: 600; }
        .muted { color: #7a8292; font-size: 12px; margin-top: 22px; }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1 class="title">Invoice Ready</h1>

        <p class="text">Your invoice is ready to view using the secure link below.</p>

        <div class="invoice-box">
            <strong>Invoice:</strong> {{ $invoice->invoice_number }}
        </div>

        <p class="text">
            <a href="{{ $publicUrl }}" class="btn">View Invoice</a>
        </p>

        @if(!empty($expiresAt))
            <p class="text">This secure link expires at <strong>{{ $expiresAt }}</strong>.</p>
        @endif

        <p class="muted">If you did not expect this email, you can ignore it.</p>
    </div>
</div>
</body>
</html>
