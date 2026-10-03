<?php 
$promotion_url = base_url('examination/promotion');
?>
<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="fa fa-history"></i> Promotion History</h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= $promotion_url ?>" class="btn btn-outline-primary btn-sm">
            <i class="fa fa-arrow-up"></i> Back to Promotion
        </a>
    </div>
</div>

<?= get_system_message(); ?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <form method="get" action="<?= base_url('examination/promotion/history') ?>" class="row g-3">
                    <div class="col-md-4">
                        <label for="school_id" class="form-label">School</label>
                        <select name="school_id" id="school_id" class="form-control" onchange="this.form.submit()">
                            <option value="">All Schools</option>
                            <?php foreach ($school_list as $id => $name): ?>
                                <option value="<?= $id ?>" <?= $selected_school == $id ? 'selected' : '' ?>><?= $name ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <?php if (empty($items)): ?>
                    <div class="alert alert-info mb-0">
                        <i class="fa fa-info-circle"></i> No promotion history found.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr style="background:#1a73e8;color:#fff;">
                                    <th>#</th>
                                    <th>School</th>
                                    <th>From</th>
                                    <th>To</th>
                                    <th>Total</th>
                                    <th>Success</th>
                                    <th>Failed</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $sl = ($pager->getCurrentPage() - 1) * $pager->getPerPage(); ?>
                                <?php foreach ($items as $item): ?>
                                    <?php $sl++; ?>
                                    <tr>
                                        <td><?= $sl ?></td>
                                        <td><span class="badge bg-info"><?= esc($item->school_name ?? '') ?></span></td>
                                        <td><strong><?= esc($item->from_class_title ?? '') ?></strong> <small class="text-muted">(<?= esc($item->from_session_title ?? '') ?>)</small></td>
                                        <td><strong><?= esc($item->to_class_title ?? '') ?></strong> <small class="text-muted">(<?= esc($item->to_session_title ?? '') ?>)</small></td>
                                        <td class="text-center"><?= $item->total_students ?></td>
                                        <td class="text-center text-success"><strong><?= $item->success_count ?></strong></td>
                                        <td class="text-center text-danger"><strong><?= $item->failed_count ?></strong></td>
                                        <td class="text-center">
                                            <?php if ($item->status === 'completed'): ?>
                                                <span class="badge bg-success">Completed</span>
                                            <?php elseif ($item->status === 'partial'): ?>
                                                <span class="badge bg-warning">Partial</span>
                                            <?php elseif ($item->status === 'rolled_back'): ?>
                                                <span class="badge bg-secondary">Rolled Back</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?= date('d-m-Y h:i A', strtotime($item->created_at)) ?></small></td>
                                        <td class="text-end">
                                            <a href="<?= base_url('examination/promotion/history/' . $item->id) ?>" class="btn btn-info btn-sm">
                                                <i class="fa fa-eye"></i> View
                                            </a>
                                            <?php if ($item->status !== 'rolled_back'): ?>
                                                <button type="button" class="btn btn-danger btn-sm" onclick="rollbackPromotion(<?= $item->id ?>)">
                                                    <i class="fa fa-undo"></i> Rollback
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <?= $pager->links('default', $pagerTemplate) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
var csrfToken = '<?= csrf_hash() ?>';

function rollbackPromotion(promotionId) {
    if (!confirm('Are you sure you want to rollback this promotion? This will delete the new enrollments created.')) {
        return;
    }

    $.ajax({
        url: '<?= base_url('examination/promotion/rollback') ?>/' + promotionId,
        type: 'POST',
        data: {
            '<?= csrf_token() ?>': csrfToken
        },
        dataType: 'json',
        success: function(resp) {
            if (resp.success) {
                alert(resp.message);
                location.reload();
            } else {
                alert(resp.message);
            }
        },
        error: function() {
            alert('An error occurred during rollback.');
        }
    });
}
</script>