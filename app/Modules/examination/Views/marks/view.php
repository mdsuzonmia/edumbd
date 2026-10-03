<?php
$record = isset($record) ? $record : null;
if (!$record) {
    echo '<div class="alert alert-danger">Record not found.</div>';
    return;
}
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-eye"></i> <?= lang('Mark.page_title_list'); ?> - View Details</h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('examination/marks') ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('Mark.back_to'); ?>
        </a>
        <?php if (!empty($record->token)): ?>
        <a href="<?= base_url('examination/marks/edit/' . $record->token) ?>" class="btn btn-info">
            <i class="fa fa-edit"></i> <?= lang('Common.text_edit'); ?>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <th width="200px"><?= lang('Mark.field_student_name'); ?></th>
                            <td><?= esc(trim(($record->first_name ?? '') . ' ' . ($record->last_name ?? ''))) ?></td>
                        </tr>
                        <tr>
                            <th><?= lang('Mark.field_subject'); ?></th>
                            <td><?= esc($record->subject_title ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>School</th>
                            <td><?= esc($record->school_name ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th><?= lang('Mark.field_marks'); ?></th>
                            <td>
                                <strong><?= esc($record->obtained_mark ?? 0) ?></strong> / 
                                <strong><?= esc($record->full_mark ?? 0) ?></strong>
                            </td>
                        </tr>
                        <tr>
                            <th><?= lang('Mark.field_absent'); ?></th>
                            <td>
                                <?php if (!empty($record->is_absent)): ?>
                                    <span class="badge text-bg-danger"><?= lang('Common.yes') ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-success"><?= lang('Common.no') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= lang('Common.field_remarks'); ?></th>
                            <td><?= esc($record->remarks ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th><?= lang('Common.th_status'); ?></th>
                            <td>
                                <?php
                                $status_array = get_status();
                                echo $status_array[$record->status] ?? 'Unknown';
                                ?>
                            </td>
                        </tr>
                        <tr>
                            <th><?= lang('Common.created_at'); ?></th>
                            <td><?= date('d M, Y h:i A', strtotime($record->created_at)) ?></td>
                        </tr>
                        <tr>
                            <th><?= lang('Common.updated_at'); ?></th>
                            <td><?= date('d M, Y h:i A', strtotime($record->updated_at)) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>