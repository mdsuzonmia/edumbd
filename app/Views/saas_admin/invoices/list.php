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

$gateway_array = [
    '' => lang('Invoice.filter_gateway'),
    'manual' => 'Manual',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
];

$show = isset($show) ? $show : '';
$gateway = isset($gateway) ? $gateway : '';
$text = isset($text) ? $text : '';

$show_list = form_dropdown('show', $show_list_array, $show, ['id' => 'field_show_list', 'class' => 'form-control mb-3 mt-0 filter-select pull-right']);
$gateway_list = form_dropdown('gateway', $gateway_array, $gateway, ['id' => 'field_gateway', 'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select']);

?>

<?= form_open('saas-admin/invoices', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'invoice_search',
    'method' => 'get',
    'data-parsley-validate' => '',
]); ?>

<div class="row">
    <div class="col-sm-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-receipt"></i> <?= lang('Invoice.heading_list'); ?></h3>
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
                            <th><?= lang('Invoice.th_invoice_no') ?></th>
                            <th><?= lang('Invoice.th_school') ?></th>
                            <th><?= lang('Invoice.th_plan') ?></th>
                            <th class="text-center"><?= lang('Invoice.th_amount') ?></th>
                            <th class="text-center"><?= lang('Invoice.th_gateway') ?></th>
                            <th class="text-center"><?= lang('Invoice.th_paid_at') ?></th>
                            <th width="120px" class="text-center"><?= lang('Common.th_action') ?></th>
                            <th width="50px" class="text-end"><?= lang('Invoice.th_id') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $item): ?>
                            <?php
                            $invoice_id = $item->id;
                            $gateway_name = $item->gateway ?? '';
                            $amount = number_format((float) $item->amount, 2) . ' ' . esc($item->currency);
                            $paid_at = $item->paid_at ? date('d M, Y', strtotime($item->paid_at)) : '-';
                            ?>
                            <tr>
                                <td class="text-left pl-0"><?= ++$key ?></td>
                                <td><b><?= esc($InvoiceModel->invoiceNumber($item)) ?></b></td>
                                <td>
                                    <p class="mb-0"><b class="title"><?= esc($item->school_name) ?></b></p>
                                    <?php if (! empty($item->school_email)): ?>
                                        <p class="mb-0"><small class="text-muted"><?= esc($item->school_email) ?></small></p>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($item->plan_name ?: '-') ?></td>
                                <td class="text-center"><?= $amount ?></td>
                                <td class="text-center"><?= esc(ucfirst($gateway_name)) ?></td>
                                <td class="text-center"><?= esc($paid_at) ?></td>
                                <td class="text-center">
                                    <a href="<?= base_url('saas-admin/invoices/view/' . $invoice_id) ?>" class="edit btn btn-sm btn-info mb-1" title="<?= lang('Common.text_view') ?>"><i class="fa fa-eye"></i> <?= lang('Common.text_view') ?></a>
                                </td>
                                <td class="text-end"><?= esc($invoice_id) ?></td>
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
        $("#field_gateway").val('');
        $("#field_show_list").val('');
        $('#invoice_search').submit();
    });

    $('#field_gateway, #field_show_list').on('change', function() {
        $('#invoice_search').submit();
    });
});
</script>
