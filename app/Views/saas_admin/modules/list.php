<?php
$status_list = get_status();
?>

<div class="row mb-3">
    <div class="col-md-6 col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-puzzle-piece"></i> <?= lang('System.page_title_modules'); ?></h3>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= get_system_message(); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header"><?= lang('System.modules_title'); ?></div>
            <div class="card-body">
                <?= form_open_multipart('saas-admin/modules/install', ['class' => 'form-horizontal form-label-left']); ?>
                    <div class="mb-3">
                        <input type="file" name="module_file" class="form-control" accept=".zip" required>
                    </div>
                    <button type="submit" class="btn btn-success">
                        <i class="fa fa-upload"></i> Install Module
                    </button>
                <?= form_close(); ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <table class="table student-table student-list">
                    <thead>
                        <tr>
                            <th width="45"><?= lang('Common.th_sn'); ?></th>
                            <th><?= lang('System.th_module_name'); ?></th>
                            <th><?= lang('System.th_module_version'); ?></th>
                            <th><?= lang('System.th_module_author'); ?></th>
                            <th class="text-center"><?= lang('Common.th_status'); ?></th>
                            <th width="160" class="text-center"><?= lang('Common.th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $key => $item): ?>
                                <tr>
                                    <td><?= $key + 1; ?></td>
                                    <td>
                                        <p class="mb-0"><b><?= esc($item->name); ?></b></p>
                                        <small class="text-muted"><?= esc($item->slug); ?></small>
                                        <?php if (!empty($item->description)): ?>
                                            <p class="mb-0"><small class="text-muted"><?= esc($item->description); ?></small></p>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($item->version ?? ''); ?></td>
                                    <td><?= esc($item->author ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->status ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> <?= esc($status_list[1] ?? 'Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> <?= esc($status_list[0] ?? 'Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($item->id)): ?>
                                            <?= form_open('saas-admin/modules/status', ['class' => 'd-inline module-status-form']); ?>
                                                <input type="hidden" name="module_id" value="<?= (int) $item->id; ?>">
                                                <?php if ((int) ($item->status ?? 0) === 1): ?>
                                                    <input type="hidden" name="status" value="0">
                                                    <button type="submit" class="btn btn-sm btn-warning" data-message="Deactivate this module?">
                                                        <i class="fa fa-toggle-off"></i> Deactivate
                                                    </button>
                                                <?php else: ?>
                                                    <input type="hidden" name="status" value="1">
                                                    <button type="submit" class="btn btn-sm btn-success" data-message="Activate this module?">
                                                        <i class="fa fa-toggle-on"></i> Activate
                                                    </button>
                                                <?php endif; ?>
                                            <?= form_close(); ?>
                                        <?php else: ?>
                                            <?= form_open('saas-admin/modules/install', ['class' => 'd-inline']); ?>
                                                <input type="hidden" name="slug" value="<?= esc($item->slug); ?>">
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fa fa-check"></i> Install
                                                </button>
                                            <?= form_close(); ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No modules found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.module-status-form').forEach(form => {
    form.addEventListener('submit', function (event) {
        const button = this.querySelector('button[type="submit"]');
        const message = button ? button.dataset.message : 'Change module status?';

        if (!confirm(message)) {
            event.preventDefault();
        }
    });
});
</script>
