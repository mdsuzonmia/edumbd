<div class="container">
    <div class="row">
        <div class="col-md-12 mt-5">
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="my-5">
                        <i class="bi bi-shield-exclamation text-danger" style="font-size: 80px;"></i>
                        <h1 class="display-4 text-danger mt-4">403 - Unauthorized Access</h1>
                        <p class="lead text-muted mt-3">
                            <i class="bi bi-exclamation-triangle"></i> 
                            You do not have permission to access this page.
                        </p>
                        <p class="text-muted mb-4">
                            Please contact your school administrator if you believe this is an error.
                        </p>
                        <div class="mt-4">
                            <a href="<?= base_url('login') ?>" class="btn btn-primary btn-lg me-2">
                                <i class="bi bi-box-arrow-in-right"></i> Login
                            </a>
                            <a href="<?= base_url('student/dashboard') ?>" class="btn btn-secondary btn-lg">
                                <i class="bi bi-speedometer2"></i> Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>