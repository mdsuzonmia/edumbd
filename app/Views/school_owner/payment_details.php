<?= get_system_message(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-file-earmark-text"></i> Payment Details</h3>
        <p class="text-muted mb-0">Review the payment transaction information for this record.</p>
    </div>
    <a href="<?= site_url('school-owner/payments') ?>" class="btn btn-outline-secondary">Back to Payments</a>
</div>

<?php if (empty($payment)): ?>
    <div class="alert alert-warning">Payment not found.</div>
<?php else: ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5 class="mb-2">Payment #<?= esc($payment->transaction_id) ?></h5>
                    <p class="text-muted mb-1">Plan: <strong><?= esc($payment->plan_name ?: '—') ?></strong></p>
                    <p class="text-muted mb-1">Amount: <strong><?= esc($payment->currency ?: 'USD') ?> <?= number_format((float) ($payment->amount ?? 0), 2) ?></strong></p>
                    <p class="text-muted mb-1">Billing Cycle: <strong><?= esc($payment->billing_cycle ?: '—') ?></strong></p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="text-muted mb-1">Status: 
                        <span class="badge text-bg-<?= esc(strtolower($payment->status) === 'paid' ? 'success' : 'secondary') ?>"><?= esc(ucfirst($payment->status ?? '—')) ?></span>
                    </p>
                    <p class="text-muted mb-1">Gateway: <strong><?= esc(ucfirst($payment->gateway ?? $payment->payment_gateway ?? '-')) ?></strong></p>
                    <p class="text-muted mb-1">Paid At: <strong><?= ! empty($payment->paid_at) ? esc(date('d M, Y h:i A', strtotime($payment->paid_at))) : '—' ?></strong></p>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-md-2 g-3">
                <div class="col">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-secondary">Transaction</h6>
                        <p class="mb-1"><strong>Transaction ID</strong></p>
                        <p class="text-break"><?= esc($payment->transaction_id ?: '—') ?></p>
                        <p class="mb-1"><strong>Gateway Payment ID</strong></p>
                        <p class="text-break"><?= esc($payment->gateway_payment_id ?: '—') ?></p>
                    </div>
                </div>
                <div class="col">
                    <div class="border rounded p-3 h-100">
                        <h6 class="text-secondary">Record Info</h6>
                        <p class="mb-1"><strong>Created At</strong></p>
                        <p><?= esc(date('d M, Y h:i A', strtotime($payment->created_at))) ?></p>
                        <p class="mb-1"><strong>Last Updated</strong></p>
                        <p><?= ! empty($payment->updated_at) ? esc(date('d M, Y h:i A', strtotime($payment->updated_at))) : '—' ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
