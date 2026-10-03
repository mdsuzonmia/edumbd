<?php 
$back_url = base_url('examination/promotion/history');
?>
<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-eye"></i> Promotion Details</h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= $back_url ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-arrow-left"></i> Back to History
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header bg-primary text-white">
                <strong><i class="fa fa-info-circle"></i> Promotion Batch Information</strong>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th style="width:120px;">School</th>
                                <td><span class="badge bg-info"><?= esc($promotion->school_name ?? '') ?></span></td>
                            </tr>
                            <tr>
                                <th>From</th>
                                <td><strong><?= esc($promotion->from_class_title ?? '') ?></strong> <small class="text-muted">(<?= esc($promotion->from_session_title ?? '') ?>)</small></td>
                            </tr>
                            <tr>
                                <th>To</th>
                                <td><strong><?= esc($promotion->to_class_title ?? '') ?></strong> <small class="text-muted">(<?= esc($promotion->to_session_title ?? '') ?>)</small></td>
                            </tr>
                            <tr>
                                <th>Date</th>
                                <td><?= date('d-m-Y h:i A', strtotime($promotion->created_at)) ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <th >Total Students</th>
                                <td><span class="badge bg-primary"><?= $promotion->total_students ?></span></td>
                            </tr>
                            <tr>
                                <th>Successful</th>
                                <td><span class="badge bg-success"><?= $promotion->success_count ?></span></td>
                            </tr>
                            <tr>
                                <th>Failed</th>
                                <td><span class="badge bg-danger"><?= $promotion->failed_count ?></span></td>
                            </tr>
                            <tr>
                                <th>Status</th>
                                <td>
                                    <?php if ($promotion->status === 'completed'): ?>
                                        <span class="badge bg-success">Completed</span>
                                    <?php elseif ($promotion->status === 'partial'): ?>
                                        <span class="badge bg-warning">Partial</span>
                                    <?php elseif ($promotion->status === 'rolled_back'): ?>
                                        <span class="badge bg-secondary">Rolled Back</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php if (!empty($promotion->notes)): ?>
                            <tr>
                                <th>Notes</th>
                                <td><em><?= esc($promotion->notes) ?></em></td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-success text-white">
                <strong><i class="fa fa-list"></i> Student Details</strong>
            </div>
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fa fa-info-circle"></i> No student records found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr style="background:#1a73e8;color:#fff;">
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>From Roll</th>
                                    <th>To Roll</th>
                                    <th>Status</th>
                                    <th>Error Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sl = 0; ?>
                                <?php foreach ($items as $item): ?>
                                    <?php $sl++; ?>
                                    <tr>
                                        <td><?= $sl ?></td>
                                        <td><?= esc($item->student_code ?? '') ?></td>
                                        <td>
                                            <?php 
                                                $name = trim(($item->first_name ?? '') . ' ' . ($item->middle_name ?? '') . ' ' . ($item->last_name ?? ''));
                                                $name = preg_replace('/\s+/', ' ', $name);
                                                echo esc($name);
                                            ?>
                                        </td>
                                        <td><?= esc($item->from_roll_no ?? '-') ?></td>
                                        <td><?= esc($item->to_roll_no ?? '-') ?></td>
                                        <td class="text-center">
                                            <?php if ($item->status === 'success'): ?>
                                                <span class="badge bg-success">Success</span>
                                            <?php elseif ($item->status === 'failed'): ?>
                                                <span class="badge bg-danger">Failed</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary"><?= esc($item->status) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($item->error_message ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        <strong>Summary:</strong> 
                        <span class="badge bg-success">Success: <?= $promotion->success_count ?></span>
                        <span class="badge bg-danger">Failed: <?= $promotion->failed_count ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>