<?= get_system_message() ?>
<?php
$paymentSettings = $paymentSettings ?? [];
$paymentMethod = trim((string) ($paymentSettings['payment_method_name'] ?? ''));
$accountNumber = trim((string) ($paymentSettings['payment_account_number'] ?? ''));
$accountType = trim((string) ($paymentSettings['payment_account_type'] ?? ''));
$instructions = trim((string) ($paymentSettings['payment_instructions'] ?? ''));
$hasPaymentDestination = $paymentMethod !== '' || $accountNumber !== '' || $instructions !== '';
?>
<div class="card border-0 shadow-sm" style="max-width:700px;margin:auto">
    <div class="card-body p-4">
        <h2 class="fw-bold mb-4">পেমেন্টের সারসংক্ষেপ</h2>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>ইনভয়েস</span><strong><?= esc($order->invoice_no) ?></strong></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>সেবা</span><strong><?= esc($order->service) ?></strong></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>শিক্ষার্থী</span><strong><?= number_format($order->student_count) ?> জন</strong></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span>প্রযোজ্য হার</span><strong>৳<?= number_format($order->applied_rate, 2) ?></strong></div>
        <div class="d-flex justify-content-between py-3 fs-4"><span>মোট পরিশোধযোগ্য</span><strong>৳<?= number_format($order->total, 2) ?></strong></div>

        <?php if (in_array($order->status, ['draft', 'payment_pending'], true)): ?>
            <?php if ($hasPaymentDestination): ?>
                <div class="card border-primary bg-light mb-4">
                    <div class="card-body">
                        <h5 class="text-primary fw-bold mb-3"><i class="bi bi-send-check me-1"></i> যেখানে পেমেন্ট করবেন</h5>
                        <?php if ($paymentMethod !== ''): ?><div class="mb-2"><span class="text-muted">মাধ্যম:</span> <strong><?= esc($paymentMethod) ?></strong></div><?php endif ?>
                        <?php if ($accountNumber !== ''): ?><div class="mb-2"><span class="text-muted">অ্যাকাউন্ট নম্বর:</span> <strong class="fs-5 user-select-all"><?= esc($accountNumber) ?></strong></div><?php endif ?>
                        <?php if ($accountType !== ''): ?><div class="mb-2"><span class="text-muted">অ্যাকাউন্টের ধরন/নাম:</span> <strong><?= esc($accountType) ?></strong></div><?php endif ?>
                        <?php if ($instructions !== ''): ?><div class="mt-3 pt-3 border-top" style="white-space:pre-line"><?= esc($instructions) ?></div><?php endif ?>
                        <div class="mt-3 text-danger fw-semibold">ঠিক ৳<?= number_format($order->total, 2) ?> পাঠিয়ে নিচে Transaction ID দিন।</div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>পেমেন্টের মাধ্যম এখনো সেট করা হয়নি।</strong><br>
                    পেমেন্ট পাঠানোর আগে সিস্টেম অ্যাডমিনের সঙ্গে যোগাযোগ করুন।
                </div>
            <?php endif ?>

            <?= form_open('school-owner/billing/manual/' . $order->token) ?>
                <label class="form-label fw-bold">Transaction ID</label>
                <input class="form-control form-control-lg mb-3" name="transaction_id" value="<?= esc($order->transaction_id ?? '') ?>" required <?= $hasPaymentDestination ? '' : 'disabled' ?>>
                <label class="form-label">নোট (ঐচ্ছিক)</label>
                <textarea class="form-control mb-3" name="payment_note" <?= $hasPaymentDestination ? '' : 'disabled' ?>><?= esc($order->payment_note ?? '') ?></textarea>
                <button class="btn btn-primary btn-lg w-100" <?= $hasPaymentDestination ? '' : 'disabled' ?>>পেমেন্ট তথ্য পাঠান</button>
            <?= form_close() ?>
            <p class="small text-muted mt-3 mb-0">অ্যাডমিন পেমেন্ট যাচাই না করা পর্যন্ত final output বন্ধ থাকবে।</p>
        <?php else: ?>
            <div class="alert alert-success">এই বিলটি <?= esc($order->status) ?>।</div>
            <?php if (! empty($target)): ?><a class="btn btn-success btn-lg w-100" href="<?= site_url($target) ?>">Final সেবা খুলুন</a><?php endif ?>
        <?php endif ?>
    </div>
</div>
