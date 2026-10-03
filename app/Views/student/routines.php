<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-sm-6">
            <h3 class="text-secondary mb-0"><i class="bi bi-calendar-week"></i> My Routines</h3>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <h6 class="border-bottom pb-2">Academic Enrollments</h6>
                    <?php if (!empty($enrollments)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Academic Year</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Roll No</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrollments as $enr): ?>
                                        <tr>
                                            <td><?= esc($enr->session_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->class_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->section_title ?? 'N/A') ?></td>
                                            <td><?= esc($enr->roll_no ?? 'N/A') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted">No enrollment records found.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>