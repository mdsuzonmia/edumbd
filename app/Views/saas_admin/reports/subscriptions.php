<?php
$status_options = [
    '' => lang('Report.filter_status'),
    '0' => lang('Subscription.status_pending'),
    '1' => lang('Subscription.status_trial'),
    '2' => lang('Subscription.status_active'),
    '3' => lang('Subscription.status_suspended'),
    '4' => lang('Subscription.status_expired'),
    '5' => lang('Subscription.status_cancelled'),
    '6' => lang('Subscription.status_trashed'),
];
$billing_options = [
    '' => lang('Report.filter_billing_cycle'),
    'monthly' => lang('Subscription.monthly'),
    'yearly' => lang('Subscription.yearly'),
    'lifetime' => lang('Subscription.lifetime'),
];
$status = isset($status) ? $status : '';
$billing_cycle = isset($billing_cycle) ? $billing_cycle : '';
?>

<?= form_open('saas-admin/reports/subscriptions', ['id' => 'subscription_report_filter', 'method' => 'get']); ?>
<div class="row mb-3">
    <div class="col-sm-4">
        <h3 class="text-secondary mb-0"><i class="bi bi-repeat"></i> <?= lang('Report.heading_subscriptions'); ?></h3>
    </div>
    <div class="col-sm-8 text-end">
        <div class="row g-2 justify-content-end">
            <div class="col-auto"><?= form_dropdown('billing_cycle', $billing_options, $billing_cycle, ['id' => 'field_billing_cycle', 'class' => 'form-control']); ?></div>
            <div class="col-auto"><?= form_dropdown('status', $status_options, $status, ['id' => 'field_status', 'class' => 'form-control']); ?></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?= lang('Common.btn_search') ?></button></div>
            <div class="col-auto"><a href="<?= base_url('saas-admin/reports') ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> <?= lang('Report.back_to_reports') ?></a></div>
        </div>
    </div>
</div>
<?= form_close(); ?>

<div class="card">
    <div class="card-body">
        <table class="table student-table student-list">
            <thead>
                <tr>
                    <th><?= lang('Common.th_sn') ?></th>
                    <th><?= lang('Report.th_school') ?></th>
                    <th><?= lang('Report.th_plan') ?></th>
                    <th><?= lang('Report.th_status') ?></th>
                    <th><?= lang('Report.th_billing_cycle') ?></th>
                    <th><?= lang('Report.th_amount') ?></th>
                    <th><?= lang('Report.th_start_date') ?></th>
                    <th><?= lang('Report.th_end_date') ?></th>
                    <th><?= lang('Report.th_trial_end_date') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $key => $item): ?>
                    <tr>
                        <td><?= ++$key ?></td>
                        <td>
                            <?= esc($item->school_name ?: '-') ?>
                            <?php if (! empty($item->school_email)): ?>
                                <br><small class="text-muted"><?= esc($item->school_email) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= esc($item->plan_name ?: '-') ?></td>
                        <td><?= esc($status_options[(string) $item->status] ?? $item->status) ?></td>
                        <td><?= esc(ucfirst($item->billing_cycle ?: '-')) ?></td>
                        <td><?= esc(number_format((float) $item->amount, 2) . ' ' . $item->currency) ?></td>
                        <td><?= esc($item->start_date ?: '-') ?></td>
                        <td><?= esc($item->end_date ?: '-') ?></td>
                        <td><?= esc($item->trial_end_date ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
