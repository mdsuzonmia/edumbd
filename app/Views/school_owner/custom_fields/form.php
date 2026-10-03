<?php
if (isset($is_edit) && $is_edit == true) {
    $header_title = lang('System.page_title_cf_edit') ?: 'Edit Custom Field';
} else {
    $header_title = lang('System.page_title_cf_create') ?: 'Create Custom Field';
}

$f = $field ?? null;
$post = $post_data ?? [];

$label          = $f->label ?? $post['label'] ?? '';
$field_key      = $f->field_key ?? $post['field_key'] ?? '';
$entity_id      = $f->entity_id ?? $post['entity_id'] ?? '';
$group_id       = $f->group_id ?? $post['group_id'] ?? '';
$field_type_id  = $f->field_type_id ?? $post['field_type_id'] ?? '';
$placeholder    = $f->placeholder ?? $post['placeholder'] ?? '';
$help_text      = $f->help_text ?? $post['help_text'] ?? '';
$default_value  = $f->default_value ?? $post['default_value'] ?? '';
$validation_rules = $f->validation_rules ?? $post['validation_rules'] ?? '';
$sort_order     = $f->sort_order ?? $post['sort_order'] ?? '0';
$status_value   = $f->status ?? $post['status'] ?? 1;

$is_required     = (int) ($f->is_required ?? $post['is_required'] ?? 0);
$is_unique       = (int) ($f->is_unique ?? $post['is_unique'] ?? 0);
$is_searchable   = (int) ($f->is_searchable ?? $post['is_searchable'] ?? 0);
$show_on_reg     = (int) ($f->show_on_registration ?? $post['show_on_registration'] ?? 0);
$show_on_adm     = (int) ($f->show_on_admission ?? $post['show_on_admission'] ?? 0);
$allow_import    = (int) ($f->allow_bulk_import ?? $post['allow_bulk_import'] ?? 1);
$allow_export    = (int) ($f->allow_bulk_export ?? $post['allow_bulk_export'] ?? 1);
$show_on_profile = (int) ($f->show_on_profile ?? $post['show_on_profile'] ?? 1);
$show_on_list    = (int) ($f->show_on_list ?? $post['show_on_list'] ?? 0);
$show_on_idcard  = (int) ($f->show_on_idcard ?? $post['show_on_idcard'] ?? 0);
$show_on_result  = (int) ($f->show_on_resultcard ?? $post['show_on_resultcard'] ?? 0);

$status_list = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$form_action = !empty($is_edit) && !empty($f->id ?? 0)
    ? 'school-owner/custom-fields/update/' . ($f->token ?? '')
    : 'school-owner/custom-fields/store';

$fieldId = $f->id ?? 0;
$token = isset($token) ? $token : '';
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="fa fa-cubes"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('school-owner/custom-fields'); ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back') ?: 'Back'; ?>
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> <?= lang('Common.btn_save'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= get_system_message(); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><?= lang('Common.text_field_details') ?: 'Field Details'; ?></div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_school') ?: 'School'; ?> <span class="text-danger">*</span></label>
                    <select name="school_id" class="form-control" required>
                        <option value=""><?= lang('Common.select') ?: '-- Select --'; ?></option>
                        <?php foreach ($school_list as $sid => $sname): ?>
                            <option value="<?= $sid; ?>" <?= ((int) $selected_school === (int) $sid) ? 'selected' : ''; ?>>
                                <?= esc($sname); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_label') ?: 'Label'; ?> <span class="text-danger">*</span></label>
                    <input type="text" name="label" class="form-control" value="<?= esc($label); ?>" required maxlength="255">
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_field_key') ?: 'Field Key'; ?> <span class="text-danger">*</span></label>
                            <input type="text" name="field_key" class="form-control" value="<?= esc($field_key); ?>" required maxlength="150">
                            <small class="text-muted"><?= lang('Common.help_field_key') ?: 'Unique per school. Use lowercase, underscores.'; ?></small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_sort_order') ?: 'Sort Order'; ?></label>
                            <input type="number" name="sort_order" class="form-control" value="<?= esc($sort_order); ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_entity') ?: 'Entity'; ?> <span class="text-danger">*</span></label>
                            <select name="entity_id" class="form-control" required>
                                <option value=""><?= lang('Common.select') ?: '-- Select --'; ?></option>
                                <?php foreach ($entities as $e): ?>
                                    <option value="<?= $e->id; ?>" <?= ((int) $entity_id === (int) $e->id) ? 'selected' : ''; ?>>
                                        <?= esc($e->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_group') ?: 'Group'; ?></label>
                            <select name="group_id" class="form-control">
                                <option value=""><?= lang('Common.none') ?: '-- None --'; ?></option>
                                <?php foreach ($groups as $g): ?>
                                    <option value="<?= $g->id; ?>" <?= ((int) $group_id === (int) $g->id) ? 'selected' : ''; ?>>
                                        <?= esc($g->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_field_type') ?: 'Field Type'; ?> <span class="text-danger">*</span></label>
                            <select name="field_type_id" class="form-control" required>
                                <option value=""><?= lang('Common.select') ?: '-- Select --'; ?></option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?= $t->id; ?>" <?= ((int) $field_type_id === (int) $t->id) ? 'selected' : ''; ?>>
                                        <?= esc($t->title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group mb-3">
                            <label class="form-label"><?= lang('Common.th_placeholder') ?: 'Placeholder'; ?></label>
                            <input type="text" name="placeholder" class="form-control" value="<?= esc($placeholder); ?>" maxlength="255">
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_help_text') ?: 'Help Text'; ?></label>
                    <textarea name="help_text" class="form-control" rows="2"><?= esc($help_text); ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_default_value') ?: 'Default Value'; ?></label>
                    <input type="text" name="default_value" class="form-control" value="<?= esc($default_value); ?>">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_validation_rules') ?: 'Validation Rules'; ?></label>
                    <input type="text" name="validation_rules" class="form-control" value="<?= esc($validation_rules); ?>" maxlength="1000">
                    <small class="text-muted"><?= lang('Common.help_validation_rules') ?: 'Comma-separated rules e.g. min_length[3],max_length[50]'; ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header"><?= lang('Common.text_publish') ?: 'Publish'; ?></div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_status'); ?></label>
                    <?= $status_list; ?>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><?= lang('Common.text_options') ?: 'Options'; ?></div>
            <div class="card-body">
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_required" value="1" class="form-check-input" id="is_required" <?= $is_required ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_required"><?= lang('Common.is_required') ?: 'Required'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_unique" value="1" class="form-check-input" id="is_unique" <?= $is_unique ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_unique"><?= lang('Common.is_unique') ?: 'Unique Value'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="is_searchable" value="1" class="form-check-input" id="is_searchable" <?= $is_searchable ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_searchable"><?= lang('Common.is_searchable') ?: 'Searchable'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_registration" value="1" class="form-check-input" id="show_on_registration" <?= $show_on_reg ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_registration"><?= lang('Common.show_on_registration') ?: 'Show on Registration'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_admission" value="1" class="form-check-input" id="show_on_admission" <?= $show_on_adm ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_admission"><?= lang('Common.show_on_admission') ?: 'Show on Admission'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="allow_bulk_import" value="1" class="form-check-input" id="allow_bulk_import" <?= $allow_import ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_bulk_import"><?= lang('Common.allow_bulk_import') ?: 'Allow Bulk Import'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="allow_bulk_export" value="1" class="form-check-input" id="allow_bulk_export" <?= $allow_export ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="allow_bulk_export"><?= lang('Common.allow_bulk_export') ?: 'Allow Bulk Export'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_profile" value="1" class="form-check-input" id="show_on_profile" <?= $show_on_profile ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_profile"><?= lang('Common.show_on_profile') ?: 'Show on Profile'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_list" value="1" class="form-check-input" id="show_on_list" <?= $show_on_list ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_list"><?= lang('Common.show_on_list') ?: 'Show on List'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_idcard" value="1" class="form-check-input" id="show_on_idcard" <?= $show_on_idcard ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_idcard"><?= lang('Common.show_on_idcard') ?: 'Show on ID Card'; ?></label>
                </div>
                <div class="form-check mb-2">
                    <input type="checkbox" name="show_on_resultcard" value="1" class="form-check-input" id="show_on_resultcard" <?= $show_on_result ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="show_on_resultcard"><?= lang('Common.show_on_resultcard') ?: 'Show on Result Card'; ?></label>
                </div>
            </div>
        </div>

        <div class="card" id="options-card" style="<?= (!empty($is_edit) && !empty($options)) ? '' : 'display: none;'; ?>">
            <div class="card-header"><?= lang('Common.text_field_options') ?: 'Field Options'; ?></div>
            <div class="card-body" id="options-container">
                <table class="table table-sm" id="options-table">
                    <thead>
                        <tr>
                            <th><?= lang('Common.th_label') ?: 'Label'; ?></th>
                            <th><?= lang('Common.th_value') ?: 'Value'; ?></th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($options)): ?>
                            <?php foreach ($options as $opt): ?>
                                <tr>
                                    <td><input type="text" name="options[<?= $opt->id; ?>][label]" class="form-control form-control-sm" value="<?= esc($opt->option_label); ?>"></td>
                                    <td><input type="text" name="options[<?= $opt->id; ?>][value]" class="form-control form-control-sm" value="<?= esc($opt->option_value); ?>"></td>
                                    <td><button type="button" class="btn btn-sm btn-danger remove-option"><i class="fa fa-times"></i></button></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="add-option"><i class="fa fa-plus"></i> <?= lang('Common.add_option') ?: 'Add Option'; ?></button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="token" value="<?= esc($token) ?>">

<?= form_close(); ?>

<script>
$(document).ready(function() {
    var optionIndex = <?= !empty($options) ? count($options) + 1 : 0; ?>;
    
    // Options card toggle based on field type
    var optionTypeSlugs = ['select', 'checkbox', 'radio', 'multi_select', 'multi-select'];
    
    function toggleOptionsCard() {
        var selectedTypeId = $('select[name="field_type_id"]').val();
        if (!selectedTypeId) {
            $('#options-card').hide();
            return;
        }
        // Get the selected option text to check slug
        var selectedText = $('select[name="field_type_id"] option:selected').text().toLowerCase().replace(/\s+/g, '_');
        var isOptionType = false;
        $.each(optionTypeSlugs, function(i, slug) {
            if (selectedText.indexOf(slug) !== -1) {
                isOptionType = true;
                return false;
            }
        });
        if (isOptionType) {
            $('#options-card').show();
        } else {
            $('#options-card').hide();
        }
    }

    $('select[name="field_type_id"]').on('change', function() {
        toggleOptionsCard();
    });

    toggleOptionsCard();

    // Helper to get current CSRF token
    function getCsrfToken() {
        return $('input[name="rms_csrf_token"]').val();
    }

    // Helper to update CSRF token from response headers
    function updateCsrfToken(jqXHR) {
        if (jqXHR) {
            var newToken = jqXHR.getResponseHeader('X-CSRF-TOKEN');
            if (newToken) {
                $('input[name="rms_csrf_token"]').val(newToken);
            }
        }
    }

    // Load groups when entity changes
    function loadGroupsByEntity(entityId, selectedGroupId) {
        var $groupSelect = $('select[name="group_id"]');
        
        if (!entityId) {
            $groupSelect.html('<option value="">-- None --</option>');
            return;
        }

        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('school-owner/custom-fields/getGroupsByEntity'); ?>',
            data: { entity_id: entityId, rms_csrf_token: getCsrfToken() },
            headers: { 'X-CSRF-TOKEN': getCsrfToken() },
            success: function(response, textStatus, jqXHR) {
                updateCsrfToken(jqXHR);
                $groupSelect.html('<option value="">-- None --</option>');
                if (response.status && response.groups) {
                    $.each(response.groups, function(i, g) {
                        var selected = (selectedGroupId && parseInt(g.id) === parseInt(selectedGroupId)) ? 'selected' : '';
                        $groupSelect.append('<option value="' + g.id + '" ' + selected + '>' + g.title + '</option>');
                    });
                }
            }
        });
    }

    $('select[name="entity_id"]').on('change', function() {
        loadGroupsByEntity($(this).val(), 0);
    });

    // On page load, if entity is already selected (edit mode), load groups
    var initialEntityId = <?= (int) ($entity_id ?? 0); ?>;
    var initialGroupId = <?= (int) ($group_id ?? 0); ?>;
    if (initialEntityId) {
        loadGroupsByEntity(initialEntityId, initialGroupId);
    }

    // Add option row
    $('#add-option').on('click', function() {
        var html = '<tr>' +
            '<td><input type="text" name="options[' + optionIndex + '][label]" class="form-control form-control-sm" placeholder="Label"></td>' +
            '<td><input type="text" name="options[' + optionIndex + '][value]" class="form-control form-control-sm" placeholder="Value"></td>' +
            '<td><button type="button" class="btn btn-sm btn-danger remove-option"><i class="fa fa-times"></i></button></td>' +
            '</tr>';
        $('#options-table tbody').append(html);
        optionIndex++;
    });

    $(document).on('click', '.remove-option', function() {
        $(this).closest('tr').remove();
    });
});
</script>