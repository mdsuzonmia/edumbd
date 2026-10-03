<?php 
$school_list = $school_list ?? [];
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-bar-graph"></i> Aggregate Result</h3>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="field_year_id" class="form-label">Year <span class="required">*</span></label>
                        <select name="year_id" id="field_year_id" class="form-control" required>
                            <option value="">Select Year</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="field_class_id" class="form-label">Class <span class="required">*</span></label>
                        <select name="class_id" id="field_class_id" class="form-control" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label d-block">&nbsp;</label>
                        <button type="button" id="btn_generate" class="btn btn-success"><i class="fa fa-cogs"></i> Generate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="result" class="mt-3"></div>

<script>
$(document).ready(function() {
    // Load academic data when school changes
    $('#field_school_id').on('change', function() {
        var school_id = $(this).val();
        if (school_id) {
            $.ajax({
                type: "post",
                dataType: "json",
                url: '<?= base_url('examination/results/getAcademicDataBySchool') ?>',
                data: { school_id: school_id },
                success: function(response) {
                    if (response.status) {
                        populateSelect('#field_year_id', response.year_list);
                        populateSelect('#field_class_id', response.class_list);
                    }
                }
            });
        } else {
            ['#field_year_id', '#field_class_id'].forEach(function(sel) {
                $(sel).html('<option value="">Select...</option>');
            });
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
});
</script>