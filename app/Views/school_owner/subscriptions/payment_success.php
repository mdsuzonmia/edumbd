<?php
$isPaid = ($payment_status ?? 'pending') === 'paid';
$payment = $payment ?? null;
?>

<div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5">
        <?php if ($isPaid): ?>
            <div class="display-4 text-success mb-3">
                <i class="bi bi-check2-circle"></i>
            </div>
            <h3 class="mb-2">Payment Confirmed</h3>
            <p class="text-muted mb-4">
                Your subscription plan is active now.
            </p>
        <?php else: ?>
            <div class="display-4 text-warning mb-3">
                <i class="bi bi-hourglass-split"></i>
            </div>
            <h3 class="mb-2">Payment Pending</h3>
            <p class="text-muted mb-4 col-6 mx-auto">
                Your plan upgrade was saved, but it will not become active until payment is confirmed. It can take a few minutes for the payment to be processed. Please check your email for payment confirmation and try again after a while. If you have already made the payment, please click the button below to verify your payment manually.
            </p>
            
        <?php endif; ?>

        <?php if ($payment): ?>
            <div class="row justify-content-center mb-4">
                <div class="col-md-8">
                    <div class="table-responsive">
                        <table class="table table-bordered mb-0">
                            <tr>
                                <th class="text-start">Plan</th>
                                <td class="text-start"><?= esc($payment->plan_name ?? 'Subscription Plan') ?></td>
                            </tr>
                            <tr>
                                <th class="text-start">Amount</th>
                                <td class="text-start">
                                    <?= esc($payment->currency ?? 'USD') ?>
                                    <?= number_format((float) ($payment->amount ?? 0), 2) ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-start">Payment Method</th>
                                <td class="text-start"><?= esc(ucfirst($payment->gateway ?? '-')) ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="d-flex gap-2 justify-content-center">
            <a href="<?= site_url('school-owner/dashboard') ?>" class="btn btn-primary">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <?php if (!$isPaid && $payment): ?>
                <a href="<?= site_url('school-owner/subscriptions/renew/paypal/success/' . $payment->payment_token) ?>" class="btn btn-outline-primary">
                    <i class="bi bi-credit-card"></i> Verify Payment
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>
