<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Password Reset</title>
</head>

<body style="margin:0; padding:0; font-family:'Segoe UI', Arial, sans-serif; background-color:#f4f4f4;">

  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px 0;">
    <tr>
      <td align="center">

        <!-- Container -->
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 0 10px rgba(0,0,0,0.05);">

          <!-- Header -->
          <tr>
            <td align="center" style="padding:20px; background-color:#0f766e;">
              <span style="display:inline-block; font-size:28px; line-height:1; font-weight:700; color:#ffffff; letter-spacing:0.5px;">FixlyHandy</span>
            </td>
          </tr>

          <!-- Content -->
          <tr>
            <td style="padding:30px; color:#333333; font-size:16px; line-height:1.5;">
              <h2 style="font-size:20px; margin:0 0 20px; color:#0f766e;">Hi {{ $name ?? 'User' }},</h2>

              <p style="margin:0 0 20px;">You requested a password reset for your account.</p>
              <p style="margin:0 0 30px;">Click the button below to set a new password:</p>

              <!-- Button -->
              <table cellspacing="0" cellpadding="0" border="0" align="center" style="margin-bottom:30px;">
                <tr>
                  <td align="center" bgcolor="#0f766e" style="border-radius:4px;">
                    <a href="{{ $resetUrl }}" target="_blank"
                      style="font-size:16px; color:#ffffff; text-decoration:none; padding:12px 24px; display:inline-block;">
                      Reset Password
                    </a>
                  </td>
                </tr>
              </table>

              <!-- Fallback Link -->
              <p style="font-size:14px; margin-bottom:30px;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $resetUrl }}" style="color:#0f766e; word-break:break-all;">{{ $resetUrl }}</a>
              </p>

              <p style="margin-bottom:20px;">If you didn’t request this, you can safely ignore this email.</p>
              <p style="margin:0;">Best regards,<br>{{ config('app.name') }} Team</p>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding:20px; background-color:#0f766e; font-size:12px; color:#ffffff;">
              <p style="margin-bottom:10px;">
                <a href="#" style="margin:0 5px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/733/733579.png" alt="Facebook" width="20" style="display:inline-block;">
                </a>
                <a href="#" style="margin:0 5px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/2111/2111463.png" alt="Instagram" width="20" style="display:inline-block;">
                </a>
                <a href="#" style="margin:0 5px;">
                  <img src="https://cdn-icons-png.flaticon.com/512/145/145807.png" alt="LinkedIn" width="20" style="display:inline-block;">
                </a>
              </p>
              &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

</body>

</html>