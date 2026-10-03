<?php
$status_options = [
    '' => lang('Report.filter_status'),
    '0' => lang('Common.sys_unpublished'),
    '1' => lang('Common.sys_published'),
    '2' => lang('Common.text_trash'),
];
$status = isset($status) ? $status : '';
$status_list = form_dropdown('status', $status_options, $status, ['id' => 'field_status', 'class' => 'form-control mb-3 mt-0 filter-select pull-right']);
?>

<?= form_open('saas-admin/reports/schools', ['id' => 'school_report_filter', 'method' => 'get']); ?>
<div class="row mb-3">
    <div class="col-sm-5">
        <h3 class="text-secondary mb-0"><i class="bi bi-building"></i> <?= lang('Report.heading_schools'); ?></h3>
    </div>
    <div class="col-sm-7 text-end">
        <div class="row g-2 justify-content-end">
            <div class="col-auto"><?= $status_list ?></div>
            <div class="col-auto">
                <a href="<?= base_url('saas-admin/reports') ?>" class="btn btn-info"><i class="fa fa-arrow-left"></i> <?= lang('Report.back_to_reports') ?></a>
            </div>
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
                    <th><?= lang('Report.th_email') ?></th>
                    <th><?= lang('Report.th_phone') ?></th>
                    <th><?= lang('Report.th_plan') ?></th>
                    <th><?= lang('Report.th_subscription') ?></th>
                    <th><?= lang('Report.th_status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $key => $item): ?>
                    <tr>
                        <td><?= ++$key ?></td>
                        <td><?= esc($item->name) ?></td>
                        <td><?= esc($item->email) ?></td>
                        <td><?= esc($item->phone ?: '-') ?></td>
                        <td><?= esc($item->plan_name ?: '-') ?></td>
                        <td><?= esc($item->billing_cycle ?: '-') ?> / <?= esc($item->end_date ?: '-') ?></td>
                        <td><?= esc($status_options[(string) $item->status] ?? $item->status) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $('#field_status').on('change', function() {
        $('#school_report_filter').submit();
    });
});
</script>
