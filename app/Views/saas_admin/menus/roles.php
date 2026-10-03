<?php
$header_title = 'Assign Roles - ' . esc($menu->title);
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="fa fa-users"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/menus'); ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back') ?: 'Back'; ?>
        </a>
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
            <div class="card-header">
                Menu Info
            </div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr>
                        <th width="150">Title</th>
                        <td><?= esc($menu->title); ?></td>
                    </tr>
                    <tr>
                        <th>Slug</th>
                        <td><code><?= esc($menu->slug); ?></code></td>
                    </tr>
                    <tr>
                        <th>Route</th>
                        <td><code><?= esc($menu->route); ?></code></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <?php if ((int) $menu->status === 1): ?>
                                <span class="badge text-bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge text-bg-warning">Inactive</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                Assign Roles
            </div>
            <div class="card-body">
                <?= form_open('saas-admin/menus/sync-roles/' . $menu->id, ['class' => 'form-horizontal']); ?>
                    <div class="mb-3">
                        <?php if (!empty($allRoles)): ?>
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="60" class="text-center">
                                            <input type="checkbox" id="selectAll">
                                        </th>
                                        <th>Role Name</th>
                                        <th>Description</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($allRoles as $role): ?>
                                        <tr>
                                            <td class="text-center">
                                                <input type="checkbox"
                                                       name="role_ids[]"
                                                       value="<?= (int) $role->id; ?>"
                                                       class="role-checkbox"
                                                       <?= in_array((int) $role->id, $assignedRoleIds) ? 'checked' : ''; ?>>
                                            </td>
                                            <td><strong><?= esc($role->name); ?></strong></td>
                                            <td><?= esc($role->description ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted">No roles available.</p>
                        <?php endif; ?>
                    </div>

                    <div class="text-end">
                        <a href="<?= base_url('saas-admin/menus'); ?>" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back') ?: 'Back'; ?>
                        </a>
                        <button type="submit" class="btn btn-success">
                            <i class="fa fa-save"></i> <?= lang('Common.btn_save'); ?>
                        </button>
                    </div>
                <?= form_close(); ?>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checked = this.checked;
    document.querySelectorAll('.role-checkbox').forEach(cb => {
        cb.checked = checked;
    });
});
</script>