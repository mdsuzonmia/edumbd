<?php $status_list = get_status(); ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-database"></i> <?= lang('System.page_title_cf_entities') ?: 'Field Entities'; ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/custom-fields/entities/create'); ?>" class="btn btn-success">
            <i class="fa fa-plus"></i> <?= lang('Common.btn_add_new') ?: 'Add New'; ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= get_system_message(); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <table class="table student-table student-list">
                    <thead>
                        <tr>
                            <th width="45"><?= lang('Common.th_sn'); ?></th>
                            <th><?= lang('Common.th_title') ?: 'Title'; ?></th>
                            <th><?= lang('Common.th_slug') ?: 'Slug'; ?></th>
                            <th class="text-center"><?= lang('Common.th_status'); ?></th>
                            <th width="160" class="text-center"><?= lang('Common.th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $key => $item): ?>
                                <tr>
                                    <td><?= $key + 1; ?></td>
                                    <td><b><?= esc($item->title); ?></b></td>
                                    <td><code><?= esc($item->slug); ?></code></td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->status ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> <?= esc($status_list[1] ?? 'Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> <?= esc($status_list[0] ?? 'Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('saas-admin/custom-fields/entities/edit/' . $item->id); ?>" class="btn btn-sm btn-info mb-1">
                                            <i class="fa fa-edit"></i> <?= lang('Common.text_edit'); ?>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-warning mb-1 trash-entity" data-id="<?= $item->id; ?>">
                                            <i class="fa fa-trash"></i> <?= lang('Common.btn_trash') ?: 'Trash'; ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted"><?= lang('Common.no_records_found') ?: 'No entities found.'; ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($trashed)): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <i class="fa fa-trash"></i> Trashed Entities
            </div>
            <div class="card-body">
                <table class="table student-table student-list">
                    <thead>
                        <tr>
                            <th width="45"><?= lang('Common.th_sn'); ?></th>
                            <th><?= lang('Common.th_title') ?: 'Title'; ?></th>
                            <th><?= lang('Common.th_slug') ?: 'Slug'; ?></th>
                            <th width="220" class="text-center"><?= lang('Common.th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($trashed as $key => $item): ?>
                            <tr>
                                <td><?= $key + 1; ?></td>
                                <td><b><?= esc($item->title); ?></b></td>
                                <td><code><?= esc($item->slug); ?></code></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-success mb-1 restore-entity" data-id="<?= $item->id; ?>">
                                        <i class="fa fa-undo"></i> <?= lang('Common.btn_restore') ?: 'Restore'; ?>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger mb-1 empty-trash-entity" data-id="<?= $item->id; ?>">
                                        <i class="fa fa-times"></i> <?= lang('Common.btn_empty_trash') ?: 'Empty Trash'; ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
    $(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    
    $(document).on('click', '.trash-entity', function(e) {
        var id = $(this).data('id');
        if (!confirm('<?= lang('Common.trash_warning_message') ?: 'Are you sure you want to trash ?'; ?>')) return;
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('saas-admin/custom-fields/entities/trash'); ?>',
            data: { entity_id: id },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                csrfToken = response['X-CSRF-TOKEN'] || csrfToken;
                if (response.status) {
                    location.reload();
                }
            }
        });
    });
    
    $(document).on('click', '.restore-entity', function(e) {
        var id = $(this).data('id');
        if (!confirm('<?= lang('Common.restore_warning_message') ?: 'Are you sure you want to restore ?'; ?>')) return;
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('saas-admin/custom-fields/entities/restore'); ?>',
            data: { entity_id: id },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                csrfToken = response['X-CSRF-TOKEN'] || csrfToken;
                if (response.status) {
                    location.reload();
                }
            }
        });
    });
    
    $(document).on('click', '.empty-trash-entity', function(e) {
        var id = $(this).data('id');
        if (!confirm('<?= lang('Common.empty_trash_warning_message') ?: 'Are you sure you want to empty trash ?'; ?>')) return;
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('saas-admin/custom-fields/entities/empty-trash'); ?>',
            data: { entity_id: id },
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response) {
                csrfToken = response['X-CSRF-TOKEN'] || csrfToken;
                if (response.status) {
                    location.reload();
                }
            }
        });
    });
});
</script>