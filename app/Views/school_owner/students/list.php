<?php

$show_list_array = array(
    ''  => lang('Student.show_list'), 
    '10' => '10', 
    '50' => '50',
    '100' => '100',
    '200' => '200',
    '500' => '500',
    '1000' => '1000'
);
$show_list_data = [
    'id'    => 'field_show_list',
    'class' => 'form-control mb-3 mt-0 filter-select pull-right',
];
if(isset($show)){$show = $show;}else{$show = '';}
$show_list = form_dropdown('show', $show_list_array, $show, $show_list_data);

$status_array = get_status();
if(isset($status)){$status = $status;}else{$status = '';}
$status_list  = get_status_list(lang('Common.filter_status'), 'status', 'field_status', $status, false, true, 'mt-0 mr-1 pull-right filter-select');

if(isset($text)){$text = $text;}else{$text = '';}

$selected_school = isset($selected_school) ? $selected_school : '';
$selected_year = isset($selected_year) ? $selected_year : '';
$selected_class = isset($selected_class) ? $selected_class : '';
$year_list = $year_list ?? [];
$class_list = $class_list ?? [];
$student_statuses = $student_statuses ?? [];
$office_copy_query = http_build_query([
    'school_id' => $selected_school,
    'year_id'   => $selected_year,
    'class_id'  => $selected_class,
    'status'    => $status,
    'text'      => $text,
]);
?>

<?= form_open('school-owner/students', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'student_search', 
    'method'  => 'get', 
    'data-parsley-validate'=>'' 
    ]); 
?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />


<?php 
if (!empty($plan_status)) {
    echo $plan_status;
}
?>
    
<div class="row">
    <div class="col-sm-12 mb-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-people"></i> <?= lang('Student.heading_student_list'); ?></h3>
    </div>

    <div class="col-sm-4 text-left pt-0">
        
        <div class="row ">
            <div class="col-auto">
                <div class="input-group">
                    <input type="text" id="search_text" name="text" value="<?= $text ?>" class="form-control" placeholder="Search for...">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search" aria-hidden="true"></i> <?= lang('Common.btn_search') ?></button>
                    <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i> <?= lang('Common.btn_reset') ?></button>
                </div>
            </div>

        </div>
    </div>

    <div class="col-sm-8 ">
        <div class="row g-2 justify-content-end">

            <div class="col-auto">
                <select name="school_id" id="field_school_id" class="form-control filter-select">
                    <option value="">All Schools</option>
                    <?php if (!empty($school_list)): ?>
                        <?php foreach ($school_list as $sid => $sname): ?>
                            <option value="<?= $sid ?>" <?= $selected_school == $sid ? 'selected' : '' ?>>
                                <?= esc($sname) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-auto">
                <select name="year_id" id="field_year_id" class="form-control filter-select" <?= empty($selected_school) ? 'disabled' : '' ?>>
                    <option value="">All Years</option>
                    <?php foreach ($year_list as $yid => $ytitle): ?>
                        <option value="<?= $yid ?>" <?= $selected_year == $yid ? 'selected' : '' ?>>
                            <?= esc($ytitle) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-auto">
                <select name="class_id" id="field_class_id" class="form-control filter-select" <?= empty($selected_school) ? 'disabled' : '' ?>>
                    <option value="">All Classes</option>
                    <?php foreach ($class_list as $cid => $ctitle): ?>
                        <option value="<?= $cid ?>" <?= $selected_class == $cid ? 'selected' : '' ?>>
                            <?= esc($ctitle) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-auto"><?= $status_list; ?></div>
            <div class="col-auto"><?= $show_list; ?></div>
            <div class="col-auto">
                <a href="<?= base_url('school-owner/students/office-copy-print') . ($office_copy_query ? '?' . $office_copy_query : '') ?>" target="_blank" class="btn btn-primary">
                    <i class="fa fa-print"></i> Office Print
                </a>
            </div>
            <div class="col-auto">
                <a href="<?= base_url('school-owner/students/office-copy-pdf') . ($office_copy_query ? '?' . $office_copy_query : '') ?>" target="_blank" class="btn btn-danger">
                    <i class="fa fa-file-pdf-o"></i> Office PDF
                </a>
            </div>
            <div class="col-auto">
                <a href="<?= base_url('school-owner/students/create') ?>" class="edit btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('Student.btn_add_new') ?></a>
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

                <table id="table" class="table student-table">
                    <thead>
                        <tr>
                            <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th>
                            <th></th>
                            <th><?= lang('Student.field_name') ?></th>
                            <th><?= lang('Student.student_id') ?></th>
                            <th><?= lang('Student.field_phone') ?></th>
                            <th><?= lang('Student.field_email') ?></th>
                            <th><?= lang('Student.student_status') ?></th>
                            <th class="text-center"><?= lang('Common.th_status') ?></th>
                            <th width="300px" class="text-center"><?= lang('Common.th_action') ?></th>
                            <th width="50px" class="text-end" style="display: none;"><?= lang('Student.field_database_id') ?></th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php 
                        foreach ($items as $key => $item) {
                            $student_id   = $item->id;
                            $full_name    = $item->first_name;
                            if ($item->middle_name) $full_name .= ' ' . $item->middle_name;
                            if ($item->last_name) $full_name .= ' ' . $item->last_name;
                            $student_code = $item->student_code;
                            $phone        = $item->phone;
                            $email        = $item->email;
                            $photo        = $item->photo;
                            $created_at   = date('d M, Y', strtotime($item->created_at));

                            if ($photo != null) {
                                $photo_path = base_url('uploads/' . esc($photo));
                            } else {
                                $photo_path = base_url('uploads/default.png');
                            }

                            $edit_link = base_url('school-owner/students/edit/' . $item->token);
                            $view_link = base_url('school-owner/students/view/' . $item->token);

                            echo '<tr id="item_' . $item->id . '">
                                <td class="text-left pl-0">' . ++$key . '</td>
                                <td class="pl-0" width="70px">
                                    <img src="' . $photo_path . '" class="img-fluid rounded-circle" style="width: 50px; height: 50px;" alt="' . esc($full_name) . '" />
                                </td>
                                <td class="pl-0">
                                    <p class="mb-0"><b class="title">' . esc($full_name) . '</b></p>
                                    <small class="text-muted">Created: ' . $created_at . '</small>
                                </td>
                                <td>' . esc($student_code) . '</td>
                                <td>' . esc($phone ?: '-') . '</td>
                                <td>' . esc($email ?: '-') . '</td>
                                <td>' . esc($item->student_status ?? 'Active') . '</td>';

                                $status_html = '';
                                $restore_btn = '';
                                $empty_trash_btn = '';
                                $trash_btn = '';
                                if ($item->status == 0) {
                                    $status_html .= '<span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> ' . $status_array[$item->status] . '</span>';
                                }
                                if ($item->status == 1) {
                                    $status_html .= '<span class="badge text-bg-success"><i class="fa fa-check-circle"></i> ' . $status_array[$item->status] . '</span>';
                                $trash_btn .= '<button type="button" data-id="' . $item->token . '" class="trash btn btn-sm btn-danger mb-1" title="' . lang('Common.text_trash') . '"><i class="fa fa-trash"></i> ' . lang('Common.text_trash') . '</button>';
                                }
                                if ($item->status == 2) {
                                    $status_html .= '<span class="badge text-bg-danger"><i class="fa fa-ban"></i> ' . $status_array[$item->status] . '</span>';
                                    $restore_btn .= '<button type="button" data-id="' . $item->token . '" class="restore btn btn-sm btn-success mb-1" title="' . lang('Common.text_restore') . '"><i class="fa fa-check-circle"></i> ' . lang('Common.text_restore') . '</button>';
                                    $empty_trash_btn .= '<button type="button" data-id="' . $item->token . '" class="empty_trash btn btn-sm btn-danger mb-1" title="' . lang('Common.empty_trash') . '"><i class="fa fa-trash"></i> ' . lang('Common.empty_trash') . '</button>';
                                }

                                echo '<td class="text-center">' . $status_html . '</td>';
                                echo '<td class="text-center pr-0">
                                    ' . $restore_btn . '
                                    <a href="' . $view_link . '" class="view btn btn-sm btn-primary mb-1" title="' . lang('Common.text_view') . '"><i class="fa fa-eye"></i> ' . lang('Common.text_view') . '</a>
                                    <a href="' . $edit_link . '" class="edit btn btn-sm btn-info mb-1" title="' . lang('Common.text_edit') . '"><i class="fa fa-edit"></i> ' . lang('Common.text_edit') . '</a>
                                    ' . $empty_trash_btn . '
                                    ' . $trash_btn . '
                                </td>';
                                echo '<td class="text-end" style="display: none;">' . $student_id . '</td>';
                                echo '</tr>';
                        }
                        ?>
                    </tbody>
                </table>

                <div class="row">
                    <div class="col-sm-6">
                        <p>Showing <?= $pager->getCurrentPage() ?> to <?= $pager->getPerPage() ?> of <?= $pager->getTotal() ?> records.</p>
                    </div>
                    <div class="col-sm-6 text-right">
                        <?php if ($pager->getTotal() > $pager->getPerPage() && !empty($pager->getTotal())): ?>
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
        $("#field_show_list").val('');
        $("#field_school_id").val('');
        $("#field_year_id").val('');
        $("#field_class_id").val('');
        $('#student_search').submit();
    });

    $('#field_status').on('change', function() { $('#student_search').submit(); });
    $('#field_show_list').on('change', function() { $('#student_search').submit(); });
    $('#field_school_id').on('change', function() { $('#student_search').submit(); });
    $('#field_year_id').on('change', function() { $('#student_search').submit(); });
    $('#field_class_id').on('change', function() { $('#student_search').submit(); });
    
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    $(document).on('click', '.trash', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.trash_warning_message') ?>');
        if (confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('school-owner/students/trash'); ?>/' + id,
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if (response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.empty_trash', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.empty_trash_warning_message') ?>');
        if (confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('school-owner/students/empty-trash'); ?>',
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if (response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.restore', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.restore_warning_message') ?>');
        if (confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('school-owner/students/restore'); ?>/' + id,
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if (response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });
});
</script>
