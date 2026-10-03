<?php $status_list = get_status(); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-object-group"></i> <?= lang('System.page_title_cf_groups') ?: 'Field Groups'; ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('admin/custom-fields'); ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back_to_fields') ?: 'Back to Fields'; ?>
        </a>
        <a href="<?= base_url('admin/custom-fields/groups/create'); ?>" class="btn btn-success">
            <i class="fa fa-plus"></i> <?= lang('Common.btn_add_new') ?: 'Add New'; ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-12"><?= get_system_message(); ?></div>
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
                            <th><?= lang('Common.th_entity') ?: 'Entity'; ?></th>
                            <th class="text-center"><?= lang('Common.th_sort_order') ?: 'Sort'; ?></th>
                            <th class="text-center"><?= lang('Common.th_status'); ?></th>
                            <th width="160" class="text-center"><?= lang('Common.th_action'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($items)): ?>
                            <?php foreach ($items as $key => $item): ?>
                                <?php
                                $entityName = '';
                                foreach ($entities as $e) {
                                    if ((int) $e->id === (int) $item->entity_id) { $entityName = $e->title; break; }
                                }
                                ?>
                                <tr>
                                    <td><?= $key + 1; ?></td>
                                    <td><b><?= esc($item->title); ?></b></td>
                                    <td><?= esc($entityName); ?></td>
                                    <td class="text-center"><?= (int) $item->sort_order; ?></td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->status ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> <?= esc($status_list[1] ?? 'Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> <?= esc($status_list[0] ?? 'Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('admin/custom-fields/groups/edit/' . $item->id); ?>" class="btn btn-sm btn-info mb-1"><i class="fa fa-edit"></i> <?= lang('Common.text_edit'); ?></a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1 delete-group" data-id="<?= $item->id; ?>"><i class="fa fa-trash"></i> <?= lang('Common.text_delete'); ?></button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="text-center text-muted"><?= lang('Common.no_records_found') ?: 'No groups found.'; ?></td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var csrfToken = $('#csrf_token').val();
    $(document).on('click', '.delete-group', function(e) {
        var id = $(this).data('id');
        if (!confirm('<?= lang('Common.delete_warning_message') ?: 'Are you sure?'; ?>')) return;
        $.ajax({ type: 'post', dataType: 'json', url: '<?= base_url('admin/custom-fields/groups/delete'); ?>/' + id, headers: { 'X-CSRF-TOKEN': csrfToken }, success: function(r) { csrfToken = r['X-CSRF-TOKEN'] || csrfToken; if (r.status) location.reload(); } });
    });
});
</script>