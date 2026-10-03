<?php
$gateway_options = [
    '' => lang('Report.filter_gateway'),
    'manual' => 'Manual',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
];
$date_from = isset($date_from) ? $date_from : '';
$date_to = isset($date_to) ? $date_to : '';
$gateway = isset($gateway) ? $gateway : '';
?>

<?= form_open('saas-admin/reports/revenue', ['id' => 'revenue_report_filter', 'method' => 'get']); ?>
<div class="row mb-3">
    <div class="col-sm-4">
        <h3 class="text-secondary mb-0"><i class="bi bi-cash-stack"></i> <?= lang('Report.heading_revenue'); ?></h3>
    </div>
    <div class="col-sm-8 text-end">
        <div class="row g-2 justify-content-end">
            <div class="col-auto"><input type="date" name="date_from" value="<?= esc($date_from) ?>" class="form-control"></div>
            <div class="col-auto"><input type="date" name="date_to" value="<?= esc($date_to) ?>" class="form-control"></div>
            <div class="col-auto"><?= form_dropdown('gateway', $gateway_options, $gateway, ['class' => 'form-control']); ?></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> <?= lang('Common.btn_search') ?></button></div>
            <div class="col-auto"><a href="<?= base_url('saas-admin/reports') ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> <?= lang('Report.back_to_reports') ?></a></div>
        </div>
    </div>
</div>
<?= form_close(); ?>

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.total_revenue') ?></h6>
                <h3><?= number_format((float) $total, 2) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <table class="table student-table student-list">
            <thead>
                <tr>
                    <th><?= lang('Common.th_sn') ?></th>
                    <th><?= lang('Report.th_school') ?></th>
                    <th><?= lang('Report.th_plan') ?></th>
                    <th><?= lang('Report.th_gateway') ?></th>
                    <th><?= lang('Report.th_amount') ?></th>
                    <th><?= lang('Report.th_transaction') ?></th>
                    <th><?= lang('Report.th_paid_at') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $key => $item): ?>
                    <tr>
                        <td><?= ++$key ?></td>
                        <td><?= esc($item->school_name ?: '-') ?></td>
                        <td><?= esc($item->plan_name ?: '-') ?></td>
                        <td><?= esc(ucfirst($item->gateway ?: '-')) ?></td>
                        <td><?= esc(number_format((float) $item->amount, 2) . ' ' . $item->currency) ?></td>
                        <td><?= esc($item->transaction_id ?: '-') ?></td>
                        <td><?= esc($item->paid_at ?: '-') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
