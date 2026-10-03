<?php 
$school_list = $school_list ?? [];
$year_list = $year_list ?? [];
$class_list = $class_list ?? [];
$exam_list = $exam_list ?? [];
$school_id = $school_id ?? 0;
$year_id = $year_id ?? 0;
$class_id = $class_id ?? 0;
$exam_id = $exam_id ?? 0;
$students = $students ?? [];
$not_found = $not_found ?? false;
$result = $result ?? null;
$student = $student ?? null;
$subject_marks = $subject_marks ?? [];
$rendered_content = $rendered_content ?? '';
$template_style = $template_style ?? '';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-person"></i> <?= lang('Reports.heading_individual_result') ?? 'Individual Result' ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-3">
                        <label for="school_id" class="form-label">School <span class="required">*</span></label>
                        <select name="school_id" id="school_id" class="form-control" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>" <?= $sid == $school_id ? 'selected' : '' ?>><?= esc($sname) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="year_id" class="form-label">Year/Session <span class="required">*</span></label>
                        <select name="year_id" id="year_id" class="form-control" required disabled>
                            <option value="">Select Year</option>
                            <?php if (!empty($year_list)): ?>
                                <?php foreach ($year_list as $yid => $yval): ?>
                                    <option value="<?= $yid ?>" <?= $yid == $year_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="class_id" class="form-control" required disabled>
                            <option value="">Select Class</option>
                            <?php if (!empty($class_list)): ?>
                                <?php foreach ($class_list as $cid => $cval): ?>
                                    <option value="<?= $cid ?>" <?= $cid == $class_id ? 'selected' : '' ?>><?= esc($cval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="exam_id" class="form-control" required disabled>
                            <option value="">Select Exam</option>
                            <?php if (!empty($exam_list)): ?>
                                <?php foreach ($exam_list as $eid => $e_val): ?>
                                    <option value="<?= $eid ?>" <?= $eid == $exam_id ? 'selected' : '' ?>><?= esc($e_val) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3 align-self-end">
                        <button type="button" id="loadStudentsBtn" class="btn btn-primary w-100" disabled>
                            <i class="bi bi-search"></i> Load Students
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="studentsResult"></div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    let allFieldsSelected = false;
    
    // Function to add CSRF token to data
    function addCsrfToken(data) {
        data[csrfName] = csrfHash;
        return data;
    }
    
    // Function to check if all 4 fields are selected
    function checkAllFieldsSelected() {
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const classId = $('#class_id').val();
        const examId = $('#exam_id').val();
        
        allFieldsSelected = schoolId && yearId && classId && examId;
        
        if (allFieldsSelected) {
            $('#loadStudentsBtn').prop('disabled', false);
        } else {
            $('#loadStudentsBtn').prop('disabled', true);
        }
        
        return allFieldsSelected;
    }
    
    // Function to populate dropdown
    function populateDropdown(selectElement, data, defaultText) {
        selectElement.empty();
        selectElement.append('<option value="">' + defaultText + '</option>');
        
        if (data && Object.keys(data).length > 0) {
            $.each(data, function(key, value) {
                selectElement.append('<option value="' + key + '">' + value + '</option>');
            });
            selectElement.prop('disabled', false);
        } else {
            selectElement.prop('disabled', true);
        }
        
        checkAllFieldsSelected();
    }
    
    // When school is selected, load Year/Session, Class, and Exam
    $('#school_id').change(function() {
        const schoolId = $(this).val();
        
        // Reset all dependent dropdowns
        $('#year_id').empty().append('<option value="">Select Year</option>').prop('disabled', true);
        $('#class_id').empty().append('<option value="">Select Class</option>').prop('disabled', true);
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        $('#loadStudentsBtn').prop('disabled', true);
        $('#studentsResult').empty();
        
        if (schoolId) {
            // Load Years
            $.ajax({
                url: baseUrl + 'examination/reports/individual-result/ajax-get-years',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#year_id'), response.data, 'Select Year');
                    }
                },
                error: function() {
                    console.error('Error loading years');
                }
            });
            
            // Load Classes
            $.ajax({
                url: baseUrl + 'examination/reports/individual-result/ajax-get-classes',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#class_id'), response.data, 'Select Class');
                    }
                },
                error: function() {
                    console.error('Error loading classes');
                }
            });
            
            // Load Exams
            $.ajax({
                url: baseUrl + 'examination/reports/individual-result/ajax-get-exams',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#exam_id'), response.data, 'Select Exam');
                    }
                },
                error: function() {
                    console.error('Error loading exams');
                }
            });
        }
    });
    
    // Check all fields when any dropdown changes
    $('#year_id, #class_id, #exam_id').change(function() {
        checkAllFieldsSelected();
    });
    
    // Load students when button is clicked
    $('#loadStudentsBtn').click(function() {
        if (!checkAllFieldsSelected()) {
            return;
        }
        
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const classId = $('#class_id').val();
        const examId = $('#exam_id').val();
        
        // Show loading indicator
        $('#studentsResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Loading students...</div></div></div>');
        
        $.ajax({
            url: baseUrl + 'examination/reports/individual-result/ajax-get-students',
            type: 'POST',
            data: addCsrfToken({
                school_id: schoolId,
                year_id: yearId,
                class_id: classId,
                exam_id: examId
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#studentsResult').html(response.html);
                } else {
                    $('#studentsResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + response.message + '</div></div></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                $('#studentsResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error loading students. Please try again.</div></div></div>');
            }
        });
    });
});
</script>

<?php if ($school_id && $year_id && $class_id && $exam_id): ?>
    <?php if (!empty($students)): ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Students in Class (<?= count($students) ?> found)</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Roll No</th>
                                        <th>Student Name</th>
                                        <th>Student ID</th>
                                        <th>Registration No</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php $i = 1; ?>
                                    <?php foreach ($students as $stu): ?>
                                        <?php 
                                        $studentName = trim(($stu->first_name ?? '') . ' ' . ($stu->middle_name ?? '') . ' ' . ($stu->last_name ?? ''));
                                        $studentName = preg_replace('/\s+/', ' ', $studentName);
                                        ?>
                                        <tr>
                                            <td><?= $i++ ?></td>
                                            <td><?= esc($stu->roll_no ?? '') ?></td>
                                            <td><?= esc($studentName) ?></td>
                                            <td><?= esc($stu->student_code ?? '') ?></td>
                                            <td><?= esc($stu->registration_no ?? '') ?></td>
                                            <td>
                                                <?php if ($stu->has_result): ?>
                                                    <span class="badge bg-success">Result Available</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">Not Generated</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($stu->has_result && !empty($stu->token)): ?>
                                                    <a href="<?= base_url('examination/reports/individual-result/details/' . $stu->token) ?>" class="btn btn-sm btn-info">
                                                        <i class="bi bi-eye"></i> View
                                                    </a>
                                                   
                                                <?php else: ?>
                                                    <button class="btn btn-sm btn-secondary" disabled>
                                                        <i class="bi bi-eye-slash"></i> Unavailable
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
            </div>
        </div>
    <?php elseif ($school_id && $year_id && $class_id && $exam_id && empty($students)): ?>
        <div class="row mt-3">
            <div class="col-md-12">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i> No students found for the selected filters.
                </div>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

