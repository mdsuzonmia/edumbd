<!-- Grade Systems List -->
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3><?= lang('GradeSystem.page_title_list') ?></h3>
            </div>
            <div class="title_right">
                <a href="<?= base_url('examination/grade-systems/create') ?>" class="btn btn-success pull-right">
                    <i class="fa fa-plus"></i> <?= lang('GradeSystem.btn_new') ?>
                </a>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <!-- Filter Form -->
                        <form method="get" action="" class="form-inline mb-3">
                            <div class="row">
                                <div class="col-md-3 col-sm-6">
                                    <div class="form-group">
                                        <input type="text" name="text" class="form-control" placeholder="<?= lang('GradeSystem.search_placeholder') ?>" value="<?= esc($text ?? '') ?>">
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <select name="status" class="form-control">
                                            <option value=""><?= lang('Common.all_status') ?></option>
                                            <option value="1" <?= ($status ?? '') === '1' ? 'selected' : '' ?>><?= lang('Common.active') ?></option>
                                            <option value="0" <?= ($status ?? '') === '0' ? 'selected' : '' ?>><?= lang('Common.inactive') ?></option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <div class="form-group">
                                        <select name="show" class="form-control">
                                            <option value=""><?= lang('Common.show') ?></option>
                                            <option value="10" <?= ($show ?? '') == '10' ? 'selected' : '' ?>>10</option>
                                            <option value="25" <?= ($show ?? '') == '25' ? 'selected' : '' ?>>25</option>
                                            <option value="50" <?= ($show ?? '') == '50' ? 'selected' : '' ?>>50</option>
                                            <option value="100" <?= ($show ?? '') == '100' ? 'selected' : '' ?>>100</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-2 col-sm-6">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fa fa-search"></i> <?= lang('Common.btn_search') ?>
                                    </button>
                                    <a href="<?= base_url('examination/grade-systems') ?>" class="btn btn-secondary">
                                        <i class="fa fa-refresh"></i>
                                    </a>
                                </div>
                            </div>
                        </form>

                        <!-- System Message -->
                        <?= get_system_message(); ?>

                        <!-- Data Table -->
                        <?php if (!empty($items)): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th width="50">#</th>
                                            <th><?= lang('GradeSystem.title') ?></th>
                                            <th><?= lang('GradeSystem.school') ?></th>
                                            <th><?= lang('GradeSystem.total_mark') ?></th>
                                            <th class="text-center"><?= lang('Common.status') ?></th>
                                            <th width="200" class="text-center"><?= lang('Common.actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $item): ?>
                                            <tr>
                                                <td><?= $item->id ?></td>
                                                <td>
                                                    <strong><?= esc($item->title) ?></strong>
                                                    <?php if (!empty($item->description)): ?>
                                                        <br><small class="text-muted"><?= esc($item->description) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= esc($item->school_name ?? 'N/A') ?></td>
                                                <td><?= number_format($item->total_mark, 2) ?></td>
                                                <td class="text-center">
                                                    <?php if ($item->status == 1): ?>
                                                        <span class="badge badge-success"><?= lang('Common.active') ?></span>
                                                    <?php else: ?>
                                                        <span class="badge badge-danger"><?= lang('Common.inactive') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <a href="<?= base_url('examination/grade-systems/edit/' . $item->token) ?>" class="btn btn-sm btn-primary" title="<?= lang('Common.btn_edit') ?>">
                                                        <i class="fa fa-edit"></i>
                                                    </a>
                                                    <a href="<?= base_url('examination/grade-systems/rules/' . $item->token) ?>" class="btn btn-sm btn-info" title="<?= lang('GradeSystem.grade_rules') ?>">
                                                        <i class="fa fa-list"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-danger btn-trash" data-token="<?= esc($item->token) ?>" title="<?= lang('Common.btn_trash') ?>">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if (isset($pager) && $pager): ?>
                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="dataTables_paginate paging_simple_numbers">
                                            <?= $pager->links() ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <?= lang('Common.no_data_found') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Trash button handler
    document.querySelectorAll('.btn-trash').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const token = this.dataset.token;
            if (!token) return;
            
            if (!confirm('<?= lang("Common.trash_warning_message") ?>')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('token', token);
            formData.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
            
            fetch('<?= base_url("examination/grade-systems/trash") ?>', {
                method: 'POST',
                body: formData,
                headers: { 'X-CSRF-TOKEN': '<?= csrf_hash() ?>' }
            })
            .then(response => response.json())
            .then(data => {
                if (data.status) {
                    location.reload();
                } else {
                    alert(data.html || '<?= lang("Common.data_error_trashed") ?>');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('<?= lang("Common.data_error_trashed") ?>');
            });
        });
    });
});
</script>