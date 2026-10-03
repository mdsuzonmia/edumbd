<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Successful</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.08);">
        <div style="background: #007bff; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">Password Reset Successful</h1>
        </div>

        <div style="padding: 24px; color: #333333;">
            <p style="font-size: 16px; margin-bottom: 16px;">Hello <?= esc($name) ?>,</p>
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                Your password has been updated successfully. You can now sign in using your new password.
            </p>

            <p style="text-align: center; margin-bottom: 24px;">
                <a href="<?= site_url('login') ?>" style="display: inline-block; background: #28a745; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">Go to Login</a>
            </p>

            <p style="font-size: 14px; color: #555555; margin-bottom: 0;">
                If you did not reset your password, please contact our support team immediately.
            </p>
        </div>

        <div style="background: #f8f9fa; color: #555555; padding: 16px; font-size: 13px; text-align: center;">
            <p style="margin: 0;">Thank you for using our service.</p>
        </div>
    </div>
</body>
</html>
