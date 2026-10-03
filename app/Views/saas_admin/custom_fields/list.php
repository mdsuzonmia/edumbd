<?php $status_list = get_status(); ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-cubes"></i> <?= lang('System.page_title_custom_fields') ?: 'Custom Fields'; ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <form method="get" class="d-inline-block me-2" style="display: inline-block; vertical-align: middle;">
            <div class="input-group input-group-sm" style="display: inline-flex; width: auto;">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by label/key..." value="<?= esc($_GET['search'] ?? ''); ?>" style="width:160px;">
                <input type="text" name="school_name" class="form-control form-control-sm" placeholder="School name..." value="<?= esc($_GET['school_name'] ?? ''); ?>" style="width:160px;" list="school-list">
                <datalist id="school-list">
                    <?php foreach ($schools as $s): ?>
                        <option value="<?= esc($s->name); ?>" data-id="<?= $s->id; ?>">
                    <?php endforeach; ?>
                </datalist>
                <input type="hidden" name="school_id" id="school_id" value="<?= (int) ($_GET['school_id'] ?? 0); ?>">
                <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-search"></i></button>
            </div>
        </form>
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
                            <th><?= lang('Common.th_school') ?: 'School'; ?></th>
                            <th><?= lang('Common.th_entity') ?: 'Entity'; ?></th>
                            <th><?= lang('Common.th_group') ?: 'Group'; ?></th>
                            <th><?= lang('Common.th_type') ?: 'Type'; ?></th>
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
                                $schoolName = '';
                                foreach ($schools as $s) {
                                    if ((int) $s->id === (int) $item->school_id) {
                                        $schoolName = $s->name;
                                        break;
                                    }
                                }
                                ?>
                                <tr>
                                    <td><?= $key + 1; ?></td>
                                    <td><b><?= esc($item->label); ?></b></td>
                                    <td><code><?= esc($item->field_key); ?></code></td>
                                    <td><?= esc($schoolName); ?></td>
                                    <td><?= esc($entityName); ?></td>
                                    <td><?= esc($item->group_name ?? ''); ?></td>
                                    <td><?= esc($item->field_type ?? ''); ?></td>
                                    <td class="text-center">
                                        <?php if ((int) ($item->status ?? 0) === 1): ?>
                                            <span class="badge text-bg-success"><i class="fa fa-check-circle"></i> <?= esc($status_list[1] ?? 'Active'); ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> <?= esc($status_list[0] ?? 'Inactive'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-warning mb-1 trash-field" data-id="<?= $item->id; ?>">
                                            <i class="fa fa-trash"></i> <?= lang('Common.btn_trash') ?: 'Trash'; ?>
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
    
    // School datalist: when user selects a school from the autocomplete list, set the school_id
    var schoolMap = {};
    <?php foreach ($schools as $s): ?>
        schoolMap['<?= esc($s->name); ?>'] = <?= $s->id; ?>;
    <?php endforeach; ?>

    $('input[name="school_name"]').on('input', function() {
        var name = $(this).val();
        if (schoolMap[name]) {
            $('input[name="school_id"]').val(schoolMap[name]);
        } else {
            $('input[name="school_id"]').val('');
        }
    });
    
    $(document).on('click', '.trash-field', function(e) {
        var id = $(this).data('id');
        if (!confirm('<?= lang('Common.trash_warning_message') ?: 'Are you sure you want to trash?'; ?>')) return;
        
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: '<?= base_url('saas-admin/custom-fields/trash'); ?>',
            data: { field_id: id },
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