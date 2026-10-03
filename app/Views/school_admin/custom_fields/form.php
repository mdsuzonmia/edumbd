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
    ? 'admin/custom-fields/update/' . $f->id
    : 'admin/custom-fields/store';
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary"><i class="fa fa-cubes"></i> <?= $header_title; ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('admin/custom-fields'); ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back') ?: 'Back'; ?>
        </a>
        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> <?= lang('Common.btn_save'); ?>
        </button>
    </div>
</div>

<div class="row">
    <div class="col-12"><?= get_system_message(); ?></div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><?= lang('Common.text_field_details') ?: 'Field Details'; ?></div>
            <div class="card-body">
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
                                    <option value="<?= $e->id; ?>" <?= ((int) $entity_id === (int) $e->id) ? 'selected' : ''; ?>><?= esc($e->title); ?></option>
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
                                    <option value="<?= $g->id; ?>" <?= ((int) $group_id === (int) $g->id) ? 'selected' : ''; ?>><?= esc($g->title); ?></option>
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
                                    <option value="<?= $t->id; ?>" <?= ((int) $field_type_id === (int) $t->id) ? 'selected' : ''; ?>><?= esc($t->title); ?></option>
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
                <?php
                $checks = [
                    'is_required' => 'Required',
                    'is_unique' => 'Unique Value',
                    'is_searchable' => 'Searchable',
                    'show_on_registration' => 'Show on Registration',
                    'show_on_admission' => 'Show on Admission',
                    'allow_bulk_import' => 'Allow Bulk Import',
                    'allow_bulk_export' => 'Allow Bulk Export',
                    'show_on_profile' => 'Show on Profile',
                    'show_on_list' => 'Show on List',
                    'show_on_idcard' => 'Show on ID Card',
                    'show_on_resultcard' => 'Show on Result Card',
                ];
                $vals = compact('is_required','is_unique','is_searchable','show_on_reg','show_on_adm','allow_import','allow_export','show_on_profile','show_on_list','show_on_idcard','show_on_result');
                $i = 0;
                foreach ($checks as $name => $label):
                    $valKey = str_replace(['show_on_registration','show_on_admission','allow_bulk_import','allow_bulk_export','show_on_resultcard'], ['show_on_reg','show_on_adm','allow_import','allow_export','show_on_result'], $name);
                ?>
                <div class="form-check mb-2">
                    <input type="checkbox" name="<?= $name; ?>" value="1" class="form-check-input" id="chk_<?= $i; ?>" <?= ($vals[$valKey] ?? 0) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="chk_<?= $i++; ?>"><?= lang('Common.' . $name) ?: $label; ?></label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php if ($is_edit && !empty($options)): ?>
        <div class="card">
            <div class="card-header"><?= lang('Common.text_field_options') ?: 'Field Options'; ?></div>
            <div class="card-body">
                <table class="table table-sm" id="options-table">
                    <thead><tr><th><?= lang('Common.th_label') ?: 'Label'; ?></th><th><?= lang('Common.th_value') ?: 'Value'; ?></th><th width="40"></th></tr></thead>
                    <tbody>
                        <?php foreach ($options as $opt): ?>
                        <tr>
                            <td><input type="text" name="options[<?= $opt->id; ?>][label]" class="form-control form-control-sm" value="<?= esc($opt->option_label); ?>"></td>
                            <td><input type="text" name="options[<?= $opt->id; ?>][value]" class="form-control form-control-sm" value="<?= esc($opt->option_value); ?>"></td>
                            <td><button type="button" class="btn btn-sm btn-danger remove-option"><i class="fa fa-times"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <button type="button" class="btn btn-sm btn-success" id="add-option"><i class="fa fa-plus"></i> <?= lang('Common.add_option') ?: 'Add Option'; ?></button>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?= form_close(); ?>

<script>
$(document).ready(function() {
    var idx = <?= !empty($options) ? count($options) + 1 : 0; ?>;
    $('#add-option').on('click', function() {
        $('#options-table tbody').append('<tr><td><input type="text" name="options['+idx+'][label]" class="form-control form-control-sm" placeholder="Label"></td><td><input type="text" name="options['+idx+'][value]" class="form-control form-control-sm" placeholder="Value"></td><td><button type="button" class="btn btn-sm btn-danger remove-option"><i class="fa fa-times"></i></button></td></tr>');
        idx++;
    });
    $(document).on('click', '.remove-option', function() { $(this).closest('tr').remove(); });
});
</script>