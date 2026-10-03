<?php $status_list = get_status(); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-cubes"></i> <?= lang('System.page_title_custom_fields') ?: 'Custom Fields'; ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <form method="get" class="d-inline-block me-2" style="display: inline-block; vertical-align: middle;">
            <div class="input-group input-group-sm" style="display: inline-flex; width: auto;">
                <select name="school_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value=""><?= lang('Common.filter_school') ?: 'All Schools'; ?></option>
                    <?php foreach ($school_list as $sid => $sname): ?>
                        <option value="<?= $sid; ?>" <?= ((int) ($selected_school ?? 0) === (int) $sid) ? 'selected' : ''; ?>>
                            <?= esc($sname); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <noscript><button type="submit" class="btn btn-sm btn-primary"><?= lang('Common.btn_search') ?: 'Go'; ?></button></noscript>
            </div>
        </form>
        <a href="<?= base_url('school-owner/custom-fields/create'); ?>" class="btn btn-success">
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
                            <th><?= lang('Common.th_label') ?: 'Label'; ?></th>
                            <th><?= lang('Common.th_field_key') ?: 'Field Key'; ?></th>
                            <th><?= lang('Common.th_entity') ?: 'Entity'; ?></th>
                            <th><?= lang('Common.th_group') ?: 'Group'; ?></th>
                            <th><?= lang('Common.th_type') ?: 'Type'; ?></th>
                            <th class="text-center"><?= lang('Common.th_required') ?: 'Required'; ?></th>
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
                                    if ((int) $e->id === (int) $item->entity_id) {
                                        $entityName = $e->title;
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= $key + 1; ?></td>
                                    <td><b><?= esc($item->label); ?></b></td>
                                    <td><code><?= esc($item->field_key); ?></code></td>
                                    <td><?= esc($entityName); ?></td>
                                    <td><?= esc($item->group_name ?? ''); ?></td>
                                    <td><?= esc($item->field_type ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->is_required ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><?= lang('Common.yes') ?: 'Yes'; ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary"><?= lang('Common.no') ?: 'No'; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->status ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> <?= esc($status_list[1] ?? 'Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> <?= esc($status_list[0] ?? 'Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= base_url('school-owner/custom-fields/edit/' . $item->token); ?>" class="btn btn-sm btn-info mb-1">
                                            <i class="fa fa-edit"></i> <?= lang('Common.text_edit'); ?>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger mb-1 delete-field" data-token="<?= $item->token; ?>">
                                            <i class="fa fa-trash"></i> <?= lang('Common.text_delete'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center text-muted"><?= lang('Common.no_records_found') ?: 'No custom fields found.'; ?></td>
                            </tr>
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
    
    $(document).on('click', '.delete-field', function(e) {
        var token = $(this).data('token');
        if (!confirm('<?= lang('Common.delete_warning_message') ?: 'Are you sure you want to delete this?'; ?>')) return;
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('school-owner/custom-fields/delete'); ?>/' + token,
            headers: { 'X-CSRF-TOKEN': csrfToken },
            success: function(response, status, xhr) {
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                $('#csrf_token').val(csrfToken);
                if (response.status) {
                    location.reload();
                }
            }
        });
    });
});
</script>