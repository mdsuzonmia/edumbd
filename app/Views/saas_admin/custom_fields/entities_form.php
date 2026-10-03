<?php
if (isset($is_edit) && $is_edit == true) {
    $header_title = lang('System.page_title_cf_entity_edit') ?: 'Edit Entity';
} else {
    $header_title = lang('System.page_title_cf_entity_create') ?: 'Create Entity';
}

$title = isset($entity) ? $entity->title : ($post_data['title'] ?? '');
$slug  = isset($entity) ? $entity->slug  : ($post_data['slug'] ?? '');
$status_value = isset($entity) ? $entity->status : ($post_data['status'] ?? 1);
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

$form_action = !empty($is_edit) && !empty($entity->id ?? 0)
    ? 'saas-admin/custom-fields/entities/update/' . $entity->id
    : 'saas-admin/custom-fields/entities/store';
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="fa fa-database"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/custom-fields/entities'); ?>" class="btn btn-secondary">
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
                    <input type="text" name="title" class="form-control" value="<?= esc($title); ?>" required maxlength="100">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label"><?= lang('Common.th_slug') ?: 'Slug'; ?> <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control" value="<?= esc($slug); ?>" required maxlength="100">
                    <small class="text-muted"><?= lang('Common.help_slug') ?: 'Unique identifier. Use lowercase letters, numbers and hyphens.'; ?></small>
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