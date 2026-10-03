<?php 
// Get CSRF token for AJAX calls
$csrf_token = csrf_hash();
$history_url = site_url('school-owner/students/bulk/import/history');
?>

<div class="right_col" role="main">
    <!-- Page Header -->
    <div class="row">
        <div class="col-md-8 col-sm-12">
            <h3><i class="fa fa-upload"></i> <?= lang('Bulkstudent.page_title'); ?></h3>
        </div>
        <div class="col-md-4 col-sm-12 text-end">
            <a href="<?= $history_url ?>" class="btn btn-success btn-sm">
                <i class="fa fa-history"></i> View Import History
            </a>
        </div>
    </div>

    <div class="clearfix"></div>

    <?= get_system_message(); ?>

    <!-- Step Indicator -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="step-indicator">
                <div class="step active" id="step-ind-1">
                    <div class="step-number">1</div>
                    <div class="step-label">Download Sample</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step-ind-2">
                    <div class="step-number">2</div>
                    <div class="step-label">Upload File</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step-ind-3">
                    <div class="step-number">3</div>
                    <div class="step-label">Preview & Validate</div>
                </div>
                <div class="step-line"></div>
                <div class="step" id="step-ind-4">
                    <div class="step-number">4</div>
                    <div class="step-label">Import</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 1: Select School & Download Sample -->
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-download"></i> Step 1: Download Sample File</h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <p class="text-muted mb-3">Select a school first, then download the sample CSV or JSON template. The sample file includes all required columns based on your school's settings.</p>
                    <p class="text-muted mb-3">Custom fields are included automatically using the header format <code>custom_field_key</code>.</p>
                    
                    <?= form_open(site_url('school-owner/students/bulk/import'), [
                        'class'   => 'form-horizontal form-label-left', 
                        'id'      => 'school_select_form', 
                        'method'  => 'get', 
                        'data-parsley-validate' => '' 
                    ]); ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="col-form-label col-md-4" for="school_id"><?= lang('Mark.field_school') ?> *</label>
                                <div class="col-md-8">
                                    <select name="school_id" id="school_id" class="form-control" required>
                                        <option value=""><?= lang('Mark.field_select_school') ?></option>
                                        <?php foreach ($school_list as $id => $name): ?>
                                            <option value="<?= $id ?>" <?= (isset($_GET['school_id']) && $_GET['school_id'] == $id) ? 'selected' : '' ?>><?= $name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-12">
                            <a href="javascript:void(0)" id="download_csv_btn" class="btn btn-info btn-sm" onclick="downloadSample('csv')">
                                <i class="fa fa-file-excel-o"></i> Download Sample CSV
                            </a>
                            <a href="javascript:void(0)" id="download_json_btn" class="btn btn-warning btn-sm" onclick="downloadSample('json')">
                                <i class="fa fa-file-code-o"></i> Download Sample JSON
                            </a>
                        </div>
                    </div>

                    <?= form_close(); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Upload File -->
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-upload"></i> Step 2: Upload Your File</h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <p class="text-muted mb-2">Upload your CSV or JSON file following the sample format. The first row must be headers matching the sample.</p>
                    <p class="text-muted mb-3"><i class="fa fa-info-circle"></i> Supported formats: <strong>CSV</strong> and <strong>JSON</strong>. Maximum file size: <?= ini_get('upload_max_filesize') ?>.</p>

                    <form id="upload_form" enctype="multipart/form-data">
                        <input type="hidden" name="school_id" id="upload_school_id" value="<?= isset($_GET['school_id']) ? (int)$_GET['school_id'] : '' ?>">
                        <input type="hidden" name="<?= csrf_token() ?>" value="<?= $csrf_token ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fa fa-file"></i></span>
                                    </div>
                                    <input type="file" name="bulk_file" id="bulk_file" class="form-control" accept=".csv,.json" required>
                                    <div class="input-group-append">
                                        <button class="btn btn-success" type="submit" id="upload_btn">
                                            <i class="fa fa-upload"></i> Upload & Preview
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Preview & Validate -->
    <div class="row" id="preview_section" style="display: none;">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-eye"></i> Step 3: Preview & Validate Data</h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content">
                    <div id="preview_container">
                        <!-- Preview table will be loaded here via AJAX -->
                        <div class="text-center">
                            <i class="fa fa-spinner fa-spin fa-3x"></i>
                            <p>Loading preview...</p>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-md-12 text-right">
                            <button id="validate_btn" class="btn btn-warning" onclick="validateData()">
                                <i class="fa fa-check-circle"></i> Validate Data
                            </button>
                            <button id="import_btn" class="btn btn-success" onclick="importData()" style="display: none;">
                                <i class="fa fa-save"></i> Import All Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Validation Results -->
    <div class="row" id="validation_section" style="display: none;">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-list"></i> Validation Results</h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content" id="validation_container">
                    <!-- Validation messages will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Import Results -->
    <div class="row" id="import_result_section" style="display: none;">
        <div class="col-md-12 col-sm-12">
            <div class="x_panel">
                <div class="x_title">
                    <h4><i class="fa fa-check"></i> Import Result</h4>
                    <div class="clearfix"></div>
                </div>
                <div class="x_content" id="import_result_container">
                    <!-- Import result will be displayed here -->
                </div>
            </div>
        </div>
    </div>

    
</div>

<style>
    /* Step Indicator */
    .step-indicator {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px 0;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 1px 4px rgba(0,0,0,0.08);
    }
    .step-indicator .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        min-width: 100px;
    }
    .step-indicator .step-number {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e0e0e0;
        color: #999;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 14px;
        margin-bottom: 6px;
        transition: all 0.3s;
    }
    .step-indicator .step.active .step-number {
        background: #337ab7;
        color: #fff;
    }
    .step-indicator .step.completed .step-number {
        background: #5cb85c;
        color: #fff;
    }
    .step-indicator .step-label {
        font-size: 12px;
        color: #999;
        font-weight: 500;
        text-align: center;
    }
    .step-indicator .step.active .step-label {
        color: #337ab7;
        font-weight: 600;
    }
    .step-indicator .step.completed .step-label {
        color: #5cb85c;
    }
    .step-indicator .step-line {
        flex: 1;
        height: 2px;
        background: #e0e0e0;
        margin: 0 8px;
        margin-bottom: 20px;
        max-width: 80px;
    }

    /* Enhanced x_panel */
    .x_panel {
        border: 1px solid #e6e9ed;
        border-radius: 6px;
        margin-bottom: 20px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    }
    .x_title {
        border-bottom: 1px solid #e6e9ed;
        padding: 10px 15px;
        background: #fafafa;
        border-radius: 6px 6px 0 0;
    }
    .x_title h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 600;
        color: #333;
    }
    .x_content {
        padding: 15px;
    }

    /* Upload area enhancement */
    #upload_form .input-group {
        border: 2px dashed #d9d9d9;
        border-radius: 6px;
        padding: 4px;
        transition: border-color 0.3s;
    }
    #upload_form .input-group:hover {
        border-color: #337ab7;
    }
    #upload_form .input-group:focus-within {
        border-color: #337ab7;
    }

    /* Button enhancements */
    .btn {
        border-radius: 4px;
        font-weight: 500;
        transition: all 0.2s;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    }

    /* History link button */
    .btn-outline-primary {
        border-color: #337ab7;
        color: #337ab7;
    }
    .btn-outline-primary:hover {
        background: #337ab7;
        color: #fff;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .step-indicator {
            flex-wrap: wrap;
            gap: 8px;
        }
        .step-indicator .step-line {
            display: none;
        }
        .step-indicator .step {
            min-width: 70px;
        }
    }
</style>

<script type="text/javascript">
    var currentSchoolId = <?= isset($_GET['school_id']) ? (int)$_GET['school_id'] : 0 ?>;
    var bulkCsrfToken = '<?= $csrf_token ?>';

    function updateBulkCsrfToken(xhr) {
        var token = xhr.getResponseHeader('X-CSRF-TOKEN');
        if (token) {
            bulkCsrfToken = token;
            $('#upload_form input[name="<?= csrf_token() ?>"]').val(token);
        }
    }

    function getAjaxErrorMessage(xhr, fallback) {
        if (xhr.responseJSON) {
            return xhr.responseJSON.html || xhr.responseJSON.message || fallback;
        }
        return xhr.responseText || fallback;
    }

    $(document).ready(function() {
        // School select change
        $('#school_id').on('change', function() {
            var sid = $(this).val();
            currentSchoolId = sid;
            $('#upload_school_id').val(sid);
        });

        // Upload form submit
        $('#upload_form').on('submit', function(e) {
            e.preventDefault();
            if (!currentSchoolId) {
                alert('Please select a school first.');
                return;
            }
            uploadFile();
        });
    });

    function downloadSample(type) {
        var sid = $('#school_id').val();
        if (!sid) {
            alert('Please select a school first.');
            return;
        }
        var url = '<?= site_url('school-owner/students/bulk/sample-') ?>' + type + '?school_id=' + sid;
        window.open(url, '_blank');
    }

    function uploadFile() {
        var formData = new FormData($('#upload_form')[0]);
        
        $('#upload_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Uploading...');
        $('#preview_section').hide();
        $('#validation_section').hide();
        $('#import_result_section').hide();

        $.ajax({
            url: '<?= site_url('school-owner/students/bulk/upload') ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response, status, xhr) {
                updateBulkCsrfToken(xhr);
                $('#upload_btn').prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Preview');
                
                if (response.status) {
                    $('#preview_container').html(response.html);
                    $('#preview_section').show();
                    $('#validate_btn').show();
                    $('#import_btn').hide();
                    updateStepIndicator(3);
                } else {
                    alert(response.html);
                }
            },
            error: function(xhr) {
                updateBulkCsrfToken(xhr);
                $('#upload_btn').prop('disabled', false).html('<i class="fa fa-upload"></i> Upload & Preview');
                alert(getAjaxErrorMessage(xhr, 'Upload failed. Please try again.'));
            }
        });
    }

    function validateData() {
        $('#validate_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Validating...');

        $.ajax({
            url: '<?= site_url('school-owner/students/bulk/validate') ?>',
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': bulkCsrfToken
            },
            success: function(response, status, xhr) {
                updateBulkCsrfToken(xhr);
                $('#validate_btn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Validate Data');
                
                $('#validation_section').show();
                $('#validation_container').html(response.html);
                $('#preview_container').html(response.html);

                if (response.status) {
                    $('#import_btn').show();
                    $('#validate_btn').hide();
                    updateStepIndicator(4);
                } else {
                    $('#import_btn').hide();
                }
            },
            error: function(xhr) {
                updateBulkCsrfToken(xhr);
                $('#validate_btn').prop('disabled', false).html('<i class="fa fa-check-circle"></i> Validate Data');
                alert(getAjaxErrorMessage(xhr, 'Validation failed. Please try again.'));
            }
        });
    }

    function importData() {
        if (!confirm('Are you sure you want to import all records? This action cannot be undone.')) {
            return;
        }

        $('#import_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Importing...');

        $.ajax({
            url: '<?= site_url('school-owner/students/bulk/import/process') ?>',
            type: 'POST',
            dataType: 'json',
            headers: {
                'X-CSRF-TOKEN': bulkCsrfToken
            },
            success: function(response, status, xhr) {
                updateBulkCsrfToken(xhr);
                $('#import_btn').prop('disabled', false).html('<i class="fa fa-save"></i> Import All Data');
                
                $('#import_result_section').show();
                $('#import_result_container').html(response.html);
                $('#preview_section').hide();
                $('#validation_section').hide();
                updateStepIndicator(4);
            },
            error: function(xhr) {
                updateBulkCsrfToken(xhr);
                $('#import_btn').prop('disabled', false).html('<i class="fa fa-save"></i> Import All Data');
                alert(getAjaxErrorMessage(xhr, 'Import failed. Please try again.'));
            }
        });
    }

    function updateStepIndicator(step) {
        for (var i = 1; i <= 4; i++) {
            var el = $('#step-ind-' + i);
            el.removeClass('active completed');
            if (i < step) {
                el.addClass('completed');
            } else if (i === step) {
                el.addClass('active');
            }
        }
    }
</script>
