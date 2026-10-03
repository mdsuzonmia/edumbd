<?php
$csrf_token = csrf_hash();
?>
<?= form_open('examination/subjects/assign-students', [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'assign_students_form',
    'method'  => 'post',
]); ?>
<input type="hidden" id="csrf_token" value="<?= $csrf_token ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-person-plus"></i> Assign Students to Subjects</h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/subjects') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Subjects
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Select Filters</h5>
                <?= get_system_message(); ?>
                <div id="result"></div>

                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="field_school_id" class="form-label">School <span class="required">*</span></label>
                        <select name="school_id" id="field_school_id" class="form-control" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>"><?= esc($sname) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="field_year_id" class="form-label">Academic Year <span class="required">*</span></label>
                        <select name="year_id" id="field_year_id" class="form-control" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="field_class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="field_class_id" class="form-control" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>

                    <div class="col-md-12 text-end">
                        <button type="button" id="btn_load_students" class="btn btn-primary">
                            <i class="fa fa-users"></i> Load Students
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="students_section" style="display: none;">
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Students List — Select subjects for each student</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="students_table">
                            <thead>
                                <tr>
                                    <th width="50px">#</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Roll No</th>
                                    <th>Assigned Subjects</th>
                                    <th>Assigned Optional Subjects</th>
                                </tr>
                            </thead>
                            <tbody id="students_table_body"></tbody>
                        </table>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-end">
                            <button type="submit" id="btn_save_assignments" class="btn btn-success">
                                <i class="fa fa-save"></i> Save Assignments
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    // Store subject list globally
    window.subjectList = {};
    window.optionalSubjectList = {};

    // Load academic data when school changes
    $('#field_school_id').on('change', function() {
        var school_id = $(this).val();
        if (school_id) {
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/subjects/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                        window.subjectList = response.subject_list || {};
                        window.optionalSubjectList = response.optional_subject_list || {};
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id'].forEach(function(sel) {
                $(sel).html('<option value="">Select...</option>');
            });
            window.subjectList = {};
            window.optionalSubjectList = {};
        }
    });

    function populateSelect(selector, data) {
        var $sel = $(selector);
        $sel.html('<option value="">Select...</option>');
        if (data) {
            $.each(data, function(key, value) {
                $sel.append('<option value="' + key + '">' + value + '</option>');
            });
        }
    }

    // Load students
    $('#btn_load_students').on('click', function() {
        var school_id = $('#field_school_id').val();
        var class_id  = $('#field_class_id').val();
        var year_id   = $('#field_year_id').val();

        if (!school_id || !class_id || !year_id) {
            alert('Please select School, Academic Year, and Class.');
            return;
        }

        $('#btn_load_students').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Loading...');

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/subjects/getStudentsByFilter') ?>',
            data: {
                school_id: school_id,
                class_id: class_id,
                year_id: year_id
            },
            success: function(response) {
                if (response.status && response.students && response.students.length > 0) {
                    renderStudentsTable(response.students, response.subject_list);
                } else {
                    alert(response.message || 'No students found for the selected filters.');
                    $('#students_section').hide();
                }
            },
            error: function() {
                alert('Error loading students. Please try again.');
            },
            complete: function() {
                $('#btn_load_students').prop('disabled', false).html('<i class="fa fa-users"></i> Load Students');
            }
        });
    });

    function renderStudentsTable(students, subjectList) {
        var tbody = $('#students_table_body');
        tbody.empty();

        // Build subject options for select2
        var subjectOptions = '<option value="">Select Subjects</option>';
        $.each(subjectList, function(id, name) {
            subjectOptions += '<option value="' + id + '">' + name + '</option>';
        });

        // Build optional subject options (only subjects with optional=1)
        var optionalSubjectOptions = '<option value="">Select Optional Subject</option>';
        $.each(window.optionalSubjectList, function(id, name) {
            optionalSubjectOptions += '<option value="' + id + '">' + name + '</option>';
        });

        $.each(students, function(i, student) {
            var row = '<tr>';
            row += '<td>' + (i + 1) + '</td>';
            row += '<td>' + (student.student_code || '') + '</td>';
            row += '<td>' + student.student_name + '</td>';
            row += '<td>' + (student.roll_no || '') + '</td>';
            row += '<td>';
            row += '<select class="subject-multi-select" name="student_subjects[' + i + '][subject_ids][]" multiple style="width:100%">';
            row += subjectOptions;
            row += '</select>';
            row += '<input type="hidden" name="student_subjects[' + i + '][enrollment_id]" value="' + student.enrollment_id + '">';
            row += '</td>';
            row += '<td>';
            row += '<select class="optional-subject-select" name="student_subjects[' + i + '][optional_subject_id]" style="width:100%">';
            row += optionalSubjectOptions;
            row += '</select>';
            row += '</td>';
            row += '</tr>';
            tbody.append(row);
        });

        // Initialize select2 on all multi-select dropdowns
        $('.subject-multi-select').each(function(idx, el) {
            var $el = $(el);
            $el.select2({
                placeholder: 'Select subjects for this student',
                allowClear: true,
                width: 'resolve'
            });

            // Pre-select assigned subjects
            var student = students[idx];
            if (student.assigned_subject_ids && student.assigned_subject_ids.length > 0) {
                $el.val(student.assigned_subject_ids).trigger('change');
            }
        });

        // Initialize select2 on optional subject single-select dropdowns
        $('.optional-subject-select').each(function(idx, el) {
            var $el = $(el);
            $el.select2({
                placeholder: 'Select optional subject',
                allowClear: true,
                width: 'resolve'
            });

            // Pre-select assigned optional subject
            var student = students[idx];
            if (student.assigned_optional_subject_id) {
                $el.val(student.assigned_optional_subject_id).trigger('change');
            }
        });

        $('#students_section').show();
        $('html, body').animate({ scrollTop: $('#students_section').offset().top - 100 }, 1000);
    }

    // Form submission
    $('#assign_students_form').on('submit', function(e) {
        e.preventDefault();

        var hasAnySelection = false;
        $('.subject-multi-select').each(function() {
            if ($(this).val() && $(this).val().length > 0) {
                hasAnySelection = true;
            }
        });

        if (!hasAnySelection) {
            alert('Please select at least one subject for at least one student.');
            return;
        }

        if (!confirm('Are you sure you want to save these assignments? This will overwrite any existing subject assignments for these students.')) {
            return;
        }

        $('#btn_save_assignments').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

        var school_id = $('#field_school_id').val();

        // Build form data manually for the nested array structure
        var studentSubjects = [];
        $('tr', $('#students_table_body')).each(function() {
            var $select = $(this).find('.subject-multi-select');
            var $optionalSelect = $(this).find('.optional-subject-select');
            var $enrollmentInput = $(this).find('input[name$="[enrollment_id]"]');
            var selectedSubjects = $select.val();
            var optionalSubjectId = $optionalSelect.val();

            if (selectedSubjects && selectedSubjects.length > 0) {
                var studentData = {
                    enrollment_id: $enrollmentInput.val(),
                    subject_ids: selectedSubjects
                };
                if (optionalSubjectId) {
                    studentData.optional_subject_id = optionalSubjectId;
                    // Ensure optional subject is included in subject_ids so a record is created/updated
                    if (studentData.subject_ids.indexOf(optionalSubjectId) === -1) {
                        studentData.subject_ids.push(optionalSubjectId);
                    }
                }
                studentSubjects.push(studentData);
            }
        });

        $.ajax({
            type: "post", dataType: "json",
            url: '<?= base_url('examination/subjects/assign-students') ?>',
            data: {
                school_id: school_id,
                student_subjects: studentSubjects
            },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                if (response.status) {
                    $('#result').html('<div class="alert alert-success">' + response.message + '</div>');
                    // Reload students to reflect changes
                    $('#btn_load_students').click();
                } else {
                    $('#result').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#result').html('<div class="alert alert-danger">Error saving assignments. Please try again.</div>');
            },
            complete: function() {
                $('#btn_save_assignments').prop('disabled', false).html('<i class="fa fa-save"></i> Save Assignments');
                $('html, body').animate({ scrollTop: $('#result').offset().top - 100 }, 500);
            }
        });
    });
});
</script>

<style>
.select2-container--default .select2-selection--multiple {
    min-height: 32px;
    border: 1px solid #ced4da;
    border-radius: 4px;
    font-size: 13px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #007bff;
    border-color: #006fe6;
    color: #fff;
    font-size: 12px;
    padding: 1px 6px;
    border-radius: 3px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: #fff;
    margin-right: 4px;
}
.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: #ffc107;
}
#students_table td {
    vertical-align: middle;
}
</style>