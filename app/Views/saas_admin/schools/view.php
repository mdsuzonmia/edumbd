<?php
$status_list = get_status();
$logo_path = !empty($school->logo) ? base_url('uploads/' . $school->logo) : base_url('uploads/logo.png');

?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-building"></i> <?= esc($school->name) ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        
        <a href="<?= base_url('saas-admin/schools') ?>" class="btn btn-sm btn-secondary">
            <i class="fa fa-arrow-left"></i> <?= lang('School.back_to_school') ?>
        </a>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <img src="<?= esc($logo_path) ?>" class="img-thumbnail mb-3" style="max-width: 140px; max-height: 140px;" alt="<?= esc($school->name) ?>">
                <h5><?= esc($school->name) ?></h5>
                <p class="text-muted mb-1"><?= esc($school->slug) ?></p>
                <p class="mb-0">
                    <?php if ((int) $school->status === 1): ?>
                        <span class="badge text-bg-success"><?= esc($status_list[1] ?? 'Published') ?></span>
                    <?php elseif ((int) $school->status === 2): ?>
                        <span class="badge text-bg-danger"><?= esc($status_list[2] ?? 'Trash') ?></span>
                    <?php else: ?>
                        <span class="badge text-bg-warning"><?= esc($status_list[0] ?? 'Unpublished') ?></span>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">School Details</div>
            <div class="card-body">
                <table class="table table-bordered mb-0">
                    <tr><th width="220">Email</th><td><?= esc($school->email ?: '-') ?></td></tr>
                    <tr><th>Phone</th><td><?= esc($school->phone ?: '-') ?></td></tr>
                    <tr><th>Country</th><td><?= esc($school->country ?: '-') ?></td></tr>
                    <tr><th>Timezone</th><td><?= esc($school->timezone ?: '-') ?></td></tr>
                    <tr><th>Custom Domain</th><td><?= esc($school->custom_domain ?: '-') ?></td></tr>
                    <tr><th>Address</th><td><?= esc($school->address ?: '-') ?></td></tr>
                    <tr><th>Created</th><td><?= $school->created_at ? esc(date('d M, Y H:i', strtotime($school->created_at))) : '-' ?></td></tr>
                </table>
            </div>
        </div>

        
    </div>
</div>
