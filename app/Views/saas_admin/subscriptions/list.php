<?php

$subscription_status_array = [
    ''  => lang('Subscription.select_status'),
    '0' => lang('Subscription.status_pending'),
    '1' => lang('Subscription.status_trial'),
    '2' => lang('Subscription.status_active'),
    '3' => lang('Subscription.status_suspended'),
    '4' => lang('Subscription.status_expired'),
    '5' => lang('Subscription.status_cancelled'),
    '6' => lang('Subscription.status_trashed'),
];

$billing_cycle_array = [
    '' => lang('Subscription.select_billing_cycle'),
    'monthly' => lang('Subscription.monthly'),
    'yearly' => lang('Subscription.yearly'),
    'lifetime' => lang('Subscription.lifetime'),
];

$show_list_array = [
    '' => lang('Student.show_list'),
    '10' => '10',
    '50' => '50',
    '100' => '100',
    '200' => '200',
    '500' => '500',
    '1000' => '1000',
];

$show = isset($show) ? $show : '';
$status = isset($status) ? $status : '';
$billing_cycle = isset($billing_cycle) ? $billing_cycle : '';
$text = isset($text) ? $text : '';

$show_list = form_dropdown('show', $show_list_array, $show, [
    'id' => 'field_show_list',
    'class' => 'form-control mb-3 mt-0 filter-select pull-right',
]);

$status_list = form_dropdown('status', $subscription_status_array, $status, [
    'id' => 'field_status',
    'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select',
]);

$billing_cycle_list = form_dropdown('billing_cycle', $billing_cycle_array, $billing_cycle, [
    'id' => 'field_billing_cycle',
    'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select',
]);

$status_badges = [
    '0' => 'badge text-bg-warning',
    '1' => 'badge text-bg-info',
    '2' => 'badge text-bg-success',
    '3' => 'badge text-bg-secondary',
    '4' => 'badge text-bg-danger',
    '5' => 'badge text-bg-dark',
    '6' => 'badge text-bg-danger',
];

?>

<?= form_open('saas-admin/subscriptions', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'subscription_search',
    'method' => 'get',
    'data-parsley-validate' => '',
]); ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="row">
    <div class="col-sm-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-repeat"></i> <?= lang('Subscription.heading_list'); ?></h3>
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
            <div class="col-auto"><?= $billing_cycle_list; ?></div>
            <div class="col-auto"><?= $status_list; ?></div>
            <div class="col-auto"><?= $show_list; ?></div>
            <div class="col-auto">
                <a href="<?= base_url('saas-admin/subscriptions/create') ?>" class="edit btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('Subscription.btn_add_new') ?></a>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="row">
    <div class="col-md-12 col-sm-12">
        <div class="card">
            <div class="card-body">
                <?= get_system_message(); ?>
                <div id="result"></div>

                <table id="table" class="table student-table student-list">
                    <thead>
                        <tr>
                            <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th>
                            <th><?= lang('Subscription.th_user') ?></th>
                            <th><?= lang('Subscription.th_plan') ?></th>
                            <th class="text-center"><?= lang('Subscription.th_amount') ?></th>
                            <th class="text-center"><?= lang('Subscription.th_billing_cycle') ?></th>
                            <th class="text-center"><?= lang('Subscription.th_start_date') ?></th>
                            <th class="text-center"><?= lang('Subscription.th_end_date') ?></th>
                            <th class="text-center"><?= lang('Subscription.th_status') ?></th>
                            <th width="220px" class="text-center"><?= lang('Common.th_action') ?></th>
                            <th width="50px" class="text-end"><?= lang('Subscription.th_id') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $key => $item): ?>
                            <?php
                            $subscription_id = $item->id;
                            $status_value = (string) $item->status;
                            $status_label = $subscription_status_array[$status_value] ?? $item->status;
                            $status_class = $status_badges[$status_value] ?? 'badge text-bg-secondary';
                            $amount = number_format((float) $item->amount, 2) . ' ' . esc($item->currency);
                            $start_date = $item->start_date ? date('d M, Y', strtotime($item->start_date)) : '';
                            $end_date = $item->end_date ? date('d M, Y', strtotime($item->end_date)) : '-';
                            ?>
                            <tr id="item_<?= esc($subscription_id) ?>">
                                <td class="text-left pl-0"><?= ++$key ?></td>
                                <td>
                                    <strong class="text-primary"><?= esc($item->user_name) ?></strong><br>
                                    <small class="text-muted"><?= esc($item->user_email) ?></small>
                                    
                                </td>
                                <td><?= esc($item->plan_name) ?></td>
                                <td class="text-center"><?= $amount ?></td>
                                <td class="text-center"><?= esc(ucfirst($item->billing_cycle)) ?></td>
                                <td class="text-center"><?= esc($start_date) ?></td>
                                <td class="text-center"><?= esc($end_date) ?></td>
                                <td class="text-center">
                                    <span class="<?= esc($status_class) ?> mb-1 d-inline-block"><?= esc($status_label) ?></span>
                                    <?= form_dropdown('row_status_' . $subscription_id, $subscription_status_array, $status_value, [
                                        'class' => 'form-control form-control-sm change_status',
                                        'data-id' => $subscription_id,
                                    ]); ?>
                                </td>
                                <td class="text-center pr-0">
                                    <a href="<?= base_url('saas-admin/subscriptions/view/' . $subscription_id) ?>" class="edit btn btn-sm btn-info mb-1" title="<?= lang('Common.text_view') ?>"><i class="fa fa-eye"></i> <?= lang('Common.text_view') ?></a>

                                    <?php if ((int) $item->status === 2): ?>
                                        <button type="button" data-id="<?= esc($subscription_id) ?>" class="cancel btn btn-sm btn-warning mb-1" title="<?= lang('Subscription.btn_cancel') ?>"><i class="fa fa-ban"></i> <?= lang('Subscription.btn_cancel') ?></button>
                                    <?php endif; ?>

                                    <?php if ((int) $item->status !== 6): ?>
                                        <button type="button" data-id="<?= esc($subscription_id) ?>" class="trash btn btn-sm btn-danger mb-1" title="<?= lang('Common.text_trash') ?>"><i class="fa fa-trash"></i> <?= lang('Common.text_trash') ?></button>
                                    <?php endif; ?>

                                    <?php if ((int) $item->status === 6): ?>
                                        <button type="button" data-id="<?= esc($subscription_id) ?>" class="restore btn btn-sm btn-success mb-1" title="<?= lang('Common.text_restore') ?>"><i class="fa fa-check-circle"></i> <?= lang('Common.text_restore') ?></button>
                                        <button type="button" data-id="<?= esc($subscription_id) ?>" class="empty_trash btn btn-sm btn-danger mb-1" title="<?= lang('Common.empty_trash') ?>"><i class="fa fa-trash"></i> <?= lang('Common.empty_trash') ?></button>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= esc($subscription_id) ?></td>
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
        $("#field_billing_cycle").val('');
        $("#field_show_list").val('');
        $('#subscription_search').submit();
    });

    $('#field_status, #field_billing_cycle, #field_show_list').on('change', function() {
        $('#subscription_search').submit();
    });

    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });

    function postAction(url, data, currentRow, warningMessage) {
        var confirmation = confirm(warningMessage);
        if (confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

            $.ajax({
                type: "post",
                dataType: "json",
                url: url,
                data: data,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if (response.status == true) {
                        currentRow.parents('tr').remove();
                    }
                }
            });
        }
    }

    $(document).on('click', '.trash', function(e) {
        postAction('<?= base_url('saas-admin/subscriptions/trash'); ?>', { subscription_id: $(this).data("id") }, $(this), '<?= lang('Common.trash_warning_message') ?>');
    });

    $(document).on('click', '.empty_trash', function(e) {
        postAction('<?= base_url('saas-admin/subscriptions/empty-trash'); ?>', { subscription_id: $(this).data("id") }, $(this), '<?= lang('Common.empty_trash_warning_message') ?>');
    });

    $(document).on('click', '.restore', function(e) {
        postAction('<?= base_url('saas-admin/subscriptions/restore'); ?>', { subscription_id: $(this).data("id") }, $(this), '<?= lang('Common.restore_warning_message') ?>');
    });

    $(document).on('click', '.cancel', function(e) {
        var id = $(this).data("id");
        postAction('<?= base_url('saas-admin/subscriptions/cancel'); ?>/' + id, {}, $(this), '<?= lang('Common.delete_warning_message') ?>');
    });

    $(document).on('change', '.change_status', function(e) {
        var id = $(this).data("id");
        var status = $(this).val();
        var confirmation = confirm('<?= lang('Subscription.change_status') ?>?');

        if (confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

            $.ajax({
                type: "post",
                dataType: "json",
                url: '<?= base_url('saas-admin/subscriptions/status'); ?>/' + id,
                data: {
                    status: status
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);

                    if (response.status == true) {
                        location.reload();
                    }
                }
            });
        }
    });
});
</script>
