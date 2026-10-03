<?php
echo get_system_message();

$statusLabels = [
    0 => ['Pending', 'secondary'],
    1 => ['Trial', 'info'],
    2 => ['Active', 'success'],
    3 => ['Suspended', 'warning'],
    4 => ['Expired', 'danger'],
    5 => ['Cancelled', 'dark'],
    6 => ['Trashed', 'danger'],
];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-clock-history"></i> Subscription Plan History</h3>
        <p class="text-muted mb-0">Review plan changes for your school.</p>
    </div>
    <a href="<?= site_url('school-owner/plans/upgrade') ?>" class="btn btn-primary">
        <i class="bi bi-box-arrow-up-right"></i> Upgrade Plan
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Plan</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">Billing Cycle</th>
                        <th class="text-center">Start Date</th>
                        <th class="text-center">End Date</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Changed At</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($items)): ?>
                        <?php foreach ($items as $item): ?>
                            <?php
                            $status = $statusLabels[(int) $item->status] ?? [$item->status, 'secondary'];
                            ?>
                            <tr>
                                <td>
                                    <strong><?= esc($item->plan_name ?? 'Trial Plan') ?></strong>
                                    <?php if (!empty($item->payment_gateway)): ?>
                                        <div class="small text-muted"><?= esc(ucfirst($item->payment_gateway)) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <?= esc($item->currency ?? 'USD') ?>
                                    <?= number_format((float) ($item->amount ?? 0), 2) ?>
                                </td>
                                <td class="text-center"><?= esc(ucfirst($item->billing_cycle ?? '-')) ?></td>
                                <td class="text-center">
                                    <?= !empty($item->start_date) ? esc(date('d M, Y', strtotime($item->start_date))) : '-' ?>
                                </td>
                                <td class="text-center">
                                    <?= !empty($item->end_date) ? esc(date('d M, Y', strtotime($item->end_date))) : 'Lifetime' ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= esc($status[1]) ?>"><?= esc($status[0]) ?></span>
                                </td>
                                <td class="text-end">
                                    <?= !empty($item->created_at) ? esc(date('d M, Y h:i A', strtotime($item->created_at))) : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No subscription history found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
