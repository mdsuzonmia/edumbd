<!-- Examination Dashboard -->
<div class="right_col" role="main">
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3>Examination Dashboard</h3>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
            <!-- Total Schools -->
            <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="x_panel tile">
                    <div class="x_title">
                        <h2>Total Schools</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <h1 class="text-center"><?= $total_schools ?? 0 ?></h1>
                        <p class="text-center">Schools under your management</p>
                    </div>
                </div>
            </div>

            <!-- Total Exams -->
            <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="x_panel tile">
                    <div class="x_title">
                        <h2>Total Exams</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <h1 class="text-center"><?= $total_exams ?? 0 ?></h1>
                        <p class="text-center">Active examinations</p>
                    </div>
                </div>
            </div>

            <!-- Total Results -->
            <div class="col-md-4 col-sm-4 col-xs-12">
                <div class="x_panel tile">
                    <div class="x_title">
                        <h2>Total Results</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <h1 class="text-center"><?= $total_results ?? 0 ?></h1>
                        <p class="text-center">Generated results</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Results -->
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel tile">
                    <div class="x_title">
                        <h2>Pending Results</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <h1 class="text-center"><?= $pending_results ?? 0 ?></h1>
                        <p class="text-center">Results awaiting publication</p>
                        <?php if (($pending_results ?? 0) > 0): ?>
                            <div class="text-center">
                                <a href="<?= base_url('examination/publish') ?>" class="btn btn-primary">
                                    Publish Results
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Quick Actions -->
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="x_panel">
                    <div class="x_title">
                        <h2>Quick Actions</h2>
                        <div class="clearfix"></div>
                    </div>
                    <div class="x_content">
                        <div class="row">
                            <div class="col-md-3 col-sm-3 col-xs-6">
                                <a href="<?= base_url('examination/exam-setup/create') ?>" class="btn btn-primary btn-block">
                                    <i class="fa fa-plus"></i> New Exam
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-6">
                                <a href="<?= base_url('examination/marks') ?>" class="btn btn-success btn-block">
                                    <i class="fa fa-edit"></i> Enter Marks
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-6">
                                <a href="<?= base_url('examination/results/generate') ?>" class="btn btn-info btn-block">
                                    <i class="fa fa-cogs"></i> Generate Results
                                </a>
                            </div>
                            <div class="col-md-3 col-sm-3 col-xs-6">
                                <a href="<?= base_url('examination/reports/individual-result') ?>" class="btn btn-warning btn-block">
                                    <i class="fa fa-file-text"></i> View Reports
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>