<?php
if (isset($is_edit) && $is_edit == true) {
    $header_title = lang('System.page_title_cf_group_edit') ?: 'Edit Group';
} else {
    $header_title = lang('System.page_title_cf_group_create') ?: 'Create Group';
}

$g = $group ?? null;

$title       = $g->title ?? '';
$entity_id   = $g->entity_id ?? '';
$description = $g->description ?? '';
$sort_order  = $g->sort_order ?? 0;
$status_value = $g->status ?? 1;

$status_list = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$form_action = !empty($is_edit) && !empty($g->id ?? 0)
    ? 'saas-admin/custom-fields/groups/update/' . $g->id
    : 'saas-admin/custom-fields/groups/store';
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="fa fa-object-group"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/custom-fields/groups'); ?>" class="btn btn-secondary">
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
    <div class="col-md-8 col-sm-12">
        <div class="card">
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_title') ?: 'Title'; ?> <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= esc($title); ?>" required maxlength="255">
                </div>

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

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_description') ?: 'Description'; ?></label>
                    <textarea name="description" class="form-control" rows="3"><?= esc($description); ?></textarea>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_sort_order') ?: 'Sort Order'; ?></label>
                    <input type="number" name="sort_order" class="form-control" value="<?= esc($sort_order); ?>">
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-12">
        <div class="card">
            <div class="card-header"><?= lang('Common.text_publish') ?: 'Publish'; ?></div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_status'); ?></label>
                    <?= $status_list; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= form_close(); ?>