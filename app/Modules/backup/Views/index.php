<?php 
$download_json_link    = base_url('/backup/download-json');
$download_photos_link    = base_url('/backup/download-photo');
?>

<div class="right_col" role="main">
    <div class="row">
        <div class="col-md-12 ">
            <h3><?= lang('Backup.page_title') ?></h3>
            <div id="result" class=""></div>
            <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />
        </div>
    </div>

    
    <div class="row">
        <div class="col-sm-6 mb-2 ">
            <div class="card p-3">
                <div class="body ">
                    <h4 class="mt-0"><?= lang('Backup.full_system_backup') ?></h4>
                    <h6 class="card-title"><?= lang('Backup.choose_backup_json_file') ?></h6>
                    
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                        <input type="file" class="form-control" id="backup_json" >
                        </div>
                        <button class="btn btn-success btn_backup_json" type="submit"><i class="fa fa-upload"></i> <?= lang('Backup.btn_upload') ?></button>
                    </div>

                    <a href="<?= $download_json_link ?>" class="btn btn-info"><i class="fa fa-download"></i> <?= lang('Backup.download_backup_json') ?></a>
                </div>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="card p-3">
                <div class="body ">
                    <h3 class="mt-0"><?= lang('Backup.full_system_photo_backup') ?></h3>
                    <h6 class="card-title"><?= lang('Backup.choose_backup_photo_file') ?></h6>
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                        <input type="file" class="form-control" id="backup_photo" >
                        </div>
                        <button class="btn btn-success btn_backup_photo" type="submit"><i class="fa fa-upload"></i> <?= lang('Backup.btn_upload') ?></button>
                    </div>

                    <a href="<?= $download_photos_link ?>" class="btn btn-info"><i class="fa fa-download"></i> <?= lang('Backup.download_photos') ?></a>
                </div>
            </div>
        </div>
    </div>

    

</div>

<script type="text/javascript">
$(document).ready(function() {

    // Handle CSRF token
    var csrfToken   = $('#csrf_token').val();
    // Set the CSRF token in all AJAX requests globally
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });

    // JSON Upload
    $('.btn_backup_json').click(function() {
        // Clear result and show loading spinner
        $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

        var backup_json = $('#backup_json').prop('files')[0];
        // Check if file is selected
        if (!backup_json) {
            $('#result').html('<div class="alert alert-danger">Please select a JSON file before uploading.</div>');
            return;
        }

        // Ensure it's a JSON file
        if (backup_json.type !== "application/json" && !backup_json.name.endsWith(".json")) {
            $('#result').html('<div class="alert alert-danger">Invalid file type. Only JSON files are allowed.</div>');
            return;
        }

        var form_data   = new FormData();
        form_data.append('backup_json', backup_json);
        $.ajax({
            url: '<?= base_url('backup/save-json') ?>',
            type: 'POST',
            data: form_data,
            headers: {'X-CSRF-TOKEN': csrfToken },
            contentType: false,
            processData: false,
            success: function(response) {
                $('#result').html(response.html);
                $('#backup_json').val(''); 
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (csrfToken) {
                    $('#csrf_token').val(csrfToken);
                }
            },
            error: function(xhr, status, error) {
                $('#result').html('<div class="alert alert-danger">Error: ' + xhr.responseText + '</div>');
            }
        });
    });

    // Photo Upload
    $('.btn_backup_photo').click(function() {
        $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

        var backup_photo = $('#backup_photo').prop('files')[0];
        if (!backup_photo) {
            $('#result').html('<div class="alert alert-danger">Please select a ZIP file before uploading.</div>');
            return;
        }

        if (backup_photo.type !== "application/zip" && !backup_photo.name.endsWith(".zip")) {
            $('#result').html('<div class="alert alert-danger">Invalid file type. Only ZIP files are allowed.</div>');
            return;
        }

        var form_data   = new FormData();
        form_data.append('backup_photo', backup_photo);
        $.ajax({
            url: '<?= base_url('backup/upload-photo') ?>',
            type: 'POST',
            data: form_data,
            headers: {'X-CSRF-TOKEN': csrfToken },
            contentType: false,
            processData: false,
            success: function(response) {
                $('#result').html(response.html);
                $('#backup_photo').val(''); 
                csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                if (csrfToken) {
                    $('#csrf_token').val(csrfToken);
                }
            },
            error: function(xhr, status, error) {
                $('#result').html('<div class="alert alert-danger">Error: ' + xhr.responseText + '</div>');
            }
        });
    });


});
</script>