{{-- resources/views/emails/auth/password-reset.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Reset Your Password - {{ $appName }}</title>
    <!--[if mso]>
    <xml>
        <o:OfficeDocumentSettings>
            <o:AllowPNG/>
            <o:PixelsPerInch>96</o:PixelsPerInch>
        </o:OfficeDocumentSettings>
    </xml>
    <style>
        table {border-collapse: collapse;}
        .spacer {display: none !important;}
    </style>
    <![endif]-->
    <style>
        /* Mobile-first responsive styles */
        @media only screen and (max-width: 640px) {
            .container {
                width: 100% !important;
                max-width: 100% !important;
                margin: 0 !important;
                padding: 10px !important;
            }
            
            .content-cell {
                padding: 20px 15px !important;
            }
            
            .header-cell {
                padding: 30px 15px 10px 15px !important;
            }
            
            h1 {
                font-size: 28px !important;
                line-height: 1.2 !important;
            }
            
            h2 {
                font-size: 18px !important;
                line-height: 1.3 !important;
            }
            
            .title-text {
                font-size: 16px !important;
                margin: 15px 0 5px 0 !important;
            }
            
            .greeting {
                margin: 25px 0 15px 0 !important;
                font-size: 15px !important;
            }
            
            .info-box {
                margin: 20px 0 !important;
                padding: 15px !important;
            }
            
            .cta-button {
                display: block !important;
                text-align: center !important;
                padding: 14px 20px !important;
                font-size: 16px !important;
                margin: 20px 0 10px 0 !important;
                width: 100% !important;
            }
            
            .footer-cell {
                padding: 20px 15px !important;
            }
            
            .footer-links a {
                display: block !important;
                margin: 5px 0 !important;
                padding: 0 !important;
            }
            
            .footer-links span {
                display: none !important;
            }
            
            .security-note {
                font-size: 12px !important;
                padding: 10px !important;
            }
        }
        
        /* Tablet styles */
        @media only screen and (min-width: 641px) and (max-width: 768px) {
            .container {
                width: 90% !important;
                max-width: 600px !important;
            }
        }
        
        /* Desktop styles */
        @media only screen and (min-width: 769px) {
            .container {
                width: 100% !important;
                max-width: 700px !important;
            }
        }
        
        /* Dark mode support */
        @media (prefers-color-scheme: dark) {
            body {
                background-color: #121212 !important;
            }
            
            .email-container {
                background-color: #1e1e1e !important;
                color: #e0e0e0 !important;
            }
            
            h1, h2, h3, strong {
                color: #ffffff !important;
            }
            
            p, td {
                color: #b0b0b0 !important;
            }
            
            .info-box {
                background-color: #2d2d2d !important;
                border-color: #404040 !important;
            }
            
            hr {
                border-color: #404040 !important;
            }
            
            .footer-cell {
                background-color: #2d2d2d !important;
                color: #b0b0b0 !important;
            }
            
            .footer-links a {
                color: #b0b0b0 !important;
            }
            
            .security-note {
                background-color: #1a1a1a !important;
                border-color: #404040 !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.5; color: #333333; background-color: #f5f5f5; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%;">
    <!--[if mso]>
    <div style="background-color: #f5f5f5;">
    <![endif]-->
    <center>
        <table width="100%" cellpadding="0" cellspacing="0" border="0" class="container" style="max-width: 600px; margin: 0 auto; background-color: #ffffff; width: 100%;">
            <!-- Header -->
            <tr>
                <td class="header-cell" style="padding: 40px 35px 15px 35px; color: #161616;">
                    <h1 style="margin: 0 0 5px 0; font-size: 32px; font-weight: 700; letter-spacing: 0.5px; text-align: left; color: #111827;">{{ $appName }}</h1>
                    <p class="title-text" style="margin: 20px 0 5px 0; font-size: 18px; font-weight: 400; opacity: 0.95; text-align: left; color: #374151;">Password Reset Request</p>
                </td>
            </tr>
            
            <!-- Divider -->
            <tr>
                <td style="padding: 0 35px;">
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                </td>
            </tr>

            <!-- Main Content -->
            <tr>
                <td class="content-cell" style="padding: 0 35px 30px 35px;">
                    <!-- Greeting -->
                    <p class="greeting" style="margin: 40px 0 20px 0; font-size: 16px; color: #374151;">
                        <strong>Hello {{ $user->first_name ?? 'there' }},</strong>
                    </p>

                    <!-- Main Message -->
                    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        We received a request to reset your password for your {{ $appName }} account. If you didn't make this request, you can safely ignore this email.
                    </p>

                    <!-- Information Box -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="info-box" style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; margin: 25px 0; padding: 20px;">
                        <tr>
                            <td>
                                <h2 style="margin: 0 0 15px 0; font-size: 17px; color: #111827; font-weight: 600;">Reset Details</h2>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" style="font-size: 14px; color: #4b5563;">
                                    <tr>
                                        <td style="padding: 6px 0; width: 130px;"><strong>Account:</strong></td>
                                        <td style="padding: 6px 0; font-weight: 600; color: #111827;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Request Time:</strong></td>
                                        <td style="padding: 6px 0;">{{ now()->format('F j, Y \a\t H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Link Expires:</strong></td>
                                        <td style="padding: 6px 0;">{{ now()->addMinutes($expireMinutes)->format('F j, Y \a\t H:i') }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Request From:</strong></td>
                                        <td style="padding: 6px 0;">
                                            IP: {{ $ipAddress }} {{ $userAgent }}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Primary Action -->
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #4b5563; font-weight: 600;">
                        🔑 Ready to reset your password?
                    </p>

                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        Click the button below to create a new password. This link will expire in {{ $expireMinutes }} minutes for security.
                    </p>

                    <!-- Reset Button -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                        <tr>
                            <td align="center">
                                <a href="{{ $resetUrl }}" class="cta-button" style="display: inline-block; background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%); color: #ffffff; text-decoration: none; font-weight: 600; font-size: 16px; padding: 14px 40px; border-radius: 8px; text-align: center; box-shadow: 0 4px 6px rgba(106, 17, 203, 0.2);">
                                    Reset My Password
                                </a>
                            </td>
                        </tr>
                    </table>

                    <!-- Alternative Link -->
                    <p style="margin: 10px 0 25px 0; font-size: 14px; color: #6b7280; text-align: center;">
                        Or copy and paste this link:<br>
                        <span style="word-break: break-all; font-family: monospace; background-color: #f3f4f6; padding: 8px 12px; border-radius: 4px; display: inline-block; margin-top: 5px; font-size: 13px;">
                            {{ $resetUrl }}
                        </span>
                    </p>

                    <!-- Security Note -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="security-note" style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; margin: 20px 0; padding: 15px;">
                        <tr>
                            <td>
                                <p style="margin: 0; font-size: 13px; color: #92400e; line-height: 1.5;">
                                    <strong>⚠️ Security Notice:</strong> This password reset link was requested from IP address {{ $ipAddress }}. If you didn't request this, please ignore this email and ensure your account is secure. For added security, consider enabling two-factor authentication.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <!-- Instructions -->
                    <p style="margin: 25px 0 15px 0; font-size: 15px; color: #4b5563;">
                        <strong>Need help?</strong>
                    </p>
                    <ul style="margin: 0 0 25px 0; padding-left: 20px; font-size: 14px; color: #4b5563; line-height: 1.6;">
                        <li>Choose a strong password you haven't used before</li>
                        <li>Use at least 8 characters with a mix of letters, numbers, and symbols</li>
                        <li>Don't share your password with anyone</li>
                        <li>Consider using a password manager</li>
                    </ul>

                    <!-- Closing -->
                    <p style="margin: 0 0 5px 0; font-size: 15px; color: #4b5563;">Best regards,</p>
                    <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">The {{ $appName }} Security Team</p>
                </td>
            </tr>

            <!-- Divider -->
            <tr>
                <td>
                    <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 0;">
                </td>
            </tr>

            <!-- Footer -->
            <tr>
                <td class="footer-cell" align="center" style="padding: 25px 35px; font-size: 12px; color: #6b7280; background-color: #f9fafb;">
                    <p style="margin: 0 0 8px 0; line-height: 1.5;">
                        You're receiving this email because a password reset was requested for your {{ $appName }} account.
                    </p>
                    <p style="margin: 0 0 15px 0; line-height: 1.5;">
                        For security reasons, this link will expire in {{ $expireMinutes }} minutes.
                    </p>

                    <p style="margin: 0 0 12px 0;">© {{ $currentYear }} {{ $appName }}. All Rights Reserved</p>

                    <p class="footer-links" style="margin: 0;">
                        <a href="{{ config('app.url') }}/privacy" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Privacy Policy</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/security" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Security</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/help" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Help Center</a><span style="color: #6b7280;"> | </span>
                        <a href="mailto:{{ $supportEmail }}" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Support</a>
                    </p>
                </td>
            </tr>
        </table>
    </center>
    <!--[if mso]>
    </div>
    <![endif]-->
</body>
</html>