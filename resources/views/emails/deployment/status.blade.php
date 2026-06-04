<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Deployment Status</title>
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

            .deployment-box {
                margin: 15px 0 !important;
                padding: 15px !important;
            }

            .info-table td {
                display: block !important;
                width: 100% !important;
                padding: 5px 0 !important;
            }

            .cta-button {
                display: block !important;
                text-align: center !important;
                padding: 12px 20px !important;
                font-size: 14px !important;
            }

            .footer-cell {
                padding: 20px 15px !important;
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

            .deployment-box {
                background-color: #2d2d2d !important;
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
                    <h1 style="margin: 0 0 5px 0; font-size: 32px; font-weight: 700; letter-spacing: 0.5px; text-align: left; color: #111827;">
                        {{ $appName }}
                        <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769959736/pngimg.com_-_github_PNG40_r7m4r1.png"
                            alt="GitHub"
                            style="width: 40px; height: 40px; vertical-align: middle; opacity: 0.8;">
                    </h1>
                    <p class="title-text" style="margin: 20px 0 5px 0; font-size: 18px; font-weight: 400; opacity: 0.95; text-align: left; color: #374151;">
                        CI/CD Pipeline Triggered
                    </p>
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
                    <!-- Deployment Status -->
                    <!-- Deployment Status -->
                    <div style="margin: 30px 0 20px 0; padding: 15px;">
                        <p style="margin: 0; font-size: 19px; font-weight: 600; color: @if($data['success'])#159a4a @else#db1010 @endif;">
                            @if($data['success'])
                            <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769959937/check-mark-png-45004_dieajm.png"
                                alt="✓"
                                width="20" height="20"
                                style="width: 20px; height: 20px; margin-right: 10px; vertical-align: middle;">
                            Deployment Successful
                            @else
                            <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769960685/Pngtree_error-cross_6020171_av3grp.png"
                                alt="✗"
                                width="20" height="20"
                                style="width: 20px; height: 20px; margin-right: 10px; vertical-align: middle;">
                            Deployment Failed
                            @endif
                        </p>
                    </div>

                    <!-- Deployment Details Box -->
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" class="deployment-box" style="background-color: #f8fafc; border: 1px solid #e5e7eb; border-radius: 8px; margin: 25px 0; padding: 25px;">
                        <tr>
                            <td>
                                <h2 style="margin: 0 0 20px 0; font-size: 18px; color: #111827; font-weight: 600;">Deployment Details</h2>

                                <table width="100%" cellpadding="0" cellspacing="0" border="0" class="info-table" style="font-size: 14px; color: #4b5563;">
                                    <tr>
                                        <td style="padding: 8px 0; width: 140px; vertical-align: top;">
                                            <strong>Repository:</strong>
                                        </td>
                                        <td style="padding: 8px 0;">
                                            <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769959736/pngimg.com_-_github_PNG40_r7m4r1.png"
                                                alt="GitHub"
                                                style="width: 15px; height: 16px; vertical-align: middle; margin-right: 1px;">
                                            {{ $data['repository']['name'] ?? 'Unknown' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Commit:</strong></td>
                                        <td style="padding: 8px 0;">
                                            <span style="font-family: monospace; background-color: #f3f4f6; color:#167e85b5; padding: 2px 6px; border-radius: 4px; font-size: 13px;">
                                                {{ substr($data['commit']['id'] ?? 'unknown', 0, 8) }}
                                            </span>
                                            @if(isset($data['commit']['url']))
                                            <br>
                                            <a href="{{ $data['commit']['url'] }}" style="color: #6a11cb; text-decoration: none; font-size: 13px;">
                                                <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769959736/pngimg.com_-_github_PNG40_r7m4r1.png"
                                                    alt="GitHub"
                                                    style="width: 15px; height: 16px; vertical-align: middle; margin-right: 1px;">
                                                View commit on GitHub →
                                            </a>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Message:</strong></td>
                                        <td style="padding: 8px 0;">{{ $data['commit']['message'] ?? 'No commit message' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Author:</strong></td>
                                        <td style="padding: 8px 0;">{{ $data['commit']['author'] ?? 'Unknown' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Environment:</strong></td>
                                        <td style="padding: 8px 0;">
                                            <span style="background-color: @if($environment === 'production')#fef3c7 @else#dbeafe @endif; color: @if($environment === 'production')#92400e @else#1e40af @endif; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-weight: 500;">
                                                {{ ucfirst($environment) }}
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Duration:</strong></td>
                                        <td style="padding: 8px 0;">{{ number_format($data['duration'], 2) }} seconds</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Timestamp:</strong></td>
                                        <td style="padding: 8px 0;">{{ $data['timestamp'] }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Backup:</strong></td>
                                        <td style="padding: 8px 0;">
                                            @if($data['backup'] === 'Created')
                                            <span style="color: #10b981; font-weight: 500;">Created</span>
                                            @elseif($data['backup'] === 'Skipped')
                                            <span style="color: #6b7280;">Skipped</span>
                                            @else
                                            <span style="color: #ef4444;">Not created</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @if(!$data['success'])
                                    <tr>
                                        <td style="padding: 8px 0; vertical-align: top;"><strong>Error:</strong></td>
                                        <td style="padding: 8px 0; color: #ef4444; font-family: monospace; font-size: 13px; background-color: #fef2f2; padding: 10px; border-radius: 4px; margin-top: 5px;">
                                            {{ $data['error'] ?? 'Unknown error' }}
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Deployment Steps -->
                    @if($data['success'] && isset($data['steps_completed']))
                    <p style="margin: 0 0 15px 0; font-size: 15px; color: #4b5563;">
                        <strong>Deployment completed {{ $data['steps_completed'] }} steps successfully:</strong>
                    </p>

                    <ul style="margin: 0 0 25px 0; padding-left: 20px; color: #4b5563; font-size: 14px;">
                        <li style="margin-bottom: 8px;">Code pulled from repository</li>
                        <li style="margin-bottom: 8px;">Dependencies installed</li>
                        <li style="margin-bottom: 8px;">Database migrations {{ $data['migration_status'] === 'pretend_mode' ? 'simulated' : 'executed' }}</li>
                        <li style="margin-bottom: 8px;">Application caches cleared</li>
                        <li style="margin-bottom: 8px;">Application optimized</li>
                    </ul>
                    @endif

                    <!-- Repository Link -->
                    @if(isset($data['repository']['url']))
                    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 30px 0;">
                        <tr>
                            <td align="center">
                                <a href="{{ $data['repository']['url'] }}"
                                    class="cta-button"
                                    style="display: inline-block; background-color: #24292f; color: #ffffff; text-decoration: none; font-weight: 600; font-size: 14px; padding: 12px 24px; border-radius: 6px; text-align: center; line-height: 1;">
                                    <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769961409/pngegg_toaztd.png"
                                        alt="GitHub"
                                        width="16" height="16"
                                        style="width: 16px; height: 16px; margin-right: 8px; vertical-align: middle; margin-bottom: 2px;">
                                    View Repository on GitHub
                                </a>
                            </td>
                        </tr>
                    </table>
                    @endif

                    <!-- Automated Message -->
                    <div style="margin-top: 30px; padding: 15px; background-color: #f0f9ff; border-radius: 6px; border-left: 4px solid #0ea5e9;">
                        <p style="margin: 0; font-size: 13px; color: #0369a1; display: flex; align-items: flex-start;">
                            <img src="https://res.cloudinary.com/dwi5dlj62/image/upload/v1769962025/robot-44_euqdnf.png"
                                alt="🤖"
                                style="width: 40px; height: 25px; margin-right: 8px; margin-top: 2px; vertical-align: middle; flex-shrink: 0;">
                            <span>
                                <strong>Automated Deployment</strong><br>
                                This deployment was triggered automatically via GitHub webhook. You're receiving this notification because you're configured as a deployment recipient.
                            </span>
                        </p>
                    </div>
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
                        You're receiving this email because you're configured to receive deployment notifications for {{ $appName }}.
                    </p>
                    <p style="margin: 0 0 15px 0; line-height: 1.5;">
                        Environment: <strong>{{ $environment }}</strong> | Server: <strong>{{ gethostname() }}</strong>
                    </p>

                    <p style="margin: 0 0 12px 0;">© {{ $currentYear }} {{ $appName }}. All Rights Reserved</p>

                    <p class="footer-links" style="margin: 0;">
                        <a href="{{ config('app.url') }}" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Dashboard</a><span style="color: #6b7280;"> | </span>
                        <a href="mailto:{{ config('mail.support_email', 'support@example.com') }}" style="color: #6b7280; text-decoration: none; margin: 0 6px;">Support</a>
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