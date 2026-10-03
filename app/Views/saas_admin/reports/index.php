<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-bar-chart"></i> <?= lang('Report.heading_index'); ?></h3>
    </div>
    <div class="col-sm-6 text-end">
        <a href="<?= base_url('saas-admin/reports/schools') ?>" class="btn btn-sm btn-info"><i class="bi bi-building"></i> <?= lang('Report.schools_report') ?></a>
        <a href="<?= base_url('saas-admin/reports/revenue') ?>" class="btn btn-sm btn-success"><i class="bi bi-cash-stack"></i> <?= lang('Report.revenue_report') ?></a>
        <a href="<?= base_url('saas-admin/reports/subscriptions') ?>" class="btn btn-sm btn-primary"><i class="bi bi-repeat"></i> <?= lang('Report.subscriptions_report') ?></a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.total_schools') ?></h6>
                <h3><?= esc($totalSchools) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.published_schools') ?></h6>
                <h3><?= esc($publishedSchools) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.total_subscriptions') ?></h6>
                <h3><?= esc($totalSubscriptions) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.active_subscriptions') ?></h6>
                <h3><?= esc($activeSubscriptions) ?></h3>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.trial_subscriptions') ?></h6>
                <h3><?= esc($trialSubscriptions) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.expired_subscriptions') ?></h6>
                <h3><?= esc($expiredSubscriptions) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.total_revenue') ?></h6>
                <h3><?= number_format((float) $totalRevenue, 2) ?></h3>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6><?= lang('Report.monthly_revenue') ?></h6>
                <h3><?= number_format((float) $monthlyRevenue, 2) ?></h3>
            </div>
        </div>
    </div>
</div>
