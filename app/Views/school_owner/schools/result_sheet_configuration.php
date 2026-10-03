<div class="container-fluid">

    <!-- Page Header -->
    <div class=" mb-4">
            <h3 class="mb-1">Result Sheet Settings</h3>
            <p class="text-muted mb-0">
                Configure how student result sheets will be displayed and generated.
            </p>
    </div>

    <!-- Grading System -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-graduation-cap me-2"></i>
                Grading System
            </h5>
        </div>

        <div class="card-body">

            <div class="row">
                <div class="col-md-12 mb-3">
                    <label class="form-label fw-semibold">Grading System</label>

                    <select name="grading_system" class="form-select">
                        <option value="">-- Select Grading System --</option>
                        <?php if (!empty($grade_systems)): ?>
                            <?php foreach ($grade_systems as $gs): ?>
                                <option value="<?= $gs->id ?>" <?= (isset($post_data['grading_system']) && $post_data['grading_system'] == $gs->id) ? 'selected' : '' ?>>
                                    <?= esc($gs->title) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
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
                <?= result_switch('show_highest_mark', 'Show Highest Mark', $post_data['show_highest_mark'] ?? 0, 'Display highest mark on result sheet') ?>
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

    <!-- Principal Signature -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">
                <i class="fas fa-signature me-2"></i>
                Principal Signature
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

            </div>

        </div>
    </div>

</div>