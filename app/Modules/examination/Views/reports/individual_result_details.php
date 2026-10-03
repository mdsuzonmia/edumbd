<?php 
$school_name = $school_name ?? '';
$school_address = $school_address ?? '';
$school_phone = $school_phone ?? '';
$school_email = $school_email ?? '';
$school_logo = $school_logo ?? '';
$exam_name = $exam_name ?? '';
$class_name = $class_name ?? '';
$session_name = $session_name ?? '';
$student = $student ?? null;
$result = $result ?? null;
$subject_marks = $subject_marks ?? [];
$rendered_content = $rendered_content ?? '';
$template_type = $template_type ?? '';
$template_style = $template_style ?? '';
$template_bg = $template_bg ?? '';
$orientation = $orientation ?? 'portrait';
$token = $token ?? '';
?>

<style type="text/css">
    <?php if ($orientation === 'portrait'): ?>
    .result-body { 
        width: 228mm; 
        min-height: 297mm; 
        margin: 0 auto; 
        padding: 10mm 15mm; 
        box-sizing: border-box; 
    }
    <?php elseif ($orientation === 'landscape'): ?>
    .result-body { 
        width: 297mm; 
        min-height: 210mm; 
        margin: 0 auto; 
        padding: 10mm 15mm; 
        box-sizing: border-box; 
    }
    <?php endif; ?>
    <?php if ($template_bg): ?>
    .result-body {
        background-image: url('<?= $template_bg ?>');
        background-size: cover;
        background-position: center;
        background-repeat: no-repeat;
    }
    <?php endif; ?>
    <?= $template_style ?>
</style>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-person"></i> Individual Result Details</h3>
    </div>
    <div class="col-sm-6 text-end pt-0">
        <a href="<?= base_url('examination/reports/individual-result/download-pdf/' . $token) ?>" class="btn btn-primary" target="_blank">
            <i class="bi bi-file-earmark-pdf"></i> PDF Download
        </a>
        <button type="button" class="btn btn-success" onclick="printResult()"><i class="bi bi-printer"></i> Print Result</button>
        <button type="button" class="btn btn-info" onclick="sendEmailModal()"><i class="bi bi-envelope"></i> Send Email</button>
        <a href="<?= base_url('examination/reports/individual-result') ?>" class="btn btn-secondary">
            <i class="fa fa-list"></i> Back to List
        </a>
    </div>
</div>

<?php if ($rendered_content): ?>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body result-body">
                    <?= $rendered_content ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function printResult() {
            var resultBody = document.querySelector('.result-body');
            var contentHTML = resultBody.innerHTML;
            
            var printWindow = window.open('', '_blank');
            printWindow.document.write('<!DOCTYPE html><html><head><title>Print Result</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('* { margin: 0; padding: 0; box-sizing: border-box; }');
            printWindow.document.write('body {');
            <?php if ($orientation === 'portrait'): ?>
            printWindow.document.write('    width: 210mm;');
            printWindow.document.write('    min-height: 297mm;');
            <?php elseif ($orientation === 'landscape'): ?>
            printWindow.document.write('    width: 297mm;');
            printWindow.document.write('    min-height: 210mm;');
            <?php endif; ?>
            printWindow.document.write('    padding: 10mm 15mm;');
            printWindow.document.write('    font-family: Arial, sans-serif;');
            <?php if ($template_bg): ?>
            printWindow.document.write('    background-image: url("<?= $template_bg ?>");');
            printWindow.document.write('    background-size: cover;');
            printWindow.document.write('    background-position: center;');
            printWindow.document.write('    background-repeat: no-repeat;');
            <?php endif; ?>
            printWindow.document.write('}');
            <?php if ($template_style): ?>
            printWindow.document.write('<?= addslashes(str_replace(["\r\n", "\r", "\n"], ' ', $template_style)) ?>');
            <?php endif; ?>
            printWindow.document.write('</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(contentHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            setTimeout(function() { printWindow.print(); }, 500);
        }

        function sendEmailModal() {
            // Get student and guardian email addresses
            var studentEmail = '<?= $student->phone ?? '' ?>'; // This might be email, adjust as needed
            var guardianEmail = '<?= $guardian_name ?? '' ?>'; // You may need to pass guardian email from controller
            
            // Create modal HTML
            var modalHTML = `
                <div class="modal fade" id="sendEmailModal" tabindex="-1" role="dialog">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title"><i class="bi bi-envelope"></i> Send Result via Email</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <form id="sendEmailForm">
                                    <div class="mb-3">
                                        <label for="recipientEmail" class="form-label">Recipient Email</label>
                                        <input type="email" class="form-control" id="recipientEmail" required placeholder="Enter email address">
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailSubject" class="form-label">Subject</label>
                                        <input type="text" class="form-control" id="emailSubject" value="Result Card - <?= $student_name ?? 'Student' ?> - <?= $exam_name ?? '' ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="emailMessage" class="form-label">Message</label>
                                        <textarea class="form-control" id="emailMessage" rows="3">Dear Parent/Student,

Please find attached the result card for <?= $student_name ?? 'your child' ?>.

Best regards,
School Administration</textarea>
                                    </div>
                                </form>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                <button type="button" class="btn btn-primary" onclick="sendEmail()">
                                    <i class="bi bi-send"></i> Send Email
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            $('.modal.show').remove();
            $('body').append(modalHTML);
            $('#sendEmailModal').modal('show');
            
            // Store token for sending
            $('#sendEmailModal').data('token', '<?= $token ?>');
        }

        function sendEmail() {
            var token = $('#sendEmailModal').data('token');
            var email = $('#recipientEmail').val();
            var subject = $('#emailSubject').val();
            var message = $('#emailMessage').val();
            
            if (!email) {
                alert('Please enter recipient email address');
                return;
            }
            
            // Show loading state
            $('.modal-footer button').prop('disabled', true);
            $('.modal-footer button:last-child').html('<i class="bi bi-hourglass-split"></i> Sending...');
            
            // Send AJAX request
            $.ajax({
                url: '<?= base_url('examination/reports/individual-result/send-email') ?>',
                type: 'POST',
                data: {
                    token: token,
                    email: email,
                    subject: subject,
                    message: message,
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                },
                success: function(response) {
                    $('#sendEmailModal').modal('hide');
                    if (response.success) {
                        alert('Email sent successfully!');
                    } else {
                        alert('Failed to send email: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    $('#sendEmailModal').modal('hide');
                    alert('Error sending email: ' + error);
                },
                complete: function() {
                    // Remove modal
                    $('#sendEmailModal').remove();
                }
            });
        }
    </script>
<?php else: ?>
    <div class="row">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> No template found for this school. Please configure a result template first.
            </div>
        </div>
    </div>
<?php endif; ?>