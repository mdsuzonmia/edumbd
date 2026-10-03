<?php 
$school_list = $school_list ?? [];
$year_list = $year_list ?? [];
$exam_list = $exam_list ?? [];
$class_list = $class_list ?? [];
$section_list = $section_list ?? [];
$school_id = $school_id ?? 0;
$year_id = $year_id ?? 0;
$exam_id = $exam_id ?? 0;
$class_id = $class_id ?? 0;
$section_id = $section_id ?? 0;
$limit = $limit ?? 0;
$merit_html = $merit_html ?? '';
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-trophy"></i> <?= lang('Reports.heading_merit_list') ?? 'Merit List' ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form id="filterForm" class="row g-3">
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
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
                    <div class="col-md-2">
                        <label for="section_id" class="form-label">Section</label>
                        <select name="section_id" id="section_id" class="form-control" disabled>
                            <option value="">All Sections</option>
                            <?php if (!empty($section_list)): ?>
                                <?php foreach ($section_list as $sid => $sval): ?>
                                    <option value="<?= $sid ?>" <?= $sid == $section_id ? 'selected' : '' ?>><?= esc($sval) ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="limit" class="form-label">Top Students</label>
                        <select name="limit" id="limit" class="form-control">
                            <option value="0" <?= $limit == 0 ? 'selected' : '' ?>>All Students</option>
                            <option value="5" <?= $limit == 5 ? 'selected' : '' ?>>Top 5</option>
                            <option value="10" <?= $limit == 10 ? 'selected' : '' ?>>Top 10</option>
                            <option value="15" <?= $limit == 15 ? 'selected' : '' ?>>Top 15</option>
                            <option value="20" <?= $limit == 20 ? 'selected' : '' ?>>Top 20</option>
                            <option value="25" <?= $limit == 25 ? 'selected' : '' ?>>Top 25</option>
                            <option value="30" <?= $limit == 30 ? 'selected' : '' ?>>Top 30</option>
                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>Top 50</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="button" id="generateBtn" class="btn btn-primary" disabled>
                            <i class="bi bi-trophy"></i> Generate Merit List
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div id="meritResult">
    <?php if (!empty($merit_html)): ?>
        <?= $merit_html ?>
    <?php endif; ?>
</div>

<script>
$(document).ready(function() {
    const baseUrl = '<?= base_url() ?>';
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';
    let allFieldsSelected = false;
    
    function addCsrfToken(data) {
        data[csrfName] = csrfHash;
        return data;
    }
    
    function checkAllFieldsSelected() {
        const schoolId = $('#school_id').val();
        const yearId = $('#year_id').val();
        const examId = $('#exam_id').val();
        const classId = $('#class_id').val();
        
        allFieldsSelected = schoolId && yearId && examId && classId;
        $('#generateBtn').prop('disabled', !allFieldsSelected);
        
        return allFieldsSelected;
    }
    
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
    
    $('#school_id').change(function() {
        const schoolId = $(this).val();
        
        $('#year_id').empty().append('<option value="">Select Session</option>').prop('disabled', true);
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        $('#class_id').empty().append('<option value="">Select Class</option>').prop('disabled', true);
        $('#section_id').empty().append('<option value="">All Sections</option>').prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#meritResult').empty();
        
        if (schoolId) {
            $.ajax({
                url: baseUrl + 'examination/reports/merit-list/ajax-get-years',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#year_id'), response.data, 'Select Session');
                    }
                }
            });
            
            $.ajax({
                url: baseUrl + 'examination/reports/merit-list/ajax-get-classes',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#class_id'), response.data, 'Select Class');
                    }
                }
            });
        }
    });
    
    $('#year_id').change(function() {
        const schoolId = $('#school_id').val();
        const yearId = $(this).val();
        
        $('#exam_id').empty().append('<option value="">Select Exam</option>').prop('disabled', true);
        $('#generateBtn').prop('disabled', true);
        $('#meritResult').empty();
        
        if (schoolId && yearId) {
            $.ajax({
                url: baseUrl + 'examination/reports/merit-list/ajax-get-exams',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId, year_id: yearId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#exam_id'), response.data, 'Select Exam');
                    }
                }
            });
        }
        checkAllFieldsSelected();
    });
    
    $('#class_id').change(function() {
        const schoolId = $('#school_id').val();
        const classId = $(this).val();
        
        $('#section_id').empty().append('<option value="">All Sections</option>').prop('disabled', true);
        
        if (schoolId && classId) {
            $.ajax({
                url: baseUrl + 'examination/reports/merit-list/ajax-get-sections',
                type: 'POST',
                data: addCsrfToken({school_id: schoolId}),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        populateDropdown($('#section_id'), response.data, 'All Sections');
                        $('#section_id').prepend('<option value="">All Sections</option>');
                        $('#section_id').val('');
                    }
                }
            });
        }
        checkAllFieldsSelected();
    });
    
    $('#exam_id, #section_id, #limit').change(function() {
        checkAllFieldsSelected();
    });
    
    $('#generateBtn').click(function() {
        if (!checkAllFieldsSelected()) return;
        
        const schoolId  = $('#school_id').val();
        const yearId    = $('#year_id').val();
        const examId    = $('#exam_id').val();
        const classId   = $('#class_id').val();
        const sectionId = $('#section_id').val();
        const limit     = $('#limit').val();
        
        $('#meritResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-info"><i class="bi bi-hourglass-split"></i> Generating merit list...</div></div></div>');
        
        $.ajax({
            url: baseUrl + 'examination/reports/merit-list/ajax-generate-merit',
            type: 'POST',
            data: addCsrfToken({
                school_id: schoolId,
                year_id: yearId,
                exam_id: examId,
                class_id: classId,
                section_id: sectionId,
                limit: limit
            }),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#meritResult').html(response.html);
                } else {
                    $('#meritResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> ' + response.message + '</div></div></div>');
                }
            },
            error: function() {
                $('#meritResult').html('<div class="row mt-3"><div class="col-md-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle"></i> Error generating merit list. Please try again.</div></div></div>');
            }
        });
    });
});
</script>