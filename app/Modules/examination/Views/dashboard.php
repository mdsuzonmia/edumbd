<div class="container-fluid">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body ">
                    <h3 class="mb-0"><?= lang('Dashboard.page_title') ?? 'Examination Dashboard' ?></h3>
                    <p class="mb-0 opacity-75">Welcome to the Examination Management System</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 1 -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-building fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Schools</p>
                            <h3 class="mb-0 fw-bold"><?= $total_schools ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-success bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-journal-check fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Active Exams</p>
                            <h3 class="mb-0 fw-bold"><?= $total_exams ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-info bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-file-earmark-text fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Total Results</p>
                            <h3 class="mb-0 fw-bold"><?= $total_results ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-warning bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-clock-history fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Pending Results</p>
                            <h3 class="mb-0 fw-bold"><?= $pending_results ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards Row 2 -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-purple bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-book fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Subjects</p>
                            <h3 class="mb-0 fw-bold"><?= $total_subjects ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-danger bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-people fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Students</p>
                            <h3 class="mb-0 fw-bold"><?= $total_students ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-secondary bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-grid fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Classes</p>
                            <h3 class="mb-0 fw-bold"><?= $total_classes ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="bg-teal bg-gradient text-white rounded-3 p-3" style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                                <i class="bi bi-calculator fs-3"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <p class="text-muted mb-0 small">Aggregates</p>
                            <h3 class="mb-0 fw-bold"><?= $total_aggregates ?? 0 ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-lightning text-warning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/exam-setup/create') ?>" class="btn btn-primary w-100">
                                <i class="bi bi-plus-circle"></i><br>
                                <small>New Exam</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/marks/input') ?>" class="btn btn-success w-100">
                                <i class="bi bi-pencil-square"></i><br>
                                <small>Enter Marks</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/results/generate') ?>" class="btn btn-info w-100">
                                <i class="bi bi-gear"></i><br>
                                <small>Generate Results</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/aggregate') ?>" class="btn btn-warning w-100">
                                <i class="bi bi-calculator"></i><br>
                                <small>Aggregate Results</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/result-publish') ?>" class="btn btn-danger w-100">
                                <i class="bi bi-send"></i><br>
                                <small>Publish Results</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-2">
                            <a href="<?= base_url('examination/remarks/aggregate') ?>" class="btn btn-secondary w-100">
                                <i class="bi bi-chat-square-text"></i><br>
                                <small>Remarks</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity & Pending Tasks -->
    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-list-check text-warning"></i> Pending Tasks</h5>
                </div>
                <div class="card-body">
                    <?php if (($pending_results ?? 0) > 0): ?>
                        <div class="alert alert-warning border-0 shadow-sm">
                            <i class="bi bi-exclamation-triangle"></i> 
                            You have <strong><?= $pending_results ?></strong> results pending publication.
                            <a href="<?= base_url('examination/result-publish') ?>" class="alert-link">Publish now</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success border-0 shadow-sm">
                            <i class="bi bi-check-circle"></i> All results have been published.
                        </div>
                    <?php endif; ?>
                    
                    <?php if (($total_exams ?? 0) == 0): ?>
                        <div class="alert alert-info border-0 shadow-sm">
                            <i class="bi bi-info-circle"></i> 
                            No exams created yet. <a href="<?= base_url('examination/exam-setup/create') ?>" class="alert-link">Create your first exam</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0">
                    <h5 class="mb-0"><i class="bi bi-link text-info"></i> Quick Links</h5>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <a href="<?= base_url('examination/exam-setup') ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="bi bi-journal-text text-primary me-2"></i> Exam Setup
                        </a>
                        <a href="<?= base_url('examination/marks/list') ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="bi bi-pencil text-success me-2"></i> Marks Management
                        </a>
                        <a href="<?= base_url('examination/results/generate') ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="bi bi-gear text-info me-2"></i> Generate Results
                        </a>
                        <a href="<?= base_url('examination/aggregate') ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="bi bi-calculator text-warning me-2"></i> Aggregate Results
                        </a>
                        <a href="<?= base_url('examination/promotion') ?>" class="list-group-item list-group-item-action border-0 px-0">
                            <i class="bi bi-arrow-up text-danger me-2"></i> Student Promotion
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.bg-purple {
    background-color: #6f42c1 !important;
}
.bg-teal {
    background-color: #20c997 !important;
}
</style>
