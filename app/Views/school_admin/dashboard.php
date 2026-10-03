


<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <h4>Welcome Back, <?= session()->get('user_name') ?></h4>
        <p class="text-muted mb-0">
            Manage your school operations efficiently.
        </p>
    </div>
</div>



<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-transparent">
        Subscription Information
    </div>

    <div class="card-body">

        <h5><?= esc($subscription['plan_name'] ?? 'No Plan') ?></h5>

        <div class="progress mb-3">
            <div class="progress-bar bg-success"
                style="width: <?= $subscription_percent ?>%">
            </div>
        </div>

        <p>
            Expiry Date:
            <strong><?= esc($subscription['expires_at'] ?? date('Y-m-d H:i:s')) ?></strong>
        </p>

        <a href="<?= site_url('school/settings') ?>"
            class="btn btn-primary">
            Manage Subscription
        </a>

    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-header bg-transparent">
        Quick Actions
    </div>

    <div class="card-body">

        <div class="row">

            <div class="col-md-3 mb-2">
                <a href="<?= site_url('school/students/create') ?>"
                    class="btn btn-outline-primary w-100">
                    Add Student
                </a>
            </div>

            <div class="col-md-3 mb-2">
                <a href="<?= site_url('school/teachers/create') ?>"
                    class="btn btn-outline-success w-100">
                    Add Teacher
                </a>
            </div>

            <div class="col-md-3 mb-2">
                <a href="<?= site_url('school/academics/exams/create') ?>"
                    class="btn btn-outline-warning w-100">
                    Create Exam
                </a>
            </div>

            <div class="col-md-3 mb-2">
                <a href="<?= site_url('school/academics/exams') ?>"
                    class="btn btn-outline-danger w-100">
                    Manage Exams
                </a>
            </div>

        </div>

    </div>
</div>

<div class="card border-0 shadow-sm mt-4">
    <div class="card-header bg-transparent">
        Recent Activities
    </div>

    <div class="card-body">

        <ul class="list-group list-group-flush">

            <?php if (!empty($activities) && is_array($activities)): ?>
                <?php foreach ($activities as $activity): ?>
                    <li class="list-group-item">
                        <?= esc($activity['message'] ?? '-') ?>
                        <small class="text-muted float-end">
                            <?= esc(date('d M Y H:i', strtotime($activity['created_at'] ?? 'now'))) ?>
                        </small>
                    </li>
                <?php endforeach; ?>
            <?php else: ?>
                <li class="list-group-item text-muted">
                    No recent activities found.
                </li>
            <?php endif; ?>

        </ul>

    </div>
</div>
