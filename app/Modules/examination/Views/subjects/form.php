<?php

// is_edit: true for edit form and false for create form
if(isset($is_edit) && $is_edit == true){
    $header_title = lang('Subject.page_title_edit');
}else{
    $header_title = lang('Subject.page_title_new');
}

// Get Status
$status_value = isset($post_data['status']) ? $post_data['status'] : 1;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$post_data = $post_data ?? [];
$form_action = !empty($is_edit) && !empty($post_data['token'])
    ? 'examination/subjects/update/'.$post_data['token']
    : 'examination/subjects/store';


$grading_list           = $grading_list ?? [];

?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'id'      => 'subject_form',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="bi bi-book"></i> <?= $header_title; ?>
        </h3>
    </div>

    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= lang('Common.btn_save') ?>
        </button>

        <a href="<?= base_url('/examination/subjects') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i>
            <?= lang('Subject.back_to') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">

        <?php if(isset($validation)): ?>
            <div class="alert alert-danger">
                <?= $validation->listErrors(); ?>
            </div>
        <?php endif; ?>

        <!-- ====================== Basic Info ====================== -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-info-circle"></i> Basic Info
                </h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <!-- School -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label">School <span class="text-danger">*</span></label>
                        <select name="school_id" id="school_id" class="form-select" required>
                            <option value="">Select School</option>
                            <?php if (!empty($school_list)): ?>
                                <?php foreach ($school_list as $sid => $sname): ?>
                                    <option value="<?= $sid ?>" <?= (isset($post_data['school_id']) && $post_data['school_id'] == $sid) ? 'selected' : '' ?>>
                                        <?= esc($sname) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>

                        <!-- Note -->
                        <p class="mt-2 text-muted"> When you choose a school, Grading and Mark Distribution will be available based on that school. </p>
                    </div>

                    <!-- Subject Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_subject') ?> <span class="text-danger">*</span></label>
                        <input type="text"
                               name="title"
                               class="form-control"
                               required
                               value="<?= isset($post_data['title']) ? esc($post_data['title']) : '' ?>">
                    </div>

                    <!-- Short Title -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_short_title') ?></label>
                        <input type="text"
                               name="short_title"
                               class="form-control"
                               value="<?= isset($post_data['short_title']) ? esc($post_data['short_title']) : '' ?>">
                    </div>

                    <!-- Subject Code -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_subject_code') ?></label>
                        <input type="text"
                               name="subject_code"
                               class="form-control"
                               value="<?= isset($post_data['subject_code']) ? esc($post_data['subject_code']) : '' ?>">
                    </div>

                    <!-- Status -->
                    <div class="col-md-6 mb-3">
                        <?= $status_list ?>
                    </div>

                </div>
            </div>
        </div>

        <!-- ====================== Optional ====================== -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-toggle-on"></i> Optional
                </h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <!-- Optional Subject -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_optional') ?></label>
                        <select name="optional" class="form-select">
                            <option value="0" <?= (isset($post_data['optional']) && $post_data['optional'] == 0) ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= (isset($post_data['optional']) && $post_data['optional'] == 1) ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <!-- Enable Exclude Mark -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_enabled_exclude_mark') ?></label>
                        <select name="enabled_exclude_mark" class="form-select">
                            <option value="0" <?= (isset($post_data['enabled_exclude_mark']) && $post_data['enabled_exclude_mark'] == 0) ? 'selected' : '' ?>>No</option>
                            <option value="1" <?= (isset($post_data['enabled_exclude_mark']) && $post_data['enabled_exclude_mark'] == 1) ? 'selected' : '' ?>>Yes</option>
                        </select>
                    </div>

                    <!-- Exclude Mark -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_exclude_mark') ?></label>
                        <input type="number"
                               name="exclude_mark"
                               class="form-control"
                               min="0"
                               step="any"
                               value="<?= isset($post_data['exclude_mark']) ? esc($post_data['exclude_mark']) : '' ?>">
                    </div>

                    <!-- Exclude Grade Point -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_exclude_grade_point') ?></label>
                        <input type="number"
                               name="exclude_grade_point"
                               class="form-control"
                               min="0"
                               step="any"
                               value="<?= isset($post_data['exclude_grade_point']) ? esc($post_data['exclude_grade_point']) : '' ?>">
                    </div>

                    <!-- Exclude Percentage -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_exclude_percentage') ?></label>
                        <input type="number"
                               name="exclude_percentage"
                               class="form-control"
                               min="0"
                               max="100"
                               step="any"
                               value="<?= isset($post_data['exclude_percentage']) ? esc($post_data['exclude_percentage']) : '' ?>">
                    </div>

                </div>
            </div>
        </div>

        <!-- ====================== Combine Settings ====================== -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-link"></i> Combine Settings
                </h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <!-- Combine Group -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Combine Group</label>
                        <input type="text"
                               name="combine_group"
                               class="form-control"
                               placeholder="e.g. English, Science"
                               value="<?= isset($post_data['combine_group']) ? esc($post_data['combine_group']) : '' ?>">
                        <p class="mt-2 text-muted"> Group name for combining subjects (e.g. English = English 1st Paper + English 2nd Paper). </p>
                    </div>

                    <!-- Combine Order -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Combine Order</label>
                        <input type="number"
                               name="combine_order"
                               class="form-control"
                               min="0"
                               step="1"
                               value="<?= isset($post_data['combine_order']) ? esc($post_data['combine_order']) : '' ?>">
                        <p class="mt-2 text-muted"> Order within the combine group (1 = first subject, 2 = second subject, etc.). </p>
                    </div>

                    <!-- Combine Method -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Combine Method</label>
                        <select name="combine_method" class="form-select">
                            <option value="" <?= (!isset($post_data['combine_method']) || $post_data['combine_method'] == '') ? 'selected' : '' ?>>None</option>
                            <option value="single" <?= (isset($post_data['combine_method']) && $post_data['combine_method'] == 'single') ? 'selected' : '' ?>>Single</option>
                            <option value="average" <?= (isset($post_data['combine_method']) && $post_data['combine_method'] == 'average') ? 'selected' : '' ?>>Average</option>
                            <option value="sum" <?= (isset($post_data['combine_method']) && $post_data['combine_method'] == 'sum') ? 'selected' : '' ?>>Sum</option>
                        </select>
                        <p class="mt-2 text-muted"> How to combine subjects: Single (just the subject), Average (average of group), Sum (sum of group). </p>
                    </div>

                </div>
            </div>
        </div>

        <!-- ====================== Result Calculation ====================== -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-calculator"></i> Result Calculation
                </h5>
            </div>
            <div class="card-body">
                <div class="row">

                    <!-- Grade System -->
                    <div class="col-md-6 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_grade_system') ?> <span class="text-danger">*</span></label>
                        <select name="grade_system_id" id="grade_system_id" class="form-select" required>
                            <option value=""><?= lang('Subject.sys_select_grade_system') ?></option>
                            <?php if (!empty($grading_list)): ?>
                                <?php foreach ($grading_list as $gid => $gtitle): ?>
                                    <option value="<?= $gid ?>" <?= (isset($post_data['grade_system_id']) && $post_data['grade_system_id'] == $gid) ? 'selected' : '' ?>>
                                        <?= esc($gtitle) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                        <p class="mt-2 text-muted"> if it is not available after selecting a school, you can create it from <a href="<?= base_url('/school-owner/academics/grade-categories') ?>">Grading System</a> </p>
                    </div>

                    <!-- Order Number -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label"><?= lang('Subject.field_label_order_number') ?></label>
                        <input type="number"
                               name="order_number"
                               class="form-control"
                               min="0"
                               step="1"
                               value="<?= isset($post_data['order_number']) ? esc($post_data['order_number']) : '' ?>">
                        <p class="mt-2 text-muted"><i class="bi bi-lightbulb"></i> Used in result sheet subject ordering.</p>
                    </div>

                    <!-- Mark Calculation -->
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Mark Calculation</label>
                        <select name="mark_calculation" class="form-select">
                            <option value="1" <?= (!isset($post_data['mark_calculation']) || $post_data['mark_calculation'] == 1) ? 'selected' : '' ?>>Yes</option>
                            <option value="0" <?= (isset($post_data['mark_calculation']) && $post_data['mark_calculation'] == 0) ? 'selected' : '' ?>>No</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- ====================== Subject Distributions ====================== -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="bi bi-diagram-3"></i> Subject Distributions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="distributions_table">
                                <thead>
                                    <tr>
                                        <th>Mark Distribution</th>
                                        <th>Full Mark</th>
                                        <th>Pass Mark</th>
                                        <th>Weight %</th>
                                        <th>Sort Order</th>
                                        <th width="120">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($subject_distributions)): ?>
                                        <?php foreach ($subject_distributions as $dist): ?>
                                            <tr data-id="<?= $dist['id'] ?>">
                                                <td><?= esc($dist['distribution_name']) ?></td>
                                                <td><?= esc($dist['full_mark']) ?></td>
                                                <td><?= esc($dist['pass_mark']) ?></td>
                                                <td><?= esc($dist['weight_percent']) ?>%</td>
                                                <td><?= esc($dist['sort_order']) ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary btn-edit-distribution" data-id="<?= $dist['id'] ?>">
                                                        <i class="fa fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger btn-delete-distribution" data-id="<?= $dist['id'] ?>">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr id="no_data_row">
                                            <td colspan="6" class="text-center text-muted">No distributions found. Add one below.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <h6 class="mb-3">Add/Edit Distribution</h6>
                        <form id="distribution_form">
                            <input type="hidden" name="distribution_id" id="distribution_id" value="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Mark Distribution </label>
                                    <select name="mark_distribution_id" id="mark_distribution_id" class="form-select" >
                                        <option value="">Select Mark Distribution</option>
                                        <?php if (!empty($mark_distribution_list)): ?>
                                            <?php foreach ($mark_distribution_list as $did => $dname): ?>
                                                <option value="<?= $did ?>"><?= esc($dname) ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Full Mark </label>
                                    <input type="number" name="full_mark" id="full_mark" class="form-control" step="0.01" min="0" >
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Pass Mark </label>
                                    <input type="number" name="pass_mark" id="pass_mark" class="form-control" step="0.01" min="0" >
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Weight %</label>
                                    <input type="number" name="weight_percent" id="weight_percent" class="form-control" step="0.01" min="0" max="100">
                                </div>

                                <div class="col-md-2 mb-3">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" id="sort_order" class="form-control" min="0">
                                </div>

                                <div class="col-md-2 mb-3 d-flex align-items-end">
                                    <button type="submit" class="btn btn-sm btn-success w-100" id="btn_save_distribution">
                                        <i class="fa fa-save"></i> Save
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden"
               name="token"
               value="<?= isset($post_data['token']) ? esc($post_data['token']) : '' ?>">

    </div>
</div>

<?= form_close() ?>

<script type="text/javascript">
$(document).ready(function() {
    var csrfToken = '<?= csrf_hash() ?>';
    var subjectToken = '<?= isset($post_data['token']) ? $post_data['token'] : '' ?>';
    var isEdit = <?= isset($is_edit) && $is_edit ? 'true' : 'false' ?>;

    // Load mark distributions, grading systems, and subjects when school changes
    $('#school_id').on('change', function() {
        var schoolId = $(this).val();
        var $gradeSystemSelect = $('#grade_system_id');

        if (!schoolId) {
            $gradeSystemSelect.html('<option value=""><?= lang('Subject.sys_select_grade_system') ?></option>');
            return;
        }

        var postData = { school_id: schoolId };
        var excludeSubjectId = '<?= isset($post_data['id']) ? $post_data['id'] : 0 ?>';
        if (excludeSubjectId) {
            postData.exclude_subject_id = excludeSubjectId;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/subjects/getDropdownsBySchool') ?>',
            data: postData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);

                $gradeSystemSelect.html('<option value=""><?= lang('Subject.sys_select_grade_system') ?></option>');

                if (response.status) {
                    
                    if (response.grading_systems) {
                        var selectedGs = '<?= isset($post_data['grade_system']) ? $post_data['grade_system'] : '' ?>';
                        $.each(response.grading_systems, function(id, title) {
                            var selected = (id == selectedGs) ? 'selected' : '';
                            $gradeSystemSelect.append('<option value="' + id + '" ' + selected + '>' + title + '</option>');
                        });
                    }
                }
            }
        });
    });

    // ========================
    // Subject Distribution CRUD
    // ========================
    
    $('#distribution_form').on('submit', function(e) {
        e.preventDefault();
        
        if (!subjectToken) {
            alert('Please save the subject first before adding distributions.');
            return;
        }

        var formData = {
            token: subjectToken,
            edit_id: $('#distribution_id').val(),
            distribution_id: $('#mark_distribution_id').val(),
            full_mark: $('#full_mark').val(),
            pass_mark: $('#pass_mark').val(),
            weight_percent: $('#weight_percent').val(),
            sort_order: $('#sort_order').val()
        };

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/subjects/saveDistribution') ?>',
            data: formData,
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);
                
                if (response.status) {
                    alert(response.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });

    // Edit distribution
    $(document).on('click', '.btn-edit-distribution', function() {
        var distId = $(this).data('id');
        
        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/subjects/getDistribution') ?>',
            data: { id: distId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response) {
                if (response.status && response.data) {
                    $('#distribution_id').val(response.data.id);
                    $('#mark_distribution_id').val(response.data.distribution_id);
                    $('#full_mark').val(response.data.full_mark);
                    $('#pass_mark').val(response.data.pass_mark);
                    $('#weight_percent').val(response.data.weight_percent);
                    $('#sort_order').val(response.data.sort_order);
                    
                    $('html, body').animate({
                        scrollTop: $("#distribution_form").offset().top
                    }, 500);
                }
            }
        });
    });

    // Delete distribution
    $(document).on('click', '.btn-delete-distribution', function() {
        var distId = $(this).data('id');
        
        if (!confirm('Are you sure you want to delete this distribution?')) {
            return;
        }

        $.ajax({
            type: "post",
            dataType: "json",
            url: '<?= base_url('examination/subjects/deleteDistribution') ?>',
            data: { id: distId },
            headers: {
                'X-CSRF-TOKEN': csrfToken
            },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('input[name="rms_csrf_token"]').val(csrfToken);
                
                if (response.status) {
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                }
            }
        });
    });
});
</script>
