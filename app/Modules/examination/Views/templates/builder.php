<?php 
$placeholders = $placeholders ?? [];
$default_template = $default_template ?? '';
$edit_template = $edit_template ?? null;
$school_list = $school_list ?? [];
$selected_school = $selected_school ?? 0;
$is_saas_admin = $is_saas_admin ?? false;
?>

<?= form_open_multipart('examination/templates/save', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'template_form', 
    'method'  => 'post', 
]); ?>
<?= csrf_field() ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />
<input type="hidden" name="template_id" id="template_id_input" value="<?= $edit_template->id ?? '' ?>" />

<?= get_system_message(); ?>

<div class="row">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-layout-text-window"></i> <?= lang('ResultTemplate.heading_builder') ?></h3>
    </div>
    <div class="col-sm-6 text-end pt-0">
        <div class="row g-2 justify-content-end">
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-success" id="btn_save"><i class="fa fa-save"></i> <?= lang('Common.btn_save') ?></button>
            </div>
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <button type="submit" class="btn btn-primary" id="btn_save_back" name="save_back" value="1"><i class="fa fa-save"></i> <?= lang('ResultTemplate.btn_save_and_back') ?></button>
            </div>
            <div class="col-auto">
                <label class="form-label d-block">&nbsp;</label>
                <a href="<?= base_url('examination/templates') ?>" class="btn btn-secondary"><i class="fa fa-list"></i> <?= lang('ResultTemplate.btn_back_to_list') ?></a>
            </div>
        </div>
    </div>
</div>

<!-- Tips Section -->
<div class="row mt-3">
    <div class="col-md-12">
        <div class="alert alert-info">
            <strong><?= lang('Common.note') ?>:</strong> <?= lang('ResultTemplate.subject_table_placeholder') ?>
        </div>
    </div>
</div>

<div class="row mt-3">
    <!-- Placeholders Panel -->
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= lang('ResultTemplate.available_placeholders') ?></h5>
            </div>
            <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                <?php foreach ($placeholders as $group => $items): ?>
                <h6 class="text-muted mt-2"><?= ucfirst($group) ?></h6>
                <div class="list-group list-group-flush mb-2">
                    <?php foreach ($items as $placeholder => $description): ?>
                    <div class="list-group-item py-1 px-2" style="cursor:pointer; font-size:12px;" 
                         onclick="insertPlaceholder('<?= $placeholder ?>')" 
                         title="<?= esc($description) ?>">
                        <code><?= $placeholder ?></code>
                        <small class="text-muted d-block"><?= esc($description) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Template Editor -->
    <div class="col-md-9">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><?= lang('ResultTemplate.template_editor') ?></h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="field_school_id"><?= lang('Common.filter_school') ?> <span class="text-danger">*</span></label>
                    <select name="school_id" id="field_school_id" class="form-control" required>
                        <?php if ($is_saas_admin): ?>
                            <option value="0" <?= (isset($edit_template) && (int) $edit_template->school_id === 0) || (!isset($edit_template) && (int) $selected_school === 0) ? 'selected' : '' ?>>All Schools</option>
                        <?php else: ?>
                            <option value=""><?= lang('Common.select_school') ?></option>
                        <?php endif; ?>
                        <?php if (!empty($school_list)): ?>
                            <?php foreach ($school_list as $sid => $sname): ?>
                                <option value="<?= $sid ?>" <?= (isset($edit_template) && $edit_template->school_id == $sid) || $selected_school == $sid ? 'selected' : '' ?>><?= esc($sname) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="template_name_input"><?= lang('ResultTemplate.template_name') ?></label>
                    <input type="text" name="template_name" id="template_name_input" class="form-control" 
                           placeholder="<?= lang('ResultTemplate.enter_template_name') ?>" 
                           value="<?= $edit_template->template_name ?? '' ?>" required>
                </div>
                <div class="mb-3">
                    <label for="template_type"><?= lang('ResultTemplate.template_type') ?></label>
                    <select name="template_type" id="template_type" class="form-control" required>
                        <option value=""><?= lang('ResultTemplate.select_template_type') ?></option>
                        <option value="result_card" <?= (isset($edit_template) && $edit_template->template_type === 'result_card') ? 'selected' : '' ?>>Result Card</option>
                        <option value="transcript" <?= (isset($edit_template) && $edit_template->template_type === 'transcript') ? 'selected' : '' ?>>Transcript</option>
                        <option value="certificate" <?= (isset($edit_template) && $edit_template->template_type === 'certificate') ? 'selected' : '' ?>>Certificate</option>
                        <option value="admit_card" <?= (isset($edit_template) && $edit_template->template_type === 'admit_card') ? 'selected' : '' ?>>Admit Card</option>
                        <option value="id_card" <?= (isset($edit_template) && $edit_template->template_type === 'id_card') ? 'selected' : '' ?>>ID Card</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="orientation"><?= lang('ResultTemplate.orientation') ?? 'Orientation' ?></label>
                    <select name="orientation" id="orientation" class="form-control">
                        <option value=""><?= lang('ResultTemplate.select_orientation') ?? 'Select Orientation' ?></option>
                        <option value="portrait" <?= (isset($edit_template) && $edit_template->orientation === 'portrait') ? 'selected' : '' ?>>Portrait</option>
                        <option value="landscape" <?= (isset($edit_template) && $edit_template->orientation === 'landscape') ? 'selected' : '' ?>>Landscape</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="template_content"><?= lang('ResultTemplate.template_content') ?></label>
                    <textarea name="template_content" id="template_content" class="form-control" rows="18" 
                              style="font-family:monospace; font-size:13px;" 
                              placeholder="<?= lang('ResultTemplate.template_content_placeholder') ?>"><?= htmlspecialchars($edit_template->template_content ?? $default_template) ?></textarea>
                </div>
                <div class="mb-3">
                    <label for="template_style"><?= lang('ResultTemplate.template_style') ?></label>
                    <textarea name="template_style" id="template_style" class="form-control" rows="10" 
                              style="font-family:monospace; font-size:13px;" 
                              placeholder="/* CSS styles for template */"><?= htmlspecialchars($edit_template->template_style ?? '') ?></textarea>
                    <small class="form-text text-muted"><?= lang('ResultTemplate.template_style_help') ?></small>
                </div>
                <div class="mb-3">
                    <label for="template_bg"><?= lang('ResultTemplate.template_background') ?? 'Template Background Image' ?></label>
                    <input type="file" name="template_bg" id="template_bg" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Upload a background image for the template (JPG, PNG, GIF). Leave empty to keep existing.</small>
                    <?php if (isset($edit_template) && !empty($edit_template->template_bg)): ?>
                    <div class="mt-2">
                        <img src="<?= base_url('public/uploads/templates/'  .  $edit_template->template_bg) ?>" alt="Current Background" style="max-width:200px;max-height:100px;border:1px solid #ddd;border-radius:4px;">
                        <br>
                        <small class="text-muted">Current background: <?= esc($edit_template->template_bg) ?></small>
                        <label class="ms-2 text-danger" style="cursor:pointer;">
                            <input type="checkbox" name="remove_template_bg" value="1"> Remove
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="class_teacher_signature"><?= lang('ResultTemplate.class_teacher_signature') ?? 'Class Teacher Signature Image' ?></label>
                    <input type="file" name="class_teacher_signature" id="class_teacher_signature" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Upload class teacher signature image (JPG, PNG, GIF). Leave empty to keep existing.</small>
                    <?php if (isset($edit_template) && !empty($edit_template->class_teacher_signature)): ?>
                    <div class="mt-2">
                        <img src="<?= base_url('public/uploads/templates/' . $edit_template->class_teacher_signature) ?>" alt="Class Teacher Signature" style="max-width:200px;max-height:80px;border:1px solid #ddd;border-radius:4px;">
                        <br>
                        <small class="text-muted">Current: <?= esc($edit_template->class_teacher_signature) ?></small>
                        <label class="ms-2 text-danger" style="cursor:pointer;">
                            <input type="checkbox" name="remove_class_teacher_signature" value="1"> Remove
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="principal_signature"><?= lang('ResultTemplate.principal_signature') ?? 'Principal Signature Image' ?></label>
                    <input type="file" name="principal_signature" id="principal_signature" class="form-control" accept="image/*">
                    <small class="form-text text-muted">Upload principal signature image (JPG, PNG, GIF). Leave empty to keep existing.</small>
                    <?php if (isset($edit_template) && !empty($edit_template->principal_signature)): ?>
                    <div class="mt-2">
                        <img src="<?= base_url('public/uploads/templates/' . $edit_template->principal_signature) ?>" alt="Principal Signature" style="max-width:200px;max-height:80px;border:1px solid #ddd;border-radius:4px;">
                        <br>
                        <small class="text-muted">Current: <?= esc($edit_template->principal_signature) ?></small>
                        <label class="ms-2 text-danger" style="cursor:pointer;">
                            <input type="checkbox" name="remove_principal_signature" value="1"> Remove
                        </label>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="is_default" id="is_default" class="form-check-input" value="1" 
                           <?= (isset($edit_template) && $edit_template->is_default) ? 'checked' : '' ?>>
                    <label for="is_default" class="form-check-label"><?= lang('ResultTemplate.set_as_default') ?></label>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="support_multiple_exams" id="support_multiple_exams" class="form-check-input" value="1"
                           <?= (isset($edit_template) && $edit_template->support_multiple_exams) ? 'checked' : '' ?>>
                    <label for="support_multiple_exams" class="form-check-label"><?= lang('ResultTemplate.support_multiple_exams') ?></label>
                    <small class="form-text text-muted d-block"><?= lang('ResultTemplate.support_multiple_exams_help') ?></small>
                </div>
                <div class="mb-3 form-check">
                    <input type="checkbox" name="support_aggregated_result" id="support_aggregated_result" class="form-check-input" value="1"
                           <?= (isset($edit_template) && $edit_template->support_aggregated_result) ? 'checked' : '' ?>>
                    <label for="support_aggregated_result" class="form-check-label"><?= lang('ResultTemplate.support_aggregated_result') ?></label>
                    <small class="form-text text-muted d-block"><?= lang('ResultTemplate.support_aggregated_result_help') ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
<?= form_close() ?>

<script type="text/javascript">
function insertPlaceholder(placeholder) {
    var textarea = document.getElementById('template_content');
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var text = textarea.value;
    textarea.value = text.substring(0, start) + placeholder + text.substring(end);
    textarea.focus();
    textarea.selectionStart = start + placeholder.length;
    textarea.selectionEnd = start + placeholder.length;
}

function resetTemplate() {
    if (confirm('<?= lang('ResultTemplate.reset_confirmation') ?>')) {
        $('#template_id_input').val('');
        $('#template_name_input').val('');
        $('#orientation').val('');
        $('#template_type').val('');
        $('#template_content').val(<?= json_encode($default_template) ?>);
        $('#template_style').val('');
        $('#is_default').prop('checked', false);
        $('#support_multiple_exams').prop('checked', false);
        $('#support_aggregated_result').prop('checked', false);
        $('#field_school_id').val('');
    }
}
</script>
