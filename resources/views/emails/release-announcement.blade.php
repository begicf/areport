<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0; padding:24px; background:#f8fafc; font-family:Arial, Helvetica, sans-serif; color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px; margin:0 auto; background:#ffffff; border:1px solid rgba(15,23,42,0.08); border-radius:18px;">
        <tr>
            <td style="padding:28px 28px 20px;">
                <div style="font-size:12px; font-weight:700; letter-spacing:0.14em; text-transform:uppercase; color:#0f766e; margin-bottom:12px;">
                    AReport Release Notice
                </div>

                <h1 style="margin:0 0 14px; font-size:28px; line-height:1.1; color:#0f172a;">
                    The new open-source AReport version is now available
                </h1>

                <p style="margin:0 0 16px; font-size:15px; line-height:1.65; color:#334155;">
                    Hello{{ $recipientName !== '' ? ' ' . $recipientName : '' }},
                </p>

                <p style="margin:0 0 16px; font-size:15px; line-height:1.65; color:#334155;">
                    We have released a new open-source version of AReport with updated reporting support and export improvements.
                </p>

                <ul style="margin:0 0 20px; padding-left:20px; color:#334155;">
                    @foreach($highlights as $highlight)
                        <li style="margin:0 0 10px; font-size:15px; line-height:1.6;">{{ $highlight }}</li>
                    @endforeach
                </ul>

                <p style="margin:0 0 22px; font-size:15px; line-height:1.65; color:#334155;">
                    You can sign in and continue working with the latest version at the link below.
                </p>

                <p style="margin:0 0 24px;">
                    <a href="{{ $loginUrl }}" style="display:inline-block; padding:12px 18px; border-radius:999px; background:#0f172a; color:#ffffff; text-decoration:none; font-weight:700;">
                        Open AReport
                    </a>
                </p>

                <p style="margin:0; font-size:14px; line-height:1.65; color:#64748b;">
                    Comments, suggestions, and feedback are welcome. If you have any questions, please reply to this email.
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
