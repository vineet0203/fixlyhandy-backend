{{-- resources/views/emails/admin/vendor-registration.blade.php --}}
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>New Vendor Registration - {{ $vendor->business_name }}</title>
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
            
            .vendor-box {
                margin: 20px 0 !important;
                padding: 15px !important;
            }
            
            .vendor-table td {
                display: block !important;
                width: 100% !important;
                padding: 4px 0 !important;
            }
            
            .vendor-table td[style*="width: 130px"] {
                padding-bottom: 0 !important;
                font-weight: 600 !important;
            }
            
            .cta-button {
                display: block !important;
                text-align: center !important;
                padding: 12px 20px !important;
                font-size: 14px !important;
                margin-bottom: 10px !important;
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
            
            .vendor-box {
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
                    <p class="title-text" style="margin: 20px 0 5px 0; font-size: 18px; font-weight: 400; opacity: 0.95; text-align: left; color: #374151;">New Vendor Registration</p>
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
                        <strong>Dear {{ $admin->last_name ?? 'Admin' }},</strong>
                    </p>

                    <!-- Alert Message -->
                    <p style="margin: 0 0 20px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        A new vendor has registered on the {{ $appName }} platform. Please review their information below.
                    </p>

                    <!-- Vendor Details Box -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="vendor-box" style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; margin: 25px 0; padding: 20px;">
                        <tr>
                            <td>
                                <h2 style="margin: 0 0 15px 0; font-size: 17px; color: #111827; font-weight: 600;">Vendor Registration Details</h2>
                                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="vendor-table" style="font-size: 14px; color: #4b5563;">
                                    <tr>
                                        <td style="padding: 6px 0; width: 130px;"><strong>Business:</strong></td>
                                        <td style="padding: 6px 0; font-weight: 600; color: #111827;">{{ $vendor->business_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Contact:</strong></td>
                                        <td style="padding: 6px 0;">{{ $user->first_name }} {{ $user->last_name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Email:</strong></td>
                                        <td style="padding: 6px 0;">{{ $user->email }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Phone:</strong></td>
                                        <td style="padding: 6px 0;">{{ $vendor->mobile_number ?? 'Not provided' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Registered:</strong></td>
                                        <td style="padding: 6px 0;">{{ $registrationDate }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 6px 0;"><strong>Status:</strong></td>
                                        <td style="padding: 6px 0;">
                                            <span style="background-color: #fef3c7; color: #92400e; padding: 2px 8px; border-radius: 4px; font-weight: 500;">
                                                {{ ucfirst($vendor->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Action Required -->
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #4b5563; font-weight: 600;">
                        ⚡ Action Required:
                    </p>

                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        Please review this vendor's registration and take appropriate action if necessary.
                    </p>

                    <!-- Action Buttons -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                        <tr>
                            <td align="center">
                                <a href="{{ $vendorUrl }}" class="cta-button" style="display: inline-block; background-color: #6a11cb; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 12px 30px; border-radius: 6px; text-align: center; margin-right: 10px;">
                                    Review Vendor
                                </a>
                                <a href="{{ $adminDashboardUrl }}" class="cta-button" style="display: inline-block; background-color: #374151; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 15px; padding: 12px 30px; border-radius: 6px; text-align: center;">
                                    View All Vendors
                                </a>
                            </td>
                        </tr>
                    </table>

                    <!-- Additional Notes -->
                    <p style="margin: 30px 0 20px 0; font-size: 15px; color: #4b5563;">
                        <strong>Note:</strong> The vendor has already received their welcome email and can access their dashboard.
                    </p>

                    <!-- Closing -->
                    <p style="margin: 0 0 25px 0; font-size: 15px; color: #4b5563; line-height: 1.6;">
                        This is an automated notification. Please review and take appropriate action as soon as possible.
                    </p>

                    <!-- Signature -->
                    <p style="margin: 0 0 5px 0; font-size: 15px; color: #4b5563;">Best regards,</p>
                    <p style="margin: 0; font-size: 15px; color: #111827; font-weight: 600;">The {{ $appName }} Admin Team</p>
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
                        You're receiving this email because you're an administrator on {{ $appName }}.
                    </p>
                    <p style="margin: 0 0 15px 0; line-height: 1.5;">
                        To adjust notification settings, visit your admin profile.
                    </p>

                    <p style="margin: 0 0 12px 0;">© {{ date('Y') }} {{ $appName }}. All Rights Reserved</p>

                    <p class="footer-links" style="margin: 0;">
                        <a href="{{ config('app.url') }}/admin/settings" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Admin Settings</a><span style="color: #6b7280;"> | </span>
                        <a href="{{ config('app.url') }}/admin/notifications" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Notification Preferences</a><span style="color: #6b7280;"> | </span>
                        <a href="mailto:{{ $supportEmail ?? 'admin@fixlyhandy.com' }}" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Support</a>
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