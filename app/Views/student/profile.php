<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-sm-6">
            <h3 class="text-secondary mb-0"><i class="bi bi-person"></i> My Profile</h3>
        </div>
        <div class="col-sm-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                <i class="fa fa-edit"></i> Edit Profile
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 text-center mb-3">
                            <?php
                            $photo = !empty($student->photo) ? base_url('uploads/' . $student->photo) : base_url('uploads/default.png');
                            ?>
                            <img src="<?= $photo ?>" alt="Student Photo" class="img-thumbnail rounded-circle" style="width: 180px; height: 180px; object-fit: cover;">
                            <h5 class="mt-2"><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></h5>
                            <p class="text-muted"><?= esc($student->student_code) ?></p>
                        </div>

                        <div class="col-md-8">
                            <h6 class="border-bottom pb-2">Personal Information</h6>
                            <table class="table table-sm table-bordered">
                                <tr>
                                    <th width="30%">Full Name</th>
                                    <td><?= esc($student->first_name . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? '')) ?></td>
                                </tr>
                                <tr>
                                    <th>Gender</th>
                                    <td><?= esc($student->gender ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Date of Birth</th>
                                    <td><?= !empty($student->date_of_birth) ? date('d M, Y', strtotime($student->date_of_birth)) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <th>Phone</th>
                                    <td><?= esc($student->phone ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Email</th>
                                    <td><?= esc($student->email ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Religion</th>
                                    <td><?= esc($student->religion ?? 'N/A') ?></td>
                                </tr>
                                <tr>
                                    <th>Nationality</th>
                                    <td><?= esc($student->nationality ?? 'N/A') ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Profile Modal -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm">
                    <div class="mb-3">
                        <label class="form-label">First Name</label>
                        <input type="text" name="first_name" class="form-control" value="<?= esc($student->first_name) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Middle Name</label>
                        <input type="text" name="middle_name" class="form-control" value="<?= esc($student->middle_name ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Last Name</label>
                        <input type="text" name="last_name" class="form-control" value="<?= esc($student->last_name ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Gender</label>
                        <select name="gender" class="form-select">
                            <option value="">Select</option>
                            <option value="Male" <?= ($student->gender ?? '') == 'Male' ? 'selected' : '' ?>>Male</option>
                            <option value="Female" <?= ($student->gender ?? '') == 'Female' ? 'selected' : '' ?>>Female</option>
                            <option value="Other" <?= ($student->gender ?? '') == 'Other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date of Birth</label>
                        <input type="date" name="date_of_birth" class="form-control" value="<?= esc($student->date_of_birth ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-control" value="<?= esc($student->phone ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" value="<?= esc($student->email ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password (leave blank to keep current)</label>
                        <input type="password" name="password" class="form-control">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitProfileUpdate()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script>
function submitProfileUpdate() {
    var formData = new FormData(document.getElementById('editProfileForm'));
    
    fetch('<?= base_url('student/profile/update') ?>', {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': '<?= csrf_hash() ?>'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.status) {
            alert('Profile updated successfully!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to update profile'));
        }
    })
    .catch(error => {
        alert('Error updating profile');
        console.error('Error:', error);
    });
}
</script>