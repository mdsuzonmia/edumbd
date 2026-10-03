<?php 
$school_list = $school_list ?? [];
$templates = $templates ?? [];
$selected_school = $selected_school ?? 0;
$is_saas_admin = $is_saas_admin ?? false;
?>

<div class="row">
    <div class="col-md-12">
<div class="card">
    <?= get_system_message(); ?>
    <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0"><?= lang('ResultTemplate.heading_list') ?></h3>
                <a href="<?= base_url('examination/templates/builder?school_id=' . $selected_school) ?>" class="btn btn-primary">
                    <i class="fa fa-plus"></i> <?= lang('Common.btn_add_new') ?>
                </a>
            </div>
            <div class="card-body">
                <!-- Filter by School -->
                <form method="get" class="mb-3">
                    <div class="row g-2 align-items-end">
                        <div class="col-auto">
                            <label for="school_id" class="form-label"><?= lang('Common.filter_school') ?></label>
                            <select name="school_id" id="school_id" class="form-select filter-select" onchange="this.form.submit()">
                                <option value="">All Schools</option>
                                <?php if (!empty($school_list)): ?>
                                    <?php foreach ($school_list as $sid => $sname): ?>
                                        <option value="<?= $sid ?>" <?= $selected_school == $sid ? 'selected' : '' ?>><?= esc($sname) ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        <div class="col-auto">
                            <a href="<?= base_url('examination/templates') ?>" class="btn btn-outline-secondary">
                                <i class="fa fa-refresh"></i> <?= lang('Common.btn_reset') ?>
                            </a>
                        </div>
                    </div>
                </form>

                <?php if (!empty($templates)): ?>
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th><?= lang('ResultTemplate.th_template_name') ?></th>
                                <th><?= lang('Common.th_school') ?></th>
                                <th><?= lang('ResultTemplate.th_default') ?></th>
                                <th><?= lang('ResultTemplate.th_multiple_exams') ?></th>
                                <th><?= lang('ResultTemplate.th_aggregated') ?></th>
                                <th><?= lang('Common.th_created_at') ?></th>
                                <th><?= lang('Common.th_action') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $schoolModel = new \App\Models\SchoolModel(); ?>
                            <?php foreach ($templates as $key => $tpl): ?>
                                <?php 
                                $school_name = lang('ResultTemplate.text_all_schools');
                                if (!empty($tpl->school_id)) {
                                    $school = $schoolModel->find($tpl->school_id);
                                    $school_name = $school ? $school->name : 'N/A';
                                }
                                ?>
                                <tr>
                                    <td><?= ++$key ?></td>
                                    <td><?= esc($tpl->template_name) ?></td>
                                    <td><?= esc($school_name) ?></td>
                                    <td>
                                        <?php if ($tpl->is_default): ?>
                                            <span class="badge text-bg-success"><?= lang('Common.sys_yes') ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary"><?= lang('Common.sys_no') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tpl->support_multiple_exams): ?>
                                            <span class="badge text-bg-info"><?= lang('Common.sys_yes') ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary"><?= lang('Common.sys_no') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($tpl->support_aggregated_result): ?>
                                            <span class="badge text-bg-info"><?= lang('Common.sys_yes') ?></span>
                                        <?php else: ?>
                                            <span class="badge text-bg-secondary"><?= lang('Common.sys_no') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $tpl->created_at ? date('M d, Y', strtotime($tpl->created_at)) : '-' ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= base_url('examination/templates/builder?template_id=' . $tpl->id) ?>" class="btn btn-outline-primary" title="<?= lang('Common.btn_edit') ?>">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" onclick="deleteTemplate(<?= $tpl->id ?>)" title="<?= lang('Common.btn_delete') ?>">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    <?= lang('ResultTemplate.no_templates') ?>
                    <a href="<?= base_url('examination/templates/builder') ?>" class="alert-link"><?= lang('ResultTemplate.create_first_template') ?></a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function deleteTemplate(id) {
    if (!confirm('<?= lang('Common.delete_warning_message') ?>')) return;
    
    $.ajax({
        type: "post",
        dataType: "json",
        url: '<?= base_url('examination/templates/delete/') ?>' + id,
        data: {
            '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
        },
        success: function(resp) {
            if (resp.status) {
                location.reload();
            } else {
                alert(resp.message || 'Failed to delete template.');
            }
        },
        error: function() {
            alert('An error occurred while deleting the template.');
        }
    });
}
</script>
