{{-- resources/views/emails/vendor/welcome.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Welcome to {{ $appName }} - Your Account is Ready!</title>
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
            
            .account-box {
                margin: 20px 0 !important;
                padding: 15px !important;
            }
            
            .account-table td {
                display: block !important;
                width: 100% !important;
                padding: 4px 0 !important;
            }
            
            .account-table td[style*="width: 130px"] {
                padding-bottom: 0 !important;
                font-weight: 600 !important;
            }
            
            .steps-table td {
                display: block !important;
                width: 100% !important;
            }
            
            .steps-table td[style*="width: 25px"] {
                width: 25px !important;
                float: left !important;
                padding-bottom: 0 !important;
            }
            
            .cta-button {
                display: block !important;
                text-align: center !important;
                padding: 12px 20px !important;
                font-size: 14px !important;
            }
            
            .support-table td {
                display: block !important;
                width: 100% !important;
                padding: 3px 0 !important;
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
            
            .account-box, .tips-box {
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
                    <p class="title-text" style="margin: 20px 0 5px 0; font-size: 18px; font-weight: 400; opacity: 0.95; text-align: left; color: #374151;">Welcome to {{ $appName }} - Your Account is Ready!</p>
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
                        <strong>Dear {{ $user->first_name }},</strong>
                    </p>

                    <!-- Welcome Message -->
                    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        We're excited to have <strong>{{ $vendor->business_name }}</strong> join our platform. Your vendor account has been successfully created and activated.
                    </p>

                    <!-- Account Details Box -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="account-box" style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; margin: 25px 0; padding: 20px;">
                        <tr>
                            <td>
                                <h2 style="margin: 0 0 15px 0; font-size: 17px; color: #111827; font-weight: 600;">Account Information</h2>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="account-table" style="font-size: 14px; color: #4b5563;">
                                    <tr>
                                        <td style="padding: 6px 0; width: 130px;"><strong>Business Name:</strong></td>
                                        <td style="padding: 6px 0;">{{ $vendor->business_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Email:</strong></td>
                                        <td style="padding: 6px 0;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Account Type:</strong></td>
                                        <td style="padding: 6px 0;">Vendor Owner</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Registered:</strong></td>
                                        <td style="padding: 6px 0;">{{ $registrationDate }}</td>
                                    </tr>
                                    @if($vendor->business_type)
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Business Type:</strong></td>
                                        <td style="padding: 6px 0;">{{ $vendor->business_type }}</td>
                                    </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Next Steps -->
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #4b5563;">
                        Here's what you can do next to get started:
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="steps-table" style="margin: 0 0 25px 0;">
                        <tr>
                            <td valign="top" style="padding: 8px 12px 8px 0; width: 25px; font-size: 14px; color: #6a11cb;">1.</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">
                                <strong style="color: #111827;">Complete your business profile</strong> - Add your logo, services, and business details
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding: 8px 12px 8px 0; color: #6a11cb;">2.</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">
                                <strong style="color: #111827;">Set up your team</strong> - Invite employees to help manage your business
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding: 8px 12px 8px 0; color: #6a11cb;">3.</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">
                                <strong style="color: #111827;">Configure your services</strong> - Create service packages and pricing
                            </td>
                        </tr>
                        <tr>
                            <td valign="top" style="padding: 8px 12px 8px 0; color: #6a11cb;">4.</td>
                            <td style="padding: 8px 0; font-size: 14px; color: #4b5563; line-height: 1.6;">
                                <strong style="color: #111827;">Start accepting bookings</strong> - Get your first client requests
                            </td>
                        </tr>
                    </table>

                    <!-- Primary CTA -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                        <tr>
                            <td>
                                <a href="{{ $dashboardUrl }}" class="cta-button" style="display: inline-block; background-color: #6a11cb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 12px 30px; border-radius: 6px; text-align: center;">
                                    Access Your Vendor Dashboard
                                </a>
                            </td>
                        </tr>
                    </table>

                    <!-- Support Info -->
                    <p style="margin: 30px 0 20px 0; font-size: 15px; color: #4b5563;">
                        If you have any questions or need assistance, our support team is here to help:
                    </p>

                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="support-table" style="font-size: 14px; color: #4b5563; margin-bottom: 25px;">
                        <tr>
                            <td style="padding: 5px 0; width: 60px;"><strong>Email:</strong></td>
                            <td style="padding: 5px 0;">
                                <a href="mailto:{{ $supportEmail }}" style="color: #6a11cb; text-decoration: none; font-weight: 500;">{{ $supportEmail }}</a>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 5px 0;"><strong>Hours:</strong></td>
                            <td style="padding: 5px 0;">Monday-Friday, 9AM-6PM</td>
                        </tr>
                    </table>

                    <!-- Closing -->
                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        We're excited to see your business grow on our platform!
                    </p>

                    <!-- Signature -->
                    <p style="margin: 0 0 5px 0; font-size: 15px; color: #4b5563;">Best regards,</p>
                    <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">The {{ $appName }} Team</p>
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
                        You're receiving this email because you registered a vendor account on {{ $appName }}.
                    </p>
                    <p style="margin: 0 0 15px 0; line-height: 1.5;">
                        If this wasn't you, please contact our support team immediately.
                    </p>

                    <p style="margin: 0 0 12px 0;">© {{ date('Y') }} {{ $appName }}. All Rights Reserved</p>

                    <p class="footer-links" style="margin: 0;">
                        <a href="{{ config('app.url') }}/privacy" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Privacy</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/terms" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Terms</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/help" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Help Center</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/unsubscribe" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Unsubscribe</a>
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