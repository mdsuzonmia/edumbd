<?php
$selected_school = isset($selected_school) ? $selected_school : '';
$selected_exam = isset($selected_exam) ? $selected_exam : '';
$selected_year = isset($selected_year) ? $selected_year : '';
$selected_class = isset($selected_class) ? $selected_class : '';
$selected_subject = isset($selected_subject) ? $selected_subject : '';
?>

<?= form_open('examination/marks/locked-marks', [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'locked_marks_filter',
    'method'  => 'get'
]); ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row mb-3">
        <div class="col-md-12">
            <h3 class="text-secondary mb-0"><i class="bi bi-lock"></i> Locked Marks List</h3>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <label for="field_school_id" class="form-label">School</label>
            <select name="school_id" id="field_school_id" class="form-control">
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

        <div class="col-md-2">
            <label for="field_year_id" class="form-label">Academic Year</label>
            <select name="year_id" id="field_year_id" class="form-control">
                <option value="">All Years</option>
                <?php if (!empty($year_list)): ?>
                    <?php foreach ($year_list as $yid => $yname): ?>
                        <option value="<?= $yid ?>" <?= $selected_year == $yid ? 'selected' : '' ?>>
                            <?= esc($yname) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="field_class_id" class="form-label">Class</label>
            <select name="class_id" id="field_class_id" class="form-control">
                <option value="">All Classes</option>
                <?php if (!empty($class_list)): ?>
                    <?php foreach ($class_list as $cid => $cname): ?>
                        <option value="<?= $cid ?>" <?= $selected_class == $cid ? 'selected' : '' ?>>
                            <?= esc($cname) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="field_exam_id" class="form-label">Exam</label>
            <select name="exam_id" id="field_exam_id" class="form-control">
                <option value="">All Exams</option>
                <?php if (!empty($exam_list)): ?>
                    <?php foreach ($exam_list as $eid => $ename): ?>
                        <option value="<?= $eid ?>" <?= $selected_exam == $eid ? 'selected' : '' ?>>
                            <?= esc($ename) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label for="field_subject_id" class="form-label">Subject</label>
            <select name="subject_id" id="field_subject_id" class="form-control">
                <option value="">All Subjects</option>
                <?php if (!empty($subject_list)): ?>
                    <?php foreach ($subject_list as $sid => $sname): ?>
                        <option value="<?= $sid ?>" <?= $selected_subject == $sid ? 'selected' : '' ?>>
                            <?= esc($sname) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label">&nbsp;</label>
            <div>
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-search"></i> Search
                </button>
                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= base_url('examination/marks/locked-marks') ?>'">
                    <i class="fa fa-refresh"></i> Reset
                </button>
            </div>
        </div>
    </div>
<?= form_close() ?>

<?php if (empty($locked_marks)): ?>
    <div class="alert alert-info">
        No locked marks found for the selected criteria.
    </div>
<?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>School</th>
                            <th>Exam</th>
                            <th>Class</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Locked By</th>
                            <th>Locked At</th>
                            <th>Unlocked By</th>
                            <th>Unlocked At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($locked_marks as $index => $lock): ?>
                            <tr>
                                <td><?= ($index + 1) ?></td>
                                <td><?= esc($lock->school_name ?? 'N/A') ?></td>
                                <td><?= esc($lock->exam_title ?? 'N/A') ?></td>
                                <td><?= esc($lock->class_title ?? 'N/A') ?></td>
                                <td><?= esc($lock->subject_title ?? 'N/A') ?></td>
                                <td>
                                    <?php if ($lock->is_locked): ?>
                                        <span class="badge bg-danger text-white">Locked</span>
                                    <?php else: ?>
                                        <span class="badge bg-success text-white">Unlocked</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($lock->locked_by_name ?? 'Unknown') ?></td>
                                <td><?= $lock->locked_at ? date('Y-m-d H:i', strtotime($lock->locked_at)) : 'N/A' ?></td>
                                <td><?= esc($lock->unlocked_by_name ?? 'N/A') ?></td>
                                <td><?= $lock->unlocked_at ? date('Y-m-d H:i', strtotime($lock->unlocked_at)) : 'N/A' ?></td>
                                <td>
                                    <?php if ($lock->is_locked): ?>
                                        <button class="btn btn-sm btn-warning unlock-btn" 
                                                data-school="<?= $lock->school_id ?>"
                                                data-exam="<?= $lock->exam_id ?>"
                                                data-class="<?= $lock->class_id ?>"
                                                data-section="<?= $lock->section_id ?? '' ?>"
                                                data-subject="<?= $lock->subject_id ?? '' ?>"
                                                data-session="<?= $lock->session_id ?? '' ?>">
                                            <i class="fas fa-unlock"></i> Unlock
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-primary lock-btn" 
                                                data-school="<?= $lock->school_id ?>"
                                                data-exam="<?= $lock->exam_id ?>"
                                                data-class="<?= $lock->class_id ?>"
                                                data-section="<?= $lock->section_id ?? '' ?>"
                                                data-subject="<?= $lock->subject_id ?? '' ?>"
                                                data-session="<?= $lock->session_id ?? '' ?>">
                                            <i class="fas fa-lock"></i> Lock
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
$(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Load academic data when school changes
    $('#field_school_id').on('change', function() {
        var school_id = $(this).val();
        var $yearSelect = $('#field_year_id');
        var $classSelect = $('#field_class_id');
        var $examSelect = $('#field_exam_id');
        
        if (school_id) {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/marks/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        // Load years
                        $yearSelect.html('<option value="">All Years</option>');
                        if (response.year_list) {
                            $.each(response.year_list, function(key, value) {
                                $yearSelect.append('<option value="' + key + '">' + value + '</option>');
                            });
                        }
                        
                        // Load classes
                        $classSelect.html('<option value="">All Classes</option>');
                        if (response.class_list) {
                            $.each(response.class_list, function(key, value) {
                                $classSelect.append('<option value="' + key + '">' + value + '</option>');
                            });
                        }
                        
                        // Load exams
                        $examSelect.html('<option value="">All Exams</option>');
                        if (response.exam_list) {
                            $.each(response.exam_list, function(key, value) {
                                $examSelect.append('<option value="' + key + '">' + value + '</option>');
                            });
                        }
                        
                        // Auto-submit after loading all filters
                        setTimeout(function() {
                            $('#locked_marks_filter').submit();
                        }, 100);
                    }
                }
            });
        } else {
            $yearSelect.html('<option value="">All Years</option>');
            $classSelect.html('<option value="">All Classes</option>');
            $examSelect.html('<option value="">All Exams</option>');
        }
    });

    // Auto-submit when filters change
    $('#field_year_id, #field_class_id, #field_exam_id').on('change', function() {
        var filterValue = $(this).val();
        if (filterValue) {
            $('#locked_marks_filter').submit();
        }
    });

     $('.unlock-btn').click(function() {
         const btn = $(this);
         const schoolId = btn.data('school');
         const examId = btn.data('exam');
         const classId = btn.data('class');
         const sectionId = btn.data('section');
         const subjectId = btn.data('subject');
         const sessionId = btn.data('session');
         
         if (confirm('Are you sure you want to unlock these marks?')) {
             $.ajax({
                 url: '<?= site_url("examination/marks/unlock") ?>',
                 type: 'POST',
                 data: {
                     school_id: schoolId,
                     exam_id: examId,
                     class_id: classId,
                     section_id: sectionId || '',
                     subject_id: subjectId || '',
                     session_id: sessionId || ''
                 },
                 success: function(response) {
                     if (response.status) {
                         alert(response.message);
                         location.reload();
                     } else {
                         alert(response.message);
                     }
                 },
                 error: function() {
                     alert('An error occurred. Please try again.');
                 }
             });
         }
     });

     $('.lock-btn').click(function() {
         const btn = $(this);
         const schoolId = btn.data('school');
         const examId = btn.data('exam');
         const classId = btn.data('class');
         const sectionId = btn.data('section');
         const subjectId = btn.data('subject');
         const sessionId = btn.data('session');
         
         if (confirm('Are you sure you want to lock these marks?')) {
             $.ajax({
                 url: '<?= site_url("examination/marks/lock") ?>',
                 type: 'POST',
                 data: {
                     school_id: schoolId,
                     exam_id: examId,
                     class_id: classId,
                     section_id: sectionId || '',
                     subject_id: subjectId || '',
                     session_id: sessionId || '',
                     lock_reason: 'Locked from locked marks list'
                 },
                 success: function(response) {
                     if (response.status) {
                         alert(response.message);
                         location.reload();
                     } else {
                         alert(response.message);
                     }
                 },
                 error: function() {
                     alert('An error occurred. Please try again.');
                 }
             });
         }
     });
});
</script>