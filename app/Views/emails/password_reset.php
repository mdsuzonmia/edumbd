<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Password Reset</title>
</head>
<body style="font-family: Arial, sans-serif; background: #f6f6f6; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.06);">
        <div style="background: #007bff; color: #ffffff; padding: 24px; text-align: center;">
            <h1 style="margin: 0; font-size: 24px;">Password Reset Request</h1>
        </div>

        <div style="padding: 24px; color: #333333;">
            <p style="font-size: 16px; margin-bottom: 16px;">Hello <?= esc($name) ?>,</p>
            <p style="font-size: 16px; line-height: 1.6; margin-bottom: 24px;">
                We received a request to reset your password. Click the button below to choose a new password for your account.
            </p>

            <p style="text-align: center; margin-bottom: 24px;">
                <a href="<?= esc($reset_link) ?>" style="display: inline-block; background: #28a745; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: bold;">Reset Password</a>
            </p>

            <p style="font-size: 14px; color: #555555; margin-bottom: 24px;">
                If the button above does not work, copy and paste the following URL into your browser:
            </p>
            <p style="font-size: 14px; word-break: break-all; color: #007bff; margin-bottom: 24px;"><a href="<?= esc($reset_link) ?>" style="color: #007bff; text-decoration: none;"><?= esc($reset_link) ?></a></p>

            <p style="font-size: 14px; color: #555555; margin-bottom: 0;">
                If you did not request a password reset, please ignore this email and your password will remain unchanged.
            </p>
        </div>

        <div style="background: #f8f9fa; color: #555555; padding: 16px; font-size: 13px; text-align: center;">
            <p style="margin: 0;">Need help? Contact our support team anytime.</p>
        </div>
    </div>
</body>
</html>
