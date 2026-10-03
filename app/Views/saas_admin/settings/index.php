<?php 
// App Name

// Logo and Favicon paths
$logo_path   = base_url('public/uploads/settings/' . esc(setting('application', 'logo')));
$icon_path   = base_url('public/uploads/settings/' . esc(setting('application', 'favicon')));
?>

<?= form_open('saas-admin/settings/save', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'setting_form', 
    'method'  => 'post', 
    'enctype' => 'multipart/form-data',
    'data-parsley-validate'=>'' 
    ]); 
?>

<div class="row mb-3">
    <div class="col-md-6 col-sm-6 ">
        <h3 class="text-secondary"><i class="fa fa-cog"></i> <?= lang('System.settings'); ?></h3>
    </div>

    <div class="col-md-6 col-sm-6 text-end ">
        <div class="row  g-2 justify-content-end">
            <div class="col-auto">
                <a href="<?= base_url('saas-admin/menus'); ?>" class="btn btn-info"><i class="fa fa-bars"></i> Menu Settings</a>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> <?= lang('System.btn_save_settings') ?></button>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-12 ">
        <?= get_system_message(); ?>
    </div>
</div>


<!-- Settings Form -->
<div class="row">
    <div class="col-md-6">
        <!-- Institute Profile -->
        <div class="card mb-4">
            <div class="card-header">General Info</div>
            <div class="card-body">
            <div class="mb-3">
                <label class="form-label">App Name</label>
                <input type="text" name="settings[app_name]" class="form-control" value="<?= esc(setting('application', 'app_name')) ?>" placeholder="Enter app name">
            </div>
            
            <div class="mb-3">
                <label class="form-label">Logo Upload</label>
                <input type="file" name="settings[logo]" class="form-control" id="file_logo" accept="image/*">
                <img id="file_preview_logo" src="<?= $logo_path ?>"  class="preview-img mt-2" alt="Logo Preview" >
            </div>
            <div class="mb-3">
                <label class="form-label">Favicon Upload</label>
                <input type="file" name="settings[favicon]" class="form-control" id="file_favicon" accept="image/*">
                <img id="file_preview_favicon" src="<?= $icon_path ?>" class="preview-img mt-2" alt="Favicon Preview">
            </div>
            
            </div>
        </div>
    </div>

    <div class="col-md-6">
            
        <!-- System Preferences -->
        <div class="card mb-4">
            <div class="card-header">System Preferences</div>
            <div class="card-body">
            
            <div class="mb-3">
                <label class="form-label">Photo Max Size (KB)</label>
                <input type="number" name="settings[photo_max_size]" class="form-control" value="<?= esc(setting('application', 'photo_max_size')) ?>" min="512" max="5120">
                <div class="form-text">Maximum allowed size per photo.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Allowed Photo Extensions</label>
                <input type="text" name="settings[allowed_photo_extensions]" class="form-control" value="<?= esc(setting('application', 'allowed_photo_extensions')) ?>">
                <div class="form-text">Comma-separated list of allowed image extensions.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Per Item in List</label>
                <input type="number" name="settings[per_item_in_list]" class="form-control" value="<?= esc(setting('application', 'per_item_in_list')) ?>" min="5" max="100">
                <div class="form-text">Number of rows per page/list view.</div>
            </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <!-- Email Settings -->
        <div class="card mb-4">
            <div class="card-header">Email Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" name="settings[smtp_host]" class="form-control" value="<?= esc(setting('application', 'smtp_host')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" name="settings[smtp_port]" class="form-control" value="<?= esc(setting('application', 'smtp_port')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Username</label>
                    <input type="text" name="settings[smtp_username]" class="form-control" value="<?= esc(setting('application', 'smtp_username')) ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" name="settings[smtp_password]" class="form-control" value="<?= esc(setting('application', 'smtp_password')) ?>">
                </div>
                <div class="mb-3">
                    <button type="button" class="btn btn-info" onclick="showTestEmailModal()">
                        <i class="bi bi-envelope"></i> Test Email
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>


<?= form_close() ?>

<!-- Test Email Modal -->
<div class="modal fade" id="testEmailModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-envelope"></i> Test Email Configuration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="testEmailForm">
                    <div class="mb-3">
                        <label for="testEmailRecipient" class="form-label">Recipient Email</label>
                        <input type="email" class="form-control" id="testEmailRecipient" required placeholder="Enter email address to test">
                        <div class="form-text">Enter an email address to test if SMTP is working correctly.</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                    <i class="bi bi-send"></i> Send Test Email
                </button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
// image file live preview
document.querySelectorAll('input[type="file"]').forEach(input => {
    input.addEventListener('change', function(event) {
        const file = event.target.files[0];
        const previewId = `file_preview_${this.id.split('_')[1]}`;
        const preview = document.getElementById(previewId);

        if (file && preview) {
            const reader = new FileReader();

            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };

            reader.readAsDataURL(file);
        }
    });
});

function showTestEmailModal() {
    $('#testEmailModal').modal('show');
}

function sendTestEmail() {
    var email = $('#testEmailRecipient').val();
    
    if (!email) {
        alert('Please enter recipient email address');
        return;
    }
    
    // Show loading state
    $('.modal-footer button').prop('disabled', true);
    $('.modal-footer button:last-child').html('<i class="bi bi-hourglass-split"></i> Sending...');
    
    // Send AJAX request
    $.ajax({
        url: '<?= base_url('saas-admin/settings/test-email') ?>',
        type: 'POST',
        data: {
            email: email,
            <?= csrf_token() ?>: '<?= csrf_hash() ?>'
        },
        success: function(response) {
            $('#testEmailModal').modal('hide');
            if (response.success) {
                alert('Test email sent successfully! Please check your inbox.');
            } else {
                alert('Failed to send test email: ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            $('#testEmailModal').modal('hide');
            alert('Error sending test email: ' + error);
        },
        complete: function() {
            // Reset modal
            $('#testEmailModal').remove();
            // Re-add modal to DOM
            $('body').append(`
                <div class="modal fade" id="testEmailModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-envelope"></i> Test Email Configuration</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="testEmailForm">
                                    <div class="mb-3">
                                        <label for="testEmailRecipient" class="form-label">Recipient Email</label>
                                        <input type="email" class="form-control" id="testEmailRecipient" required placeholder="Enter email address to test">
                                        <div class="form-text">Enter an email address to test if SMTP is working correctly.</div>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="sendTestEmail()">
                                    <i class="bi bi-send"></i> Send Test Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `);
        }
    });
}
</script>
