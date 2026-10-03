<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payment Confirmation </title>
</head>
<body style="font-family: Arial, sans-serif; background:#f6f6f6; padding:20px;">

    <div style="max-width:600px; margin:0 auto; background:#ffffff; padding:20px; border-radius:8px;">

        <h2 style="color:#333;">Hello, <?= esc($name) ?> 🎉</h2>
        <p>Congratulations! Your payment has been successfully processed.</p>
    </div>
    <div style="max-width:600px; margin:20px auto 0; background:#ffffff; padding:20px; border-radius:8px;">

        <h3 style="color:#333;">Subscription Details</h3>
        <table style="width:100%; border-collapse:collapse;">
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Plan Name:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><?= esc($plan_name) ?></td>
            </tr>

            <!-- billing_cycle -->
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Billing Cycle:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><?= esc(ucfirst($billing_cycle)) ?></td>
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Amount Paid:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><?= esc($currency) ?> <?= number_format((float) ($amount ?? 0), 2) ?></td>
            </tr>
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Payment Method:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><?= esc(ucfirst($payment_gateway ?? '-')) ?></td>
            </tr>

            <!-- Transaction ID -->
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Transaction ID:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;"><?= esc($transaction_id ?? '-') ?></td>
            </tr>

            <!-- Payment Status  -->
            <tr>
                <td style="padding:8px; border:1px solid #ddd;"><strong>Payment Status:</strong></td>
                <td style="padding:8px; border:1px solid #ddd;">
                    <?php if ($payment_status === 'paid'): ?>
                        <span style="color:green; font-weight:bold;">Paid</span>
                    <?php else: ?>
                        <span style="color:orange; font-weight:bold;">Pending</span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <p style="margin-top:20px;">Thank you for choosing our service. If you have any questions or need assistance, please feel free to contact our support team.</p>

        <a href="<?= site_url('school-owner/dashboard') ?>" style="display:inline-block; padding:10px 20px; background:#007bff; color:#fff; text-decoration:none; border-radius:4px;">Go to Dashboard</a>
    </div>
</body>
</html>