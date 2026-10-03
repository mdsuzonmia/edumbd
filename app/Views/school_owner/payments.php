<?= get_system_message(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-receipt"></i> Payments</h3>
        <p class="text-muted mb-0">Your school payment history and transaction records.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Payment ID</th>
                        <th>Plan</th>
                        <th class="text-center">Payment Type</th>   
                        <th class="text-center">Amount</th>
                        <th class="text-center">Gateway</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Paid At</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($payments)): ?>
                        <?php $counter = 0; ?>
                        <?php foreach ($payments as $payment): ?>
                            <?php
                            $statusLabels = [
                                'paid' => ['Paid', 'success'],
                                'pending' => ['Pending', 'warning'],
                                'failed' => ['Failed', 'danger'],
                                'cancelled' => ['Cancelled', 'secondary'],
                            ];
                            $status = $statusLabels[strtolower($payment->status)] ?? [ucfirst($payment->status), 'secondary'];
                            $paidAt = ! empty($payment->paid_at) ? date('d M, Y', strtotime($payment->paid_at)) : '-';
                            ?>
                            <tr>
                                <td><?= ++$counter ?></td>
                                <td><?= esc($payment->transaction_id) ?></td>
                                <td><?= esc($payment->plan_name ?: '—') ?></td>
                                <td class="text-center"><?= esc(ucfirst($payment->payment_type ?? '-')) ?></td>
                                <td class="text-center">
                                    <?= esc($payment->currency ?: 'USD') ?> <?= number_format((float) ($payment->amount ?? 0), 2) ?>
                                </td>
                                <td class="text-center"><?= esc(ucfirst($payment->gateway ?? $payment->payment_gateway ?? '-')) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= esc($status[1]) ?>"><?= esc($status[0]) ?></span>
                                </td>
                                <td class="text-center"><?= esc($paidAt) ?></td>
                                <td class="text-end">
                                    <?php if ($payment->status === 'pending'): ?>
                                        <?php
                                        $gateway = strtolower($payment->gateway ?? $payment->payment_gateway ?? 'stripe');
                                        $paymentUrl = '';
                                        if ($gateway === 'stripe') {
                                            $paymentUrl = site_url('school-owner/payments/stripe/retry/' . $payment->payment_token);
                                        } elseif ($gateway === 'paypal') {
                                            $paymentUrl = site_url('school-owner/payments/paypal/retry/' . $payment->payment_token);
                                        }
                                        ?>
                                        <?php if (!empty($paymentUrl)): ?>
                                            <a href="<?= $paymentUrl ?>" class="btn btn-sm btn-success">Pay Now</a>
                                        <?php else: ?>
                                            <span class="text-muted">N/A</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="<?= site_url('school-owner/payments/' . $payment->payment_token) ?>" class="btn btn-sm btn-info">View</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No payments found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
