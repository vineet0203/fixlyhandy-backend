<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title>Set Password</title>
</head>

<body style="margin:0; padding:0; font-family:'Segoe UI', Arial, sans-serif; background-color:#f4f4f4;">
  <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f4; padding:20px 0;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 0 10px rgba(0,0,0,0.05);">
          <tr>
            <td align="center" style="padding:20px; background-color:#0f766e;">
              <span style="display:inline-block; font-size:28px; line-height:1; font-weight:700; color:#ffffff; letter-spacing:0.5px;">FixlyHandy</span>
            </td>
          </tr>

          <tr>
            <td style="padding:30px; color:#333333; font-size:16px; line-height:1.5;">
              <h2 style="font-size:20px; margin:0 0 20px; color:#0f766e;">Hi {{ $customer->name }},</h2>

              <p style="margin:0 0 20px;">Your customer account has been created.</p>
              <p style="margin:0 0 30px;">Click the button below to set your password:</p>

              <table cellspacing="0" cellpadding="0" border="0" align="center" style="margin-bottom:30px;">
                <tr>
                  <td align="center" bgcolor="#0f766e" style="border-radius:4px;">
                    <a href="{{ $setupUrl }}" target="_blank"
                      style="font-size:16px; color:#ffffff; text-decoration:none; padding:12px 24px; display:inline-block;">
                      Set Password
                    </a>
                  </td>
                </tr>
              </table>

              <p style="font-size:14px; margin-bottom:30px;">
                Or copy and paste this link into your browser:<br>
                <a href="{{ $setupUrl }}" style="color:#0f766e; word-break:break-all;">{{ $setupUrl }}</a>
              </p>

              <p style="margin-bottom:20px;">This link will expire in 24 hours.</p>
              <p style="margin:0;">Best regards,<br>{{ config('app.name') }} Team</p>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>

</html>