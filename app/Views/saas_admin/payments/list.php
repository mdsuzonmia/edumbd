<?php

$show_list_array = [
    '' => lang('Student.show_list'),
    '10' => '10',
    '50' => '50',
    '100' => '100',
    '200' => '200',
    '500' => '500',
    '1000' => '1000',
];

$status_array = [
    '' => lang('Payment.filter_status'),
    'pending' => lang('Payment.status_pending'),
    'paid' => lang('Payment.status_paid'),
    'failed' => lang('Payment.status_failed'),
    'cancelled' => lang('Payment.status_cancelled'),
];

$gateway_array = [
    '' => lang('Payment.filter_gateway'),
    'manual' => 'Manual',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
];

$show = isset($show) ? $show : '';
$status = isset($status) ? $status : '';
$gateway = isset($gateway) ? $gateway : '';
$text = isset($text) ? $text : '';

$show_list = form_dropdown('show', $show_list_array, $show, ['id' => 'field_show_list', 'class' => 'form-control mb-3 mt-0 filter-select pull-right']);
$status_list = form_dropdown('status', $status_array, $status, ['id' => 'field_status', 'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select']);
$gateway_list = form_dropdown('gateway', $gateway_array, $gateway, ['id' => 'field_gateway', 'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select']);

$status_badges = [
    'pending' => 'badge text-bg-warning',
    'paid' => 'badge text-bg-success',
    'failed' => 'badge text-bg-danger',
    'cancelled' => 'badge text-bg-dark',
];

?>

<?= form_open('saas-admin/payments', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'payment_search',
    'method' => 'get',
    'data-parsley-validate' => '',
]); ?>

<div class="row">
    <div class="col-sm-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-credit-card"></i> <?= lang('Payment.heading_list'); ?></h3>
    </div>

    <div class="col-sm-9 text-end pt-0">
        <div class="row g-2 justify-content-end">
            <div class="col-auto">
                <div class="input-group">
                    <input type="text" id="search_text" name="text" value="<?= esc($text) ?>" class="form-control" placeholder="Search for...">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> <?= lang('Common.btn_search') ?></button>
                    <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i> <?= lang('Common.btn_reset') ?></button>
                </div>
            </div>
            <div class="col-auto"><?= $gateway_list; ?></div>
            <div class="col-auto"><?= $status_list; ?></div>
            <div class="col-auto"><?= $show_list; ?></div>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>

                <table id="table" class="table student-table student-list">
                    <thead>
                        <tr>
                            <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th>
                            <th><?= lang('Payment.th_transaction_id') ?></th>
                            <th><?= lang('Payment.th_plan') ?></th>
                            <th class="text-center"><?= lang('Payment.th_gateway') ?></th>
                            <th class="text-center"><?= lang('Payment.th_amount') ?></th>
                            <th class="text-center"><?= lang('Payment.th_status') ?></th>
                            <th class="text-center"><?= lang('Payment.th_paid_at') ?></th>
                            <th width="120px" class="text-center"><?= lang('Common.th_action') ?></th>
                            <th width="50px" class="text-end"><?= lang('Payment.th_id') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $item): ?>
                            <?php
                            $payment_id = $item->id;
                            $gateway_name = $item->gateway ?? '';
                            $status_value = strtolower((string) $item->status);
                            $status_class = $status_badges[$status_value] ?? 'badge text-bg-secondary';
                            $status_label = $status_array[$status_value] ?? ucfirst($status_value);
                            $amount = number_format((float) $item->amount, 2) . ' ' . esc($item->currency);
                            $paid_at = $item->paid_at ? date('d M, Y', strtotime($item->paid_at)) : '-';
                            ?>
                            <tr>
                                <td class="text-left pl-0"><?= ++$key ?></td>
                                <td><?= esc($item->transaction_id ?: '-') ?></td>
                                <td><?= esc($item->plan_name ?: '-') ?></td>
                                <td class="text-center"><?= esc(ucfirst($gateway_name)) ?></td>
                                <td class="text-center"><?= $amount ?></td>
                                <td class="text-center"><span class="<?= esc($status_class) ?>"><?= esc($status_label) ?></span></td>
                                <td class="text-center"><?= esc($paid_at) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('saas-admin/payments/view/' . $payment_id) ?>" class="edit btn btn-sm btn-info mb-1" title="<?= lang('Common.text_view') ?>"><i class="fa fa-eye"></i> <?= lang('Common.text_view') ?></a>
                                </td>
                                <td class="text-end"><?= esc($payment_id) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="row">
                    <div class="col-sm-6">
                        <p>Showing <?= $pager->getCurrentPage() ?> to <?= $pager->getPerPage() ?> of <?= $pager->getTotal() ?> records.</p>
                    </div>
                    <div class="col-sm-6 text-right">
                        <?php if ($pager->getTotal() > $pager->getPerPage() && ! empty($pager->getTotal())): ?>
                            <?= $pager->links('default', $pagerTemplate); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $(document).on('click', '.clear', function(e) {
        $("#search_text").val('');
        $("#field_status").val('');
        $("#field_gateway").val('');
        $("#field_show_list").val('');
        $('#payment_search').submit();
    });

    $('#field_status, #field_gateway, #field_show_list').on('change', function() {
        $('#payment_search').submit();
    });
});
</script>
