<?php 
use App\Models\SchoolModel;

$school_model = new SchoolModel();
?>

<div class="right_col" role="main">
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <h3><i class="fa fa-history"></i> <?= lang('Bulkstudent.page_title_history'); ?></h3>
        </div>
    </div>

    <div class="clearfix"></div>

    <?= get_system_message(); ?>

    <!-- Filter Form -->
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-filter"></i> <?= lang('Common.filter_by_school'); ?></h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <?= form_open(site_url('school-owner/students/bulk/import/history'), [
                        'class'   => 'form-horizontal form-label-left', 
                        'id'      => 'filter_form', 
                        'method'  => 'get', 
                        'data-parsley-validate' => '' 
                    ]); ?>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="col-form-label col-md-4" for="school_id"><?= lang('Mark.field_school') ?></label>
                                <div class="col-md-8">
                                    <select name="school_id" id="school_id" class="form-control">
                                        <option value=""><?= lang('Mark.field_all_school') ?></option>
                                        <?php foreach ($school_list as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= ($selected_school == $id) ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-sm btn-primary mt-4"><i class="fa fa-search"></i> <?= lang('Common.btn_filter') ?></button>
                        </div>
                    </div>

                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Import History Table -->
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-list"></i> <?= lang('Bulkstudent.page_title_history'); ?></h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <?php if (!empty($items) && count($items) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>#</th>
                                        <th><?= lang('Mark.field_school') ?></th>
                                        <th><?= lang('Bulkstudent.file_type') ?></th>
                                        <th><?= lang('Bulkstudent.file_name') ?></th>
                                        <th><?= lang('Bulkstudent.total_records') ?></th>
                                        <th><?= lang('Bulkstudent.success_records') ?></th>
                                        <th><?= lang('Bulkstudent.failed_records') ?></th>
                                        <th><?= lang('Bulkstudent.status') ?></th>
                                        <th><?= lang('Bulkstudent.import_date') ?></th>
                                        <th><?= lang('Common.btn_action') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($items as $index => $item): ?>
                                        <tr>
                                            <td><?= $item->id ?></td>
                                            <td><?= esc($item->school_name) ?></td>
                                            <td><span class="badge badge-info"><?= strtoupper($item->file_type) ?></span></td>
                                            <td><?= esc($item->file_name ?: 'N/A') ?></td>
                                            <td><?= $item->total_records ?></td>
                                            <td><span class="badge badge-success"><?= $item->success_records ?></span></td>
                                            <td><span class="badge badge-danger"><?= $item->failed_records ?></span></td>
                                            <td>
                                                <?php 
                                                    $statusClass = [
                                                        'completed'           => 'success',
                                                        'completed_with_errors' => 'warning',
                                                        'processing'          => 'info',
                                                        'failed'              => 'danger',
                                                    ];
                                                    $class = isset($statusClass[$item->status]) ? $statusClass[$item->status] : 'secondary';
                                                ?>
                                                <span class="badge badge-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $item->status)) ?></span>
                                            </td>
                                            <td><?= date('Y-m-d H:i', strtotime($item->created_at)) ?></td>
                                            <td>
                                                <a href="javascript:void(0)" class="btn btn-sm btn-info" onclick="viewImportDetails(<?= $item->id ?>)">
                                                    <i class="fa fa-eye"></i> <?= lang('Common.btn_view_details') ?>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($pager && $pager->getPageCount() > 1): ?>
                            <div class="row">
                                <div class="col-md-12 text-center">
                                    <?= $pager->links('default', $pagerTemplate) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle"></i> <?= lang('Bulkstudent.no_import_history') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Details Modal -->
<div class="modal fade" id="importDetailsModal" tabindex="-1" role="dialog" aria-labelledby="importDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="importDetailsModalLabel"><?= lang('Bulkstudent.import_details') ?></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="import_details_body">
                <div class="text-center">
                    <i class="fa fa-spinner fa-spin fa-3x"></i>
                    <p>Loading details...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= lang('Common.btn_close') ?></button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function viewImportDetails(importLogId) {
        $('#importDetailsModal').modal('show');
        $('#import_details_body').html('<div class="text-center"><i class="fa fa-spinner fa-spin fa-3x"></i><p>Loading details...</p></div>');

        $.ajax({
            url: '<?= site_url('school-owner/students/bulk/import/details') ?>',
            type: 'POST',
            data: { import_log_id: importLogId },
            dataType: 'json',
            success: function(response) {
                if (response.status) {
                    $('#import_details_body').html(response.html);
                } else {
                    $('#import_details_body').html('<div class="alert alert-danger">' + response.message + '</div>');
                }
            },
            error: function() {
                $('#import_details_body').html('<div class="alert alert-danger">Failed to load details.</div>');
            }
        });
    }
</script>