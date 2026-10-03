<?php
if (isset($is_edit) && $is_edit == true) {
    $header_title = 'Edit Menu';
} else {
    $header_title = 'Create Menu';
}

$title        = isset($menu) ? $menu->title : '';
$slug         = isset($menu) ? $menu->slug : '';
$route        = isset($menu) ? $menu->route : '';
$icon         = isset($menu) ? $menu->icon : '';
$menuOrder    = isset($menu) ? $menu->menu_order : 0;
$statusValue  = isset($menu) ? $menu->status : 1;
$moduleId     = isset($menu) ? $menu->module_id : null;
$parentId     = isset($menu) ? $menu->parent_id : null;
$assignedRoleIds = isset($assignedRoleIds) ? $assignedRoleIds : [];

$status_list  = get_status_list('Status', 'status', 'status', $statusValue, true);

$form_action = !empty($is_edit) && !empty($menu->id ?? 0)
    ? 'saas-admin/menus/update/' . $menu->id
    : 'saas-admin/menus/store';

// Ensure $menu is defined to avoid undefined variable errors
if (!isset($menu)) {
    $menu = null;
}

$roleId = $filterRoleId ?? null;
?>

<?= form_open($form_action, [
    'class'   => 'form-horizontal form-label-left',
    'method'  => 'post',
    'data-parsley-validate' => ''
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary">
            <i class="fa fa-bars"></i> <?= $header_title; ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/menus'); ?>" class="btn btn-secondary">
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
                    <label class="form-label">Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="<?= esc($title); ?>" required maxlength="150">
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Slug <span class="text-danger">*</span></label>
                    <input type="text" name="slug" class="form-control" value="<?= esc($slug); ?>" required maxlength="150">
                    <small class="text-muted">Unique identifier. Use lowercase letters, numbers and hyphens.</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Route <span class="text-danger">*</span></label>
                    <input type="text" name="route" class="form-control" value="<?= esc($route); ?>" required maxlength="255">
                    <small class="text-muted">e.g. admin/dashboard or school/students</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Icon</label>
                    <input type="text" name="icon" class="form-control" value="<?= esc($icon); ?>" maxlength="100" placeholder="e.g. fa fa-dashboard">
                    <small class="text-muted">Font Awesome icon class, e.g. fa fa-dashboard</small>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Menu Order</label>
                    <input type="number" name="menu_order" class="form-control" value="<?= (int) $menuOrder; ?>" min="0">
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 col-sm-12">
        <div class="card">
            <div class="card-header">Settings</div>
            <div class="card-body">
                <div class="form-group mb-3">
                    <label class="form-label">Status <span class="text-danger">*</span></label>
                    <?= $status_list; ?>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Module</label>
                    <select name="module_id" class="form-control">
                        <option value="">-- None --</option>
                        <?php if (!empty($modules)): ?>
                            <?php foreach ($modules as $mod): ?>
                                <option value="<?= (int) $mod->id; ?>" <?= $moduleId == $mod->id ? 'selected' : ''; ?>>
                                    <?= esc($mod->name); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <div class="form-group mb-3">
                    <label class="form-label">Parent Menu</label>
                    <select name="parent_id" class="form-control">
                        <option value="">-- None (Top Level) --</option>
                        <?php if (!empty($parentMenus)): ?>
                            <?php
                            // Build tree for display
                            $menuTree = [];
                            $topLevel = array_filter($parentMenus, fn($m) => empty($m->parent_id) || (int)$m->parent_id === 0);
                            
                            foreach ($topLevel as $top) {
                                $menuTree[] = ['level' => 0, 'menu' => $top];
                                $children = array_filter($parentMenus, fn($m) => (int)$m->parent_id === (int)$top->id);
                                foreach ($children as $child) {
                                    $menuTree[] = ['level' => 1, 'menu' => $child];
                                    $grandchildren = array_filter($parentMenus, fn($m) => (int)$m->parent_id === (int)$child->id);
                                    foreach ($grandchildren as $grandchild) {
                                        $menuTree[] = ['level' => 2, 'menu' => $grandchild];
                                    }
                                }
                            }
                            
                            foreach ($menuTree as $item):
                                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $item['level']);
                                $prefix = $item['level'] > 0 ? '└ ' : '';
                            ?>
                                <option value="<?= (int) $item['menu']->id; ?>" <?= $parentId == $item['menu']->id ? 'selected' : ''; ?>>
                                    <?= $indent . $prefix . esc($item['menu']->title); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <small class="text-muted">Select a parent menu to create sub-menus (up to 3 levels supported).</small>
                </div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">Assign Roles</div>
            <div class="card-body">
                <?php if (!empty($allRoles)): ?>
                    <div class="mb-2">
                        <input type="checkbox" id="selectAllRoles"> <label for="selectAllRoles"><strong>Select All</strong></label>
                    </div>
                    <?php foreach ($allRoles as $role): ?>
                        <div class="form-check">
                            <input class="form-check-input role-cb" type="checkbox"
                                   name="role_ids[]"
                                   value="<?= (int) $role->id; ?>"
                                   id="role_<?= (int) $role->id; ?>"
                                   <?= in_array((int) $role->id, $assignedRoleIds) ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="role_<?= (int) $role->id; ?>">
                                <?= esc($role->name); ?>
                                <?php if (!empty($role->description)): ?>
                                    <small class="text-muted">— <?= esc($role->description); ?></small>
                                <?php endif; ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted mb-0">No roles available.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="role_id" value="<?= (int) $roleId; ?>">

<script>
document.getElementById('selectAllRoles')?.addEventListener('change', function() {
    const checked = this.checked;
    document.querySelectorAll('.role-cb').forEach(cb => { cb.checked = checked; });
});
</script>

<?= form_close(); ?>
