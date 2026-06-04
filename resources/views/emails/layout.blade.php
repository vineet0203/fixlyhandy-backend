{{-- resources/views/emails/layout.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name'))</title>
    <style>
        /* Base styles */
        body { margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4; }
        .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; }
        .header { background-color: #3b82f6; color: white; padding: 20px; text-align: center; }
        .content { padding: 30px; }
        .footer { background-color: #f8f9fa; padding: 20px; text-align: center; color: #6b7280; font-size: 12px; }
        .button { background-color: #3b82f6; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; display: inline-block; }
        .info-box { background-color: #f3f4f6; border-left: 4px solid #3b82f6; padding: 15px; margin: 20px 0; }
        .credentials { background-color: #fef3c7; border: 1px solid #f59e0b; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>@yield('greeting', 'Welcome')</h1>
        </div>
        
        <div class="content">
            @yield('content')
        </div>
        
        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.</p>
            <p>
                <a href="{{ config('app.url') }}/privacy" style="color: #6b7280; text-decoration: none;">Privacy Policy</a> | 
                <a href="{{ config('app.url') }}/terms" style="color: #6b7280; text-decoration: none;">Terms of Service</a> | 
                <a href="mailto:{{ config('mail.support_email', 'support@example.com') }}" style="color: #6b7280; text-decoration: none;">Contact Support</a>
            </p>
            <p style="font-size: 10px; margin-top: 10px;">
                This is an automated message. Please do not reply to this email.
            </p>
        </div>
    </div>
</body>
</html>