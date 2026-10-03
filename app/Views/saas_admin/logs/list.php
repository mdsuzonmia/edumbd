<?php
$show_list_array = [
    '' => lang('Student.show_list'),
    '10' => '10',
    '25' => '25',
    '50' => '50',
    '100' => '100',
    '200' => '200',
];

$level_options = ['' => 'All Levels'];
foreach ($levels as $level_item) {
    $level_options[$level_item] = ucfirst($level_item);
}

$show = isset($show) ? $show : '';
$level = isset($level) ? $level : '';
$date = isset($date) ? $date : '';
$text = isset($text) ? $text : '';

$show_list = form_dropdown('show', $show_list_array, $show, ['id' => 'field_show_list', 'class' => 'form-control mb-3 mt-0 filter-select pull-right']);
$level_list = form_dropdown('level', $level_options, $level, ['id' => 'field_level', 'class' => 'form-control mb-3 mt-0 mr-1 pull-right filter-select']);

$level_badges = [
    'emergency' => 'badge text-bg-danger',
    'alert' => 'badge text-bg-danger',
    'critical' => 'badge text-bg-danger',
    'error' => 'badge text-bg-danger',
    'warning' => 'badge text-bg-warning',
    'notice' => 'badge text-bg-info',
    'info' => 'badge text-bg-primary',
    'debug' => 'badge text-bg-secondary',
];
?>

<?= form_open('saas-admin/logs', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'log_search',
    'method' => 'get',
    'data-parsley-validate' => '',
]); ?>

<div class="row">
    <div class="col-sm-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-shield-check"></i> Audit Logs</h3>
    </div>

    <div class="col-sm-9 text-end pt-0">
        <div class="row g-2 justify-content-end">
            <div class="col-auto">
                <div class="input-group">
                    <input type="text" id="search_text" name="text" value="<?= esc($text) ?>" class="form-control" placeholder="Search logs...">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> <?= lang('Common.btn_search') ?></button>
                    <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i> <?= lang('Common.btn_reset') ?></button>
                </div>
            </div>
            <div class="col-auto">
                <input type="date" id="field_date" name="date" value="<?= esc($date) ?>" class="form-control mb-3 mt-0 filter-select">
            </div>
            <div class="col-auto"><?= $level_list; ?></div>
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
                            <th width="170">Date</th>
                            <th width="110" class="text-center">Level</th>
                            <th>Message</th>
                            <th width="150" class="text-center">Source</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $key => $item): ?>
                                <?php
                                $badge_class = $level_badges[$item->level] ?? 'badge text-bg-secondary';
                                $serial = (($page - 1) * $perPage) + $key + 1;
                                ?>
                                <tr>
                                    <td class="text-left pl-0"><?= esc($serial) ?></td>
                                    <td><?= esc($item->logged_at) ?></td>
                                    <td class="text-center"><span class="<?= esc($badge_class) ?>"><?= esc(strtoupper($item->level)) ?></span></td>
                                    <td>
                                        <div style="white-space: pre-wrap; word-break: break-word; max-height: 180px; overflow: auto;"><?= esc($item->message) ?></div>
                                    </td>
                                    <td class="text-center"><?= esc($item->source) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted">No audit logs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>

                <div class="row">
                    <div class="col-sm-6">
                        <p>Showing <?= esc(count($items)) ?> of <?= esc($total) ?> records.</p>
                    </div>
                    <div class="col-sm-6 text-right">
                        <?= $pagination ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {
    $(document).on('click', '.clear', function() {
        $("#search_text").val('');
        $("#field_level").val('');
        $("#field_date").val('');
        $("#field_show_list").val('');
        $('#log_search').submit();
    });

    $('#field_level, #field_date, #field_show_list').on('change', function() {
        $('#log_search').submit();
    });
});
</script>
