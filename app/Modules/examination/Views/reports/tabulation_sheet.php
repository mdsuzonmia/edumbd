<?php 
$school_list = $school_list ?? [];
$year_list = $year_list ?? [];
$exam_list = $exam_list ?? [];
$class_list = $class_list ?? [];
$school_id = $school_id ?? 0;
$year_id = $year_id ?? 0;
$exam_id = $exam_id ?? 0;
$class_id = $class_id ?? 0;
$tabulation_html = $tabulation_html ?? '';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-table"></i> <?= lang('Reports.heading_tabulation_sheet') ?? 'Tabulation Sheet' ?></h3>
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
                        <label for="year_id" class="form-label">Session <span class="required">*</span></label>
                        <select name="year_id" id="year_id" class="form-control" required disabled>
                            <option value="">Select Session</option>
                            <?php if (!empty($year_list)): ?>
                                <?php foreach ($year_list as $yid => $yval): ?>
                                    <option value="<?= $yid ?>" <?= $yid == $year_id ? 'selected' : '' ?>><?= esc($yval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="exam_id" class="form-label">Exam <span class="required">*</span></label>
                        <select name="exam_id" id="exam_id" class="form-control" required disabled>
                            <option value="">Select Exam</option>
                            <?php if (!empty($exam_list)): ?>
                                <?php foreach ($exam_list as $eid => $eVal): ?>
                                    <option value="<?= $eid ?>" <?= $eid == $exam_id ? 'selected' : '' ?>><?= esc($eVal) ?></option>
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
                    <div class="col-12">
                        <button type="button" id="generateBtn" class="btn btn-primary" disabled>
                            <i class="bi bi-table"></i> Generate
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="tabulationResult">
    <?php if (!empty($tabulation_html)): ?>
        <?= $tabulation_html ?>
    <?php endif; ?>
</div>

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
        const examId = $('#exam_id').val();
        const classId = $('#class_id').val();
        
        allFieldsSelected = schoolId && yearId && examId && classId;
        
        if (allFieldsSelected) {
            $('#generateBtn').prop('disabled', false);
        } else {
            $('#generateBtn').prop('disabled', true);
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
    
    // When school is selected, load Session, Exam, and Class
    $('#school_id').change(function() {
        const schoolId = $(this).val();
        
        // Reset all dependent dropdowns
        $('#year_id').empty().append('<option value="">Select Session</option>').prop('disabled', true);
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        $('#class_id').empty().append('<option value="">Select Class</option>').prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#tabulationResult').empty();
        
        if (schoolId) {
            // Load Years
            $.ajax({
                url: baseUrl + 'examination/reports/tabulation-sheet/ajax-get-years',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#year_id'), response.data, 'Select Session');
                    }
                },
                error: function() {
                    console.error('Error loading sessions');
                }
            });
            
            // Load Classes
            $.ajax({
                url: baseUrl + 'examination/reports/tabulation-sheet/ajax-get-classes',
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
        }
    });
    
    // When year is selected, load Exams
    $('#year_id').change(function() {
        const schoolId = $('#school_id').val();
        const yearId = $(this).val();
        
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#tabulationResult').empty();
        
        if (schoolId && yearId) {
            $.ajax({
                url: baseUrl + 'examination/reports/tabulation-sheet/ajax-get-exams',
                type: 'POST',
                data: addCsrfToken({
                    school_id: schoolId,
                    year_id: yearId
                }),
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
        
        checkAllFieldsSelected();
    });
    
    // Check all fields when any dropdown changes
    $('#exam_id, #class_id').change(function() {
        checkAllFieldsSelected();
    });
    
    // Generate tabulation sheet when button is clicked
    $('#generateBtn').click(function() {
        if (!checkAllFieldsSelected()) {
            return;
        }
        
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const examId = $('#exam_id').val();
        const classId = $('#class_id').val();
        
        // Show loading indicator
        $('#tabulationResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Generating tabulation sheet...</div></div></div>');
        
        $.ajax({
            url: baseUrl + 'examination/reports/tabulation-sheet/ajax-generate-tabulation',
            type: 'POST',
            data: addCsrfToken({
                school_id: schoolId,
                year_id: yearId,
                exam_id: examId,
                class_id: classId
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#tabulationResult').html(response.html);
                } else {
                    $('#tabulationResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + response.message + '</div></div></div>');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error:', error);
                $('#tabulationResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error generating tabulation sheet. Please try again.</div></div></div>');
            }
        });
    });
});
</script>