<?php 

$show_list_array = array(
    ''  => lang('Common.show_list'), 
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
$selected_subject = isset($selected_subject) ? $selected_subject : '';
$selected_exam = isset($selected_exam) ? $selected_exam : '';
$selected_year = isset($selected_year) ? $selected_year : '';
$selected_class = isset($selected_class) ? $selected_class : '';
$items = isset($items) ? $items : [];
$pager = isset($pager) ? $pager : null;
$pagerTemplate = isset($pagerTemplate) ? $pagerTemplate : 'default';

?>

    <?= form_open('examination/marks/list', [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'marks_search', 
        'method'  => 'get', 
        'data-parsley-validate'=>'' 
        ]); 
    ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row mb-3">
        <div class="col-sm-3">
            <h3 class="text-secondary mb-0"><i class="bi bi-bar-chart"></i> <?= lang('Mark.heading_index'); ?></h3>
        </div>

        <div class="col-sm-9 text-end pt-0">
            <div class="d-flex flex-wrap gap-2 justify-content-end align-items-center">
                <div class="input-group" style="width: 300px;">
                    <input type="text" id="search_text" name="text" value="<?= $text ?>" class="form-control" placeholder="<?= lang('Common.search_placeholder') ?>">
                    <button type="submit" class="btn btn-primary" ><i class="fa fa-search" aria-hidden="true"></i></button>
                    <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i></button>
                </div>

                <select name="school_id" id="field_school_id" class="form-control filter-select" style="width: 150px;">
                    <option value=""><?= lang('Common.select_school') ?></option>
                    <?php if (!empty($school_list)): ?>
                        <?php foreach ($school_list as $sid => $sname): ?>
                            <option value="<?= $sid ?>" <?= $selected_school == $sid ? 'selected' : '' ?>>
                                <?= esc($sname) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>

                <?php if (!empty($subject_list)): ?>
                <select name="subject_id" id="field_subject_id" class="form-control filter-select" style="width: 150px;">
                    <option value=""><?= lang('Common.select_subject') ?></option>
                    <?php foreach ($subject_list as $sid => $sname): ?>
                        <option value="<?= $sid ?>" <?= $selected_subject == $sid ? 'selected' : '' ?>>
                            <?= esc($sname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <?php if (!empty($year_list)): ?>
                <select name="year_id" id="field_year_id" class="form-control filter-select" style="width: 150px;">
                    <option value=""><?= lang('Common.select_year') ?></option>
                    <?php foreach ($year_list as $yid => $yname): ?>
                        <option value="<?= $yid ?>" <?= $selected_year == $yid ? 'selected' : '' ?>>
                            <?= esc($yname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>

                <?php if (!empty($class_list)): ?>
                <select name="class_id" id="field_class_id" class="form-control filter-select" style="width: 150px;">
                    <option value=""><?= lang('Common.select_class') ?></option>
                    <?php foreach ($class_list as $cid => $cname): ?>
                        <option value="<?= $cid ?>" <?= $selected_class == $cid ? 'selected' : '' ?>>
                            <?= esc($cname) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                
                <?php if (!empty($exam_list)): ?>
                <select name="exam_id" id="field_exam_id" class="form-control filter-select" style="width: 150px;">
                    <option value=""><?= lang('Common.select_exam') ?></option>
                    <?php foreach ($exam_list as $eid => $ename): ?>
                        <option value="<?= $eid ?>" <?= $selected_exam == $eid ? 'selected' : '' ?>>
                            <?= esc($ename) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php endif; ?>
                
                <?= $status_list; ?>
                <?= $show_list; ?>
                
                <button type="button" id="btn_lock_marks" class="btn btn-warning" style="display: none;">
                    <i class="fa fa-lock"></i> Lock Marks
                </button>
                <button type="button" id="btn_unlock_marks" class="btn btn-success" style="display: none;">
                    <i class="fa fa-unlock"></i> Unlock Marks
                </button>
                <a href="<?= base_url('examination/marks/create') ?>" class="edit btn btn-success"><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('Mark.btn_new') ?></a>
                <a href="<?= base_url('examination/marks/bulk-import') ?>" class="btn btn-info"><i class="fa fa-upload" aria-hidden="true"></i> <?= lang('Mark.btn_bulk_import') ?></a>
                <a href="<?= base_url('examination/marks/locked-marks') ?>" class="btn btn-dark" target="_blank">
                    <i class="fa fa-list"></i> View Locked Marks
                </a>
            </div>
        </div>
    </div>
    <?= form_close() ?>

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="card">
                <div class="card-body">
                    <?= get_system_message(); ?>
                    <div id="result"></div>

                    <div class="table-responsive">
                    <table id="table" class="table student-table ">

                        <thead>
                            <tr>
                                <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th> 
                                <th><?= lang('Mark.field_student_name') ?></th>
                                <th><?= lang('Mark.field_roll_no') ?></th>
                                <th><?= lang('Mark.field_subject') ?></th>
                                <th><?= lang('Mark.field_exam') ?></th>
                                <th><?= lang('Mark.field_marks') ?></th>
                                <th>School</th>
                                <th class="text-center" ><?= lang('Common.th_status') ?></th> 
                                <th width="300px" class="text-end" ><?= lang('Common.th_action') ?></th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            foreach ($items as $key => $item) {
                                $item_id       = $item->id;
                                $item_token    = $item->token ?? '';
                                $student_name  = trim(($item->first_name ?? '') . ' ' . ($item->last_name ?? ''));
                                $subject_title = $item->subject_title ?? '-';
                                $exam_title    = $item->exam_title ?? '-';
                                $school_name   = $item->school_name ?? '-';
                                $distribution_name = $item->distribution_name ?? '-';
                                $obtained_mark = $item->obtained_mark ?? 0;
                                $full_mark     = $item->full_mark ?? 0;
                                $marks_display = $obtained_mark . '/' . $full_mark;
                                
                                $edit_link     = base_url('examination/marks/edit/'.$item_token);
                                $view_link     = base_url('examination/marks/view/'.$item_id);

                                echo '<tr id="item_'.$item->id.'">
                                    <td class="text-left pl-0">'.++$key.'</td>
                                    <td class="pl-0" >
                                        <p class="mb-0"><b class="title">'.esc($student_name).'</b></p>
                                    </td>
                                    <td>'.esc($item->roll_no ?? '-').'</td>
                                    <td>'.esc($subject_title).'</td>
                                    <td>'.esc($exam_title).'</td>
                                    <td>'.esc($marks_display).' ('.esc($distribution_name).')</td>
                                    <td>'.esc($school_name).'</td>';

                                    $status_html = '';
                                    $restore_btn = '';
                                    $empty_trash_btn = '';
                                    $trash_btn = '';
                                    if($item->status == 0){
                                        $status_html .= '<span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> '.$status_array[$item->status].'</span>';
                                    }
                                    if($item->status == 1){
                                        $status_html .= '<span class="badge text-bg-success"><i class="fa fa-check-circle"></i> '.$status_array[$item->status].'</span>';
                                        $trash_btn .= '<button type="button" data-id="'.$item_token.'" class=" trash btn btn-sm btn-danger mb-1" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                    }
                                    if($item->status == 2){
                                        $status_html .= '<span class="badge text-bg-danger"><i class="fa fa-ban"></i> '.$status_array[$item->status].'</span>';
                                        $restore_btn .= '<button type="button" data-id="'.$item_token.'" class=" restore btn btn-sm btn-success mb-1" title="'.lang('Common.text_restore').'"><i class="fa fa-check-circle"></i> '.lang('Common.text_restore').'</button>';
                                        $empty_trash_btn .= '<button type="button" data-id="'.$item_token.'" class=" empty_trash btn btn-sm btn-danger mb-1" title="'.lang('Common.empty_trash').'"><i class="fa fa-trash"></i> '.lang('Common.empty_trash').'</button>';
                                    }

                                    echo '<td class="text-center">'.$status_html.'</td>';
                                    echo'<td class="text-end pr-0" >
                                    <a href="'.$view_link.'" class=" btn btn-sm btn-primary mb-1" title="'.lang('Common.text_view').'"><i class="fa fa-eye"></i></a>
                                    '.$restore_btn.'
                                    <a href="'.$edit_link.'" class=" edit btn btn-sm btn-info mb-1 edit-btn" title="'.lang('Common.text_edit').'" data-school="'.$item->school_id.'" data-exam="'.$item->exam_id.'" data-class="'.$item->class_id.'" data-section="'.($item->section_id ?? '').'" data-subject="'.$item->subject_id.'"><i class="fa fa-edit"></i> '.lang('Common.text_edit').'</a> 
                                    '.$empty_trash_btn.'
                                    '.$trash_btn.'
                                    </td>';
                                    echo '</tr>';
                            }
                            ?>
                        </tbody>
                    </table>
                    </div>

                    <?php if ($pager && $pager->getTotal() > 0): ?>
                    <div class="row">
                        <div class="col-sm-6">
                        <p><?= lang('Common.showing_entries', [$pager->getCurrentPage(), $pager->getPerPage(), $pager->getTotal()]) ?></p>
                        </div>
                        <div class="col-sm-6 text-right">
                            <?php if($pager->getTotal() > $pager->getPerPage()): ?>
                            <?= $pager->links('default', $pagerTemplate); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
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
        $("#field_subject_id").val('');
        $("#field_exam_id").val('');
        $('#marks_search').submit();
    })

    $('#field_status').on('change', function() { $('#marks_search').submit(); });
    $('#field_show_list').on('change', function() { $('#marks_search').submit(); });
    $('#field_school_id').on('change', function() { $('#marks_search').submit(); });
    $('#field_subject_id').on('change', function() { $('#marks_search').submit(); });
    $('#field_exam_id').on('change', function() { $('#marks_search').submit(); });
    
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Check lock status
    function checkLockStatus() {
        var school_id = $('#field_school_id').val();
        var exam_id = $('#field_exam_id').val();
        var class_id = $('#field_class_id').val();
        var year_id = $('#field_year_id').val();
        var subject_id = $('#field_subject_id').val();

        if (!school_id || !exam_id || !class_id || !year_id || !subject_id) {
            $('#btn_lock_marks').hide();
            $('#btn_unlock_marks').hide();
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/check-lock-status') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                subject_id: subject_id,
                class_id: class_id,
                session_id: year_id
            },
            success: function(response) {
                if (response.status) {
                    if (response.is_locked) {
                        $('.edit').hide();
                    } else {
                        $('.edit').show();
                    }
                }
            }
        });
    }

    // Lock marks
    $('#btn_lock_marks').on('click', function() {
        var school_id = $('#field_school_id').val();
        var exam_id = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();

        if (!school_id || !exam_id || !subject_id) {
            alert('Please select school, exam, and subject first.');
            return;
        }

        var lockReason = prompt('Please enter a reason for locking these marks:');
        if (!lockReason) {
            return;
        }

        if (!confirm('Are you sure you want to lock all marks for this exam and subject? This will prevent any further edits.')) {
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/lock') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                subject_id: subject_id,
                class_id: 0,
                section_id: '',
                session_id: year_id,
                lock_reason: lockReason
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    checkLockStatus();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error locking marks. Please try again.');
            }
        });
    });

    // Unlock marks
    $('#btn_unlock_marks').on('click', function() {
        var school_id = $('#field_school_id').val();
        var exam_id = $('#field_exam_id').val();
        var subject_id = $('#field_subject_id').val();

        if (!school_id || !exam_id || !subject_id) {
            alert('Please select school, exam, and subject first.');
            return;
        }

        var unlockReason = prompt('Please enter a reason for unlocking these marks:');
        if (!unlockReason) {
            return;
        }

        if (!confirm('Are you sure you want to unlock all marks for this exam and subject? This will allow edits again.')) {
            return;
        }

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/unlock') ?>',
            data: {
                school_id: school_id,
                exam_id: exam_id,
                subject_id: subject_id,
                class_id: 0,
                section_id: '',
                session_id: year_id,
                unlock_reason: unlockReason
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.status) {
                    alert(response.message);
                    checkLockStatus();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('Error unlocking marks. Please try again.');
            }
        });
    });

    // Check lock status on filter change
    $('#field_school_id, #field_exam_id, #field_subject_id, #field_year_id, #field_class_id').on('change', function() {
        checkLockStatus();
        $('#marks_search').submit();
    });

    // Handle edit button click - check lock status before navigating
    $(document).on('click', '.edit-btn', function(e) {
        var schoolId = $(this).data('school');
        var examId = $(this).data('exam');
        var classId = $(this).data('class');
        var sectionId = $(this).data('section');
        var subjectId = $(this).data('subject');
        
        // Check lock status
        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/marks/check-lock-status') ?>',
            data: {
                school_id: schoolId,
                exam_id: examId,
                class_id: classId,
                section_id: sectionId || '',
                subject_id: subjectId || '',
                session_id: yearId || ''
            },
            success: function(response) {
                if (response.status && response.is_locked) {
                    alert('Cannot edit marks. Subject marks are locked for the exam ' + response.exam_title + ', session ' + response.session_name + ', class ' + response.class_name + '. Please unlock the marks first.');
                } else {
                    // Not locked, proceed to edit page
                    window.location.href = $(e.target).closest('a').attr('href');
                }
            },
            error: function() {
                // If error, allow navigation (fail open)
                window.location.href = $(e.target).closest('a').attr('href');
            }
        });
        
        return false; // Prevent default navigation
    });

    $(document).on('click', '.trash', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.trash_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/marks/trash'); ?>/' + id,
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.empty_trash', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.empty_trash_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/marks/empty-trash'); ?>',
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.restore', function(e) { 
        var id = $(this).data("id");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.restore_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/marks/restore'); ?>/' + id,
                data: { id: id },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });
});
</script>