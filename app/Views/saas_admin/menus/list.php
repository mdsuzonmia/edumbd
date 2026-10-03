<?php
$status_list = get_status();
$filterRoleId = $filterRoleId ?? null;

if (!function_exists('render_menu_tree')) {
    function render_menu_tree($menus, &$globalIndex, $filterRoleId, $level = 0) {
        foreach ($menus as $item) {
            $globalIndex++;
            $indent = '';
            if ($level > 0) {
                $indent = str_repeat('&nbsp;&nbsp;&nbsp;&nbsp;', $level) . '└ ';
            }
            ?>
            <tr id="item_<?= (int) $item->id; ?>">
                <td><?= $globalIndex; ?></td>
                <td>
                    <p class="mb-0"><b><?= $indent; ?><?= esc($item->title); ?></b></p>
                    
                </td>
                <td><code><?= esc($item->route); ?></code></td>
                <td>
                    <input type="number" class="form-control form-control-sm menu-order-input" data-id="<?= (int) $item->id; ?>" value="<?= (int) $item->menu_order; ?>" min="0" style="width: 80px;">
                </td>
                <td class="text-center">
                    <?php if ((int) ($item->status ?? 0) === 1): ?>
                        <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> Active</span>
                    <?php elseif ((int) ($item->status ?? 0) === 0): ?>
                        <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="text-center">
                    <a href="<?= base_url('saas-admin/menus/edit/' . $item->id . (!empty($filterRoleId) ? '?role_id=' . (int) $filterRoleId : '')); ?>" class="btn btn-sm btn-primary mb-1" title="Edit">
                        <i class="fa fa-edit"></i> Edit
                    </a>
                    <a href="<?= base_url('saas-admin/menus/roles/' . $item->id); ?>" class="btn btn-sm btn-info mb-1" title="Roles">
                        <i class="fa fa-users"></i> Roles
                    </a>
                    <?php if ((int) ($item->status ?? 0) === 1): ?>
                        <?= form_open('saas-admin/menus/status', ['class' => 'd-inline menu-status-form']); ?>
                            <input type="hidden" name="menu_id" value="<?= (int) $item->id; ?>">
                            <input type="hidden" name="status" value="0">
                            <input type="hidden" name="role_id" value="<?= (int) $filterRoleId; ?>">
                            <button type="submit" class="btn btn-sm btn-warning mb-1" data-message="Deactivate this menu?">
                                <i class="fa fa-toggle-off"></i>
                            </button>
                        <?= form_close(); ?>
                        <button type="button" data-id="<?= (int) $item->id; ?>" class="trash btn btn-sm btn-danger mb-1" title="<?= lang('Common.text_trash'); ?>">
                            <i class="fa fa-trash"></i> Trash
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php
            if (!empty($item->children)) {
                render_menu_tree($item->children, $globalIndex, $filterRoleId, $level + 1);
            }
        }
    }
}
?>

<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="row mb-3">
    <div class="col-md-6 col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-bars"></i> Menu Management</h3>
    </div>
    <div class="col-md-6 col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/menus/create' . (!empty($filterRoleId) ? '?role_id=' . (int) $filterRoleId : '')); ?>" class="btn btn-success">
            <i class="fa fa-plus"></i> Add Menu
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= get_system_message(); ?>
        <div id="result"></div>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-6">
        <form method="get" action="<?= base_url('saas-admin/menus'); ?>" class="d-flex align-items-center gap-2">
            <label class="form-label col-sm-3 mb-0 fw-bold">Filter by Role:</label>
            <select name="role_id" class="form-control form-control-sm" onchange="this.form.submit()">
                <option value="">-- All Roles --</option>
                <?php if (!empty($allRoles)): ?>
                    <?php foreach ($allRoles as $role): ?>
                        <option value="<?= (int) $role->id; ?>" <?= $filterRoleId == $role->id ? 'selected' : ''; ?>>
                            <?= esc($role->name); ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
            <?php if (!empty($filterRoleId)): ?>
        <a href="<?= base_url('saas-admin/menus'); ?>" class="btn btn-sm btn-secondary">Clear</a>
        <input type="hidden" name="role_id" value="<?= (int) $filterRoleId; ?>">
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <table class="table student-table student-list">
                    <thead>
                        <tr>
                            <th width="45">#</th>
                            <th>Title</th>
                            <th>Route</th>
                            <th>Order</th>
                            <th class="text-center">Status</th>
                            <th width="350" class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($menus)): ?>
                            <?php
                            $globalIndex = 0;
                            render_menu_tree($menus, $globalIndex, $filterRoleId);
                            ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No menus found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var csrfToken = document.getElementById('csrf_token').value;

    document.querySelectorAll('.menu-status-form').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var button = this.querySelector('button[type="submit"]');
            var message = button ? button.dataset.message : 'Change status?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });

    var orderInputs = document.querySelectorAll('.menu-order-input');
    orderInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            var menuId = this.dataset.id;
            var newOrder = this.value;

            document.getElementById('result').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

            var formData = new FormData();
            formData.append('menu_id', menuId);
            formData.append('menu_order', newOrder);

            fetch('<?= base_url("saas-admin/menus/update-order") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': csrfToken }
            })
            .then(function (r) {
                var newToken = r.headers.get('X-CSRF-TOKEN');
                if (newToken) {
                    document.getElementById('csrf_token').value = newToken;
                    csrfToken = newToken;
                }
                return r.json();
            })
            .then(function (response) {
                document.getElementById('result').innerHTML = response.html || '';
                if (!response.status) {
                    alert(response.message || 'Failed to update order.');
                }
            });
        });
    });

    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.trash, .empty_trash, .restore');
        if (!btn) return;

        e.preventDefault();

        var action = btn.classList.contains('trash') ? 'trash'
                   : btn.classList.contains('empty_trash') ? 'empty-trash'
                   : 'restore';

        var id = btn.dataset.id;
        var currentRow = btn.closest('tr');
        var confirmMsg = btn.classList.contains('trash') ? '<?= lang("Common.trash_warning_message") ?: "Move this item to trash?" ?>'
                       : btn.classList.contains('empty_trash') ? '<?= lang("Common.empty_trash_warning_message") ?: "Permanently delete this item?" ?>'
                       : '<?= lang("Common.restore_warning_message") ?: "Restore this item?" ?>';

        if (!confirm(confirmMsg)) return;

        document.getElementById('result').innerHTML = '<div class="text-center"><div class="spinner-border" role="status"></div></div>';

        var formData = new FormData();
        formData.append('menu_id', id);
        formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');

        fetch('<?= base_url("saas-admin/menus/") ?>' + action, {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' },
            credentials: 'same-origin'
        })
        .then(function (r) {
            var newToken = r.headers.get('X-CSRF-TOKEN');
            if (newToken) {
                document.getElementById('csrf_token').value = newToken;
                csrfToken = newToken;
            }
            return r.json();
        })
        .then(function (response) {
            document.getElementById('result').innerHTML = response.html || '';
            if (response.status) {
                if (currentRow) currentRow.remove();
            } else {
                alert(response.message || 'Operation failed.');
            }
        })
        .catch(function (err) {
            console.error('Error:', err);
            document.getElementById('result').innerHTML = '';
            alert('An error occurred. Please try again.');
        });
    });
});
</script>