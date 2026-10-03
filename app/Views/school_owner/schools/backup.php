<div class="container-fluid">

    <!-- Page Header -->
    <div class=" mb-4">
            <h3 class="mb-1">Result Sheet Settings</h3>
            <p class="text-muted mb-0">
                Configure how student result sheets will be displayed and generated.
            </p>
    </div>

    <!-- General Settings -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-cog me-2"></i>
                General Settings
            </h5>
        </div>

        <div class="card-body">

            <!-- Enable Result Sheet -->
            <div class="setting-item">
                <div>
                    <h6 class="mb-1">Enable Result Sheet</h6>
                    <small class="text-muted">
                        Allow generation and printing of result sheets.
                    </small>
                </div>

                <div class="form-check form-switch">
                    <input class="form-check-input"
                           type="checkbox"
                           name="result_enabled"
                           value="1"
                           <?= !empty($post_data['result_enabled']) ? 'checked' : '' ?>>
                </div>
            </div>

            <div class="row">

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-semibold">Result Title</label>
                    <input type="text"
                           name="result_title"
                           class="form-control"
                           value="<?= esc($post_data['result_title'] ?? '') ?>">
                </div>

                <div class="col-md-12 mb-3">
                    <label class="form-label fw-semibold">Result Header</label>
                    <textarea class="form-control"
                              name="result_header"
                              rows="5"><?= esc($post_data['result_header'] ?? '') ?></textarea>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Result Footer</label>
                    <textarea class="form-control"
                              name="result_footer"
                              rows="4"><?= esc($post_data['result_footer'] ?? '') ?></textarea>
                </div>

            </div>

        </div>
    </div>

    <!-- Grade Settings -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-graduation-cap me-2"></i>
                Grade & Result Display
            </h5>
        </div>

        <div class="card-body">

            <div class="row">

                <?= result_switch('show_grading_chart', 'Show Grading Chart', $post_data['show_grading_chart'] ?? 0, 'Display grading chart on result sheet') ?>
                <?= result_switch('show_gp', 'Show GPA', $post_data['show_gp'] ?? 0, 'Display GPA on result sheet') ?>
                <?= result_switch('show_grade', 'Show Grade', $post_data['show_grade'] ?? 0, 'Display grade on result sheet') ?>
                <?= result_switch('show_letter_grade', 'Show Letter Grade', $post_data['show_letter_grade'] ?? 0, 'Display letter grade on result sheet') ?>
                <?= result_switch('show_grading_remark', 'Show Grade Remark', $post_data['show_grading_remark'] ?? 0, 'Display grade remark on result sheet') ?>
                <?= result_switch('show_highest_mark', 'Show Highest Mark', $post_data['show_highest_mark'] ?? 0, 'Display highest mark on result sheet') ?>

            </div>

            <div class="mt-4">
                <label class="form-label fw-semibold">Grading System</label>

                <select name="grading_system" class="form-select">
                    <option value="1" <?= ($post_data['grading_system'] ?? '') == 1 ? 'selected' : '' ?>>Grading System 1</option>
                    <option value="2" <?= ($post_data['grading_system'] ?? '') == 2 ? 'selected' : '' ?>>Grading System 2</option>
                    <option value="3" <?= ($post_data['grading_system'] ?? '') == 3 ? 'selected' : '' ?>>Grading System 3</option>
                </select>
            </div>

        </div>
    </div>

    <!-- Student Information -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-user-graduate me-2"></i>
                Student Information
            </h5>
        </div>

        <div class="card-body">

            <div class="row">

                <?= result_switch('show_avatar', 'Student Photo', $post_data['show_avatar'] ?? 0, 'Display student photo on result sheet') ?>
                <?= result_switch('show_attendance_info', 'Attendance Information', $post_data['show_attendance_info'] ?? 0, 'Show attendance summary') ?>
                <?= result_switch('show_school_days', 'School Days', $post_data['show_school_days'] ?? 0, 'Display total school days') ?>
                <?= result_switch('show_present_days', 'Present Days', $post_data['show_present_days'] ?? 0, 'Display present days count') ?>
                <?= result_switch('show_absent_days', 'Absent Days', $post_data['show_absent_days'] ?? 0, 'Display absent days count') ?>

            </div>

        </div>
    </div>

    <!-- Rankings -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-trophy me-2"></i>
                Rankings & Merit Position
            </h5>
        </div>

        <div class="card-body">

            <div class="row">

                <?= result_switch('show_rank', 'Overall Rank', $post_data['show_rank'] ?? 0, 'Display overall rank') ?>
                <?= result_switch('show_exam_rank', 'Exam Rank', $post_data['show_exam_rank'] ?? 0, 'Display exam-wise rank') ?>
                <?= result_switch('show_class_rank', 'Class Rank', $post_data['show_class_rank'] ?? 0, 'Display class rank') ?>
                <?= result_switch('show_section_rank', 'Section Rank', $post_data['show_section_rank'] ?? 0, 'Display section rank') ?>

            </div>

        </div>
    </div>
    <!-- Rank Calculation Format -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-calculator me-2"></i>
                Rank Calculation Format
            </h5>
        </div>

        <div class="card-body">

            <div class="row">
                <?= result_switch('enable_rank_by_total_mark', 'Enable Rank by Total Obtained mark', $post_data['enable_rank_by_total_mark'] ?? 0, 'Calculate rank based on total obtained marks') ?>
                <?= result_switch('enable_rank_by_percentage', 'Enable rank by percentage', $post_data['enable_rank_by_percentage'] ?? 0, 'Calculate rank based on percentage') ?>
                <?= result_switch('enable_rank_by_grade_point', 'Enable Rank by grade_point', $post_data['enable_rank_by_grade_point'] ?? 0, 'Calculate rank based on grade point') ?>
            </div>

            <hr class="my-3">

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">First Calculate by</label>
                    <select name="rank_calc_first" class="form-select">
                        <option value="total_obtained_mark" <?= (!empty($post_data['rank_calc_first']) && $post_data['rank_calc_first'] == 'total_obtained_mark') ? 'selected' : '' ?>>Total Obtained mark</option>
                        <option value="percentage" <?= (!empty($post_data['rank_calc_first']) && $post_data['rank_calc_first'] == 'percentage') ? 'selected' : '' ?>>Percentage</option>
                        <option value="grade_point" <?= (!empty($post_data['rank_calc_first']) && $post_data['rank_calc_first'] == 'grade_point') ? 'selected' : '' ?>>Grade Point</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Then Calculate by</label>
                    <select name="rank_calc_second" class="form-select">
                        <option value="total_obtained_mark" <?= (!empty($post_data['rank_calc_second']) && $post_data['rank_calc_second'] == 'total_obtained_mark') ? 'selected' : '' ?>>Total Obtained mark</option>
                        <option value="percentage" <?= (!empty($post_data['rank_calc_second']) && $post_data['rank_calc_second'] == 'percentage') ? 'selected' : '' ?>>Percentage</option>
                        <option value="grade_point" <?= (!empty($post_data['rank_calc_second']) && $post_data['rank_calc_second'] == 'grade_point') ? 'selected' : '' ?>>Grade Point</option>
                    </select>
                </div>

                <div class="col-md-4 mb-3">
                    <label class="form-label fw-semibold">Then Calculate By</label>
                    <select name="rank_calc_third" class="form-select">
                        <option value="total_obtained_mark" <?= (!empty($post_data['rank_calc_third']) && $post_data['rank_calc_third'] == 'total_obtained_mark') ? 'selected' : '' ?>>Total Obtained mark</option>
                        <option value="percentage" <?= (!empty($post_data['rank_calc_third']) && $post_data['rank_calc_third'] == 'percentage') ? 'selected' : '' ?>>Percentage</option>
                        <option value="grade_point" <?= (!empty($post_data['rank_calc_third']) && $post_data['rank_calc_third'] == 'grade_point') ? 'selected' : '' ?>>Grade Point</option>
                    </select>
                </div>
            </div>

        </div>
    </div>



    <!-- Signatures -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-signature me-2"></i>
                Signatures & Remarks
            </h5>
        </div>

        <div class="card-body">

            <div class="row">

                <!-- Add Principal Signature file upload field -->
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-semibold">Principal Signature Image</label>
                    <input type="file"
                           name="principal_signature"
                           class="form-control"
                           onchange="readURL(this, 'signature');">

                    <?php
                    $signature_path = !empty($post_data['principal_signature_img'])
                        ? base_url('uploads/' . $post_data['principal_signature_img'])
                        : '';
                    ?>

                    <?php if (!empty($signature_path)): ?>
                        <div class="mt-2">
                            <img src="<?= $signature_path ?>"
                                 id="preview_signature"
                                 style="max-width:180px; max-height:80px;"
                                 class="img-thumbnail">
                        </div>
                    <?php else: ?>
                        <div class="mt-2" id="preview_signature_container" style="display:none;">
                            <img src=""
                                 id="preview_signature"
                                 style="max-width:180px; max-height:80px;"
                                 class="img-thumbnail">
                        </div>
                    <?php endif; ?>
                </div>

                <?= result_switch('show_principal_signature', 'Principal Signature', $post_data['show_principal_signature'] ?? 0, 'Show principal signature') ?>
                <?= result_switch('show_teacher_signature', 'Teacher Signature', $post_data['show_teacher_signature'] ?? 0, 'Show teacher signature') ?>
                <?= result_switch('show_principal_remarks', 'Principal Remarks', $post_data['show_principal_remarks'] ?? 0, 'Show principal remarks') ?>
                <?= result_switch('show_teacher_remarks', 'Teacher Remarks', $post_data['show_teacher_remarks'] ?? 0, 'Show teacher remarks') ?>

            </div>

        </div>
    </div>

    

</div>