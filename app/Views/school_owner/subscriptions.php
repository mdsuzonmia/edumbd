<?= get_system_message(); ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="text-secondary mb-1"><i class="bi bi-credit-card"></i> Subscriptions</h3>
        <p class="text-muted mb-0">View and manage your active and past subscriptions.</p>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Plan</th>
                        <th class="text-center">Billing Cycle</th>
                        <th class="text-center">Amount</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Start Date</th>
                        <th class="text-center">End Date</th>
                        <th class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (! empty($subscriptions)): ?>
                        <?php $counter = 0; ?>
                        <?php foreach ($subscriptions as $subscription): ?>
                            <?php
                            $statusLabels = [
                                0 => ['Pending', 'warning'],
                                1 => ['Trial', 'info'],
                                2 => ['Active', 'success'],
                                3 => ['Suspended', 'warning'],
                                4 => ['Expired', 'danger'],
                                5 => ['Cancelled', 'secondary'],
                                6 => ['Trashed', 'danger'],
                            ];
                            $status = $statusLabels[(int) $subscription->status] ?? [ucfirst($subscription->status ?? '—'), 'secondary'];

                            if($subscription->is_trial) {
                                $startDate = ! empty($subscription->trial_start) ? date('d M, Y', strtotime($subscription->trial_start)) : '—';
                                $endDate = ! empty($subscription->trial_end) ? date('d M, Y', strtotime($subscription->trial_end)) : '—';
                            }else{
                                $startDate = ! empty($subscription->start_date) ? date('d M, Y', strtotime($subscription->start_date)) : '—';
                                $endDate = ! empty($subscription->end_date) ? date('d M, Y', strtotime($subscription->end_date)) : 'Lifetime';
                            }

                            ?>
                            <tr>
                                <td><?= ++$counter ?></td>
                                <td><?= esc($subscription->plan_name ?: 'Trial Plan') ?></td>
                                <td class="text-center"><?= esc(ucfirst($subscription->billing_cycle ?? '—')) ?></td>
                                <td class="text-center"><?= esc($subscription->currency ?: 'USD') ?> <?= number_format((float) ($subscription->amount ?? 0), 2) ?></td>
                                <td class="text-center">
                                    <span class="badge text-bg-<?= esc($status[1]) ?>"><?= esc($status[0]) ?></span>
                                </td>
                                <td class="text-center"><?= esc($startDate) ?></td>
                                <td class="text-center"><?= esc($endDate) ?></td>
                                <td class="text-end">
                                    <?php if (! empty($subscription->token)): ?>
                                        <a href="<?= site_url('school-owner/subscriptions/view/' . $subscription->token) ?>" class="btn btn-sm btn-info">View</a>
                                    <?php else: ?>
                                        <!-- Get Upgrade link -->
                                        <a href="<?= site_url('school-owner/plans/upgrade') ?>" class="btn btn-sm btn-primary">Upgrade</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">No subscriptions found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
