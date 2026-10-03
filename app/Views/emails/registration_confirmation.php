<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Registration Confirmation</title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f6f6; padding:20px;">

    <div style="max-width:600px; margin:0 auto; background:#ffffff; padding:20px; border-radius:8px;">

        <h2 style="color:#333;">Welcome, <?= esc($name) ?> 🎉</h2>

        <p>Thank you for registering with us. Your account has been successfully created.</p>

        <p>
            To complete your registration, please verify your email address by clicking the button below:
        </p>

        <p style="text-align:center; margin:30px 0;">
            <a href="<?= esc($verification_link) ?>"
               style="background:#28a745; color:#fff; padding:12px 20px; text-decoration:none; border-radius:5px;">
                Verify Email
            </a>
        </p>

        <p>If the button doesn’t work, copy and paste this link into your browser:</p>

        <p style="word-break:break-all;">
            <?= esc($verification_link) ?>
        </p>

        <hr>

        <p style="font-size:12px; color:#888;">
            This link will expire in 30 minutes for security reasons.
        </p>

        <p style="font-size:12px; color:#888;">
            If you did not create this account, you can safely ignore this email.
        </p>

    </div>

</body>
</html>