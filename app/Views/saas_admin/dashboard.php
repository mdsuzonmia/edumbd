<?php
$subscriptionLabels = [
    0 => ['Pending', 'pending'],
    1 => ['Trial', 'trial'],
    2 => ['Active', 'active'],
    3 => ['Suspended', 'suspended'],
    4 => ['Expired', 'expired'],
    5 => ['Cancelled', 'cancelled'],
];

$trackedSubscriptions = array_sum(array_slice($subscriptionCounts ?? [], 0, 6));
$activeRate = $trackedSubscriptions > 0
    ? (int) round((($activeSubscriptions ?? 0) / $trackedSubscriptions) * 100)
    : 0;
$attentionTotal = (int) ($pendingPayments ?? 0) + (int) ($expiringSoon ?? 0);
$trendValues = array_column($revenueTrend ?? [], 'value');
$trendMaximum = max(1, $trendValues ? max($trendValues) : 0);
$currency = esc($revenue['currency'] ?? 'USD');

$initials = static function (?string $name): string {
    $words = preg_split('/\s+/', trim((string) $name)) ?: [];
    $letters = '';
    foreach (array_slice($words, 0, 2) as $word) {
        $letters .= mb_substr($word, 0, 1);
    }
    return $letters !== '' ? mb_strtoupper($letters) : 'SC';
};

$shortAmount = static function (float $amount): string {
    if ($amount >= 1000000) {
        return number_format($amount / 1000000, 1) . 'm';
    }
    if ($amount >= 1000) {
        return number_format($amount / 1000, 1) . 'k';
    }
    return number_format($amount, 0);
};

$formatDate = static function ($date, string $format = 'd M, Y'): string {
    return ! empty($date) ? date($format, strtotime($date)) : '—';
};
?>

<div class="saas-dashboard">
    <section class="dashboard-hero mb-4" aria-labelledby="dashboard-heading">
        <div class="hero-content row align-items-center g-3">
            <div class="col-lg-7">
                <span class="eyebrow">Platform overview</span>
                <h1 class="dashboard-title" id="dashboard-heading">Good <?= date('H') < 12 ? 'morning' : (date('H') < 18 ? 'afternoon' : 'evening') ?>, <?= esc(session()->get('user_name') ?: 'Admin') ?></h1>
                <p class="hero-subtitle mb-0">Monitor school growth, subscription health, and revenue from one clear workspace.</p>
            </div>
            <div class="col-lg-5 text-lg-end">
                <div class="hero-date mb-2"><i class="bi bi-calendar3 me-1"></i> <?= esc(date('l, d F Y')) ?></div>
                <div class="hero-actions d-flex flex-wrap gap-2 justify-content-lg-end">
                    <a href="<?= base_url('saas-admin/schools/create') ?>" class="hero-action"><i class="bi bi-building-add me-1"></i> Add school</a>
                    <a href="<?= base_url('saas-admin/subscriptions/create') ?>" class="hero-action is-primary"><i class="bi bi-plus-circle me-1"></i> New subscription</a>
                </div>
            </div>
        </div>
    </section>

    <section class="row g-3 mb-4" aria-label="Key performance indicators">
        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('saas-admin/schools') ?>" class="text-decoration-none d-block h-100">
                <article class="metric-card">
                    <span class="metric-icon"><i class="bi bi-buildings"></i></span>
                    <p class="metric-label">Total schools</p>
                    <p class="metric-value"><?= number_format((int) $totalSchools) ?></p>
                    <p class="metric-note mb-0"><strong>+<?= number_format((int) $newSchools) ?></strong> added this month</p>
                </article>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('saas-admin/subscriptions?status=2') ?>" class="text-decoration-none d-block h-100">
                <article class="metric-card">
                    <span class="metric-icon is-green"><i class="bi bi-patch-check"></i></span>
                    <p class="metric-label">Active subscriptions</p>
                    <p class="metric-value"><?= number_format((int) $activeSubscriptions) ?></p>
                    <p class="metric-note mb-0"><strong><?= number_format((int) $trialSubscriptions) ?></strong> currently on trial</p>
                </article>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('saas-admin/reports/revenue') ?>" class="text-decoration-none d-block h-100">
                <article class="metric-card">
                    <span class="metric-icon is-blue"><i class="bi bi-graph-up-arrow"></i></span>
                    <p class="metric-label">Revenue this month</p>
                    <p class="metric-value"><?= $currency ?> <?= number_format((float) ($revenue['monthly'] ?? 0), 2) ?></p>
                    <p class="metric-note mb-0"><?= $currency ?> <?= number_format((float) ($revenue['total'] ?? 0), 2) ?> collected all time</p>
                </article>
            </a>
        </div>
        <div class="col-sm-6 col-xl-3">
            <a href="<?= base_url('saas-admin/payments?status=pending') ?>" class="text-decoration-none d-block h-100">
                <article class="metric-card">
                    <span class="metric-icon is-amber"><i class="bi bi-exclamation-circle"></i></span>
                    <p class="metric-label">Needs attention</p>
                    <p class="metric-value"><?= number_format($attentionTotal) ?></p>
                    <p class="metric-note is-warning mb-0"><strong><?= number_format((int) $pendingPayments) ?></strong> pending payments · <?= number_format((int) $expiringSoon) ?> expiring</p>
                </article>
            </a>
        </div>
    </section>

    <?php if ($attentionTotal > 0): ?>
        <aside class="attention-strip mb-4" aria-label="Items requiring attention">
            <span class="attention-icon"><i class="bi bi-bell"></i></span>
            <div class="attention-copy">
                <strong>There are items waiting for review</strong>
                <?= number_format((int) $pendingPayments) ?> payment<?= (int) $pendingPayments === 1 ? '' : 's' ?> pending and <?= number_format((int) $expiringSoon) ?> subscription<?= (int) $expiringSoon === 1 ? '' : 's' ?> ending within 30 days.
            </div>
            <div class="attention-actions">
                <a href="<?= base_url('saas-admin/payments?status=pending') ?>">Review payments <i class="bi bi-arrow-right ms-1"></i></a>
            </div>
        </aside>
    <?php endif; ?>

    <section class="row g-3 mb-4" aria-label="Business overview">
        <div class="col-xl-8">
            <article class="panel-card">
                <header class="panel-header">
                    <div>
                        <h2 class="panel-title">Revenue overview</h2>
                        <p class="panel-subtitle">Paid transactions across the last six months</p>
                    </div>
                    <a href="<?= base_url('saas-admin/reports/revenue') ?>" class="panel-link">Full report <i class="bi bi-arrow-up-right"></i></a>
                </header>
                <div class="revenue-chart">
                    <div class="chart-summary">
                        <strong><?= $currency ?> <?= number_format((float) ($revenue['monthly'] ?? 0), 2) ?></strong>
                        <span>this month</span>
                    </div>
                    <div class="bars" role="img" aria-label="Six month paid revenue chart">
                        <?php foreach (($revenueTrend ?? []) as $trend): ?>
                            <?php $barHeight = max(3, (int) round(((float) $trend['value'] / $trendMaximum) * 88)); ?>
                            <div class="bar-item" title="<?= esc($trend['label']) ?>: <?= $currency ?> <?= number_format((float) $trend['value'], 2) ?>">
                                <span class="bar-value"><?= esc($shortAmount((float) $trend['value'])) ?></span>
                                <span class="bar-fill" style="--bar-height: <?= $barHeight ?>%"></span>
                                <span class="bar-label"><?= esc($trend['label']) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </article>
        </div>
        <div class="col-xl-4">
            <article class="panel-card">
                <header class="panel-header">
                    <div>
                        <h2 class="panel-title">Subscription health</h2>
                        <p class="panel-subtitle"><?= number_format($trackedSubscriptions) ?> subscriptions being tracked</p>
                    </div>
                    <a href="<?= base_url('saas-admin/subscriptions') ?>" class="panel-link">Manage</a>
                </header>
                <div class="health-body">
                    <div class="health-score">
                        <div class="score-ring" style="--score: <?= $activeRate ?>%" aria-label="<?= $activeRate ?> percent active"><?= $activeRate ?>%</div>
                        <div class="score-copy">
                            <strong>Active rate</strong>
                            <span>Share of current subscriptions in active status.</span>
                        </div>
                    </div>
                    <div class="status-list">
                        <div class="status-row"><span class="status-dot is-active"></span><span>Active</span><strong><?= number_format((int) ($subscriptionCounts[2] ?? 0)) ?></strong></div>
                        <div class="status-row"><span class="status-dot is-trial"></span><span>Trial</span><strong><?= number_format((int) ($subscriptionCounts[1] ?? 0)) ?></strong></div>
                        <div class="status-row"><span class="status-dot is-pending"></span><span>Pending</span><strong><?= number_format((int) ($subscriptionCounts[0] ?? 0)) ?></strong></div>
                        <div class="status-row"><span class="status-dot is-risk"></span><span>Expired or cancelled</span><strong><?= number_format((int) ($subscriptionCounts[4] ?? 0) + (int) ($subscriptionCounts[5] ?? 0)) ?></strong></div>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="row g-3" aria-label="Latest records">
        <div class="col-12">
            <article class="panel-card">
                <header class="panel-header">
                    <div>
                        <h2 class="panel-title">New schools</h2>
                        <p class="panel-subtitle">Recently registered schools and their current plan</p>
                    </div>
                    <a href="<?= base_url('saas-admin/schools') ?>" class="panel-link">View all schools <i class="bi bi-arrow-right"></i></a>
                </header>
                <div class="table-responsive">
                    <table class="table dashboard-table align-middle">
                        <thead>
                            <tr>
                                <th>School</th>
                                <th>Owner</th>
                                <th>Plan</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th class="text-end"><span class="visually-hidden">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($recentSchools)): ?>
                                <?php foreach ($recentSchools as $school): ?>
                                    <?php
                                    $schoolSubscription = $school->subscription ?? null;
                                    $schoolStatus = $schoolSubscription ? ($subscriptionLabels[(int) $schoolSubscription->status] ?? ['Unknown', 'suspended']) : ['No plan', 'suspended'];
                                    $isNew = ! empty($school->created_at) && strtotime($school->created_at) >= strtotime('-7 days');
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="entity-cell">
                                                <span class="entity-avatar"><?= esc($initials($school->name)) ?></span>
                                                <span class="min-width-0">
                                                    <span class="entity-name"><?= esc($school->name) ?><?php if ($isNew): ?><span class="new-pill">New</span><?php endif; ?></span>
                                                    <span class="entity-meta"><?= esc($school->email ?: ($school->country ?: 'No contact provided')) ?></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="entity-name"><?= esc($school->owner_name ?: 'Not assigned') ?></span>
                                            <span class="entity-meta"><?= esc($school->owner_email ?: '—') ?></span>
                                        </td>
                                        <td><?= esc($schoolSubscription->plan_name ?? ((int) ($schoolSubscription->status ?? -1) === 1 ? 'Trial plan' : '—')) ?></td>
                                        <td><span class="status-pill is-<?= esc($schoolStatus[1]) ?>"><?= esc($schoolStatus[0]) ?></span></td>
                                        <td><?= esc($formatDate($school->created_at)) ?></td>
                                        <td class="text-end"><a href="<?= base_url('saas-admin/schools/view/' . (int) $school->id) ?>" class="row-action" title="View <?= esc($school->name) ?>"><i class="bi bi-arrow-right"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="6" class="empty-state"><i class="bi bi-building d-block fs-4 mb-2"></i>No schools have been added yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>

        <div class="col-12">
            <article class="panel-card">
                <header class="panel-header">
                    <div>
                        <h2 class="panel-title">Latest subscriptions</h2>
                        <p class="panel-subtitle">Most recent plan sign-ups and renewals</p>
                    </div>
                    <a href="<?= base_url('saas-admin/subscriptions') ?>" class="panel-link">View all subscriptions <i class="bi bi-arrow-right"></i></a>
                </header>
                <div class="table-responsive">
                    <table class="table dashboard-table align-middle">
                        <thead>
                            <tr>
                                <th>Subscriber</th>
                                <th>Plan</th>
                                <th>Billing</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Ends</th>
                                <th class="text-end"><span class="visually-hidden">Action</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (! empty($recentSubscriptions)): ?>
                                <?php foreach ($recentSubscriptions as $subscription): ?>
                                    <?php $statusMeta = $subscriptionLabels[(int) $subscription->status] ?? ['Unknown', 'suspended']; ?>
                                    <tr>
                                        <td>
                                            <div class="entity-cell">
                                                <span class="entity-avatar"><?= esc($initials($subscription->user_name)) ?></span>
                                                <span class="min-width-0">
                                                    <span class="entity-name"><?= esc($subscription->user_name ?: 'Unknown subscriber') ?></span>
                                                    <span class="entity-meta"><?= esc($subscription->user_email ?: '—') ?></span>
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="entity-name"><?= esc($subscription->plan_name ?: ((int) $subscription->status === 1 ? 'Trial plan' : '—')) ?></span></td>
                                        <td><?= esc(ucfirst((string) ($subscription->billing_cycle ?: '—'))) ?></td>
                                        <td><span class="entity-name"><?= esc(strtoupper((string) ($subscription->currency ?: 'USD'))) ?> <?= number_format((float) $subscription->amount, 2) ?></span></td>
                                        <td><span class="status-pill is-<?= esc($statusMeta[1]) ?>"><?= esc($statusMeta[0]) ?></span></td>
                                        <td><?= esc($formatDate($subscription->end_date)) ?></td>
                                        <td class="text-end"><a href="<?= base_url('saas-admin/subscriptions/view/' . (int) $subscription->id) ?>" class="row-action" title="View subscription"><i class="bi bi-arrow-right"></i></a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="empty-state"><i class="bi bi-repeat d-block fs-4 mb-2"></i>No subscriptions have been created yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </div>
    </section>
</div>
