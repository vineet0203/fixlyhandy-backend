<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Onboarding Document Assignment</title>
    <style>
        body { margin: 0; padding: 0; background-color: #f4f7fa; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
        .card { background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 2px 12px rgba(0,0,0,0.08); }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #1e3a5f; font-size: 28px; margin: 0; }
        .greeting { font-size: 18px; color: #333; margin-bottom: 20px; }
        .message { font-size: 15px; color: #555; line-height: 1.6; margin-bottom: 25px; }
        .document-name { background: #f0f4ff; border-left: 4px solid #3574BB; padding: 12px 16px; border-radius: 0 8px 8px 0; margin: 20px 0; font-weight: 600; color: #1e3a5f; }
        .btn-container { text-align: center; margin: 30px 0; }
        .btn { display: inline-block; background: #3574BB; color: #ffffff; text-decoration: none; padding: 14px 40px; border-radius: 8px; font-size: 16px; font-weight: 600; }
        .note { font-size: 13px; color: #888; margin-top: 25px; padding-top: 20px; border-top: 1px solid #eee; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #aaa; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="logo">
                <h1>FIXLYHANDY</h1>
            </div>

            <p class="greeting">Hello {{ $assignment->employee_name }},</p>

            <p class="message">
                You have been assigned an onboarding document that requires your attention.
                Please review and complete the following form:
            </p>

            <div class="document-name">
                📄 {{ $assignment->template->name }}
            </div>

            <p class="message">
                Click the button below to open and fill out the form. No login is required.
            </p>

            <div class="btn-container">
                <a href="{{ $formUrl }}" class="btn">Complete Form</a>
            </div>

            <div class="note">
                <strong>⏰ Important:</strong> This link will expire on
                <strong>{{ $assignment->expires_at->format('M d, Y \a\t h:i A') }}</strong>.
                Please complete the form before the deadline.
                <br><br>
                If you did not expect this email, please ignore it.
            </div>
        </div>

        <div class="footer">
            &copy; {{ date('Y') }} FIXLYHANDY. All rights reserved.
        </div>
    </div>
</body>
</html>
