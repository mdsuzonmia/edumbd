<?php 
$student_name = $student_name ?? 'N/A';
$school_name = $school_name ?? 'N/A';
$class_name = $class_name ?? 'N/A';
$session_name = $session_name ?? 'N/A';
$transcript_token = $transcript_token ?? '';
$public_link = base_url('examination/transcript/' . $transcript_token);
$download_pdf_url = base_url('examination/student/transcript/download-pdf/' . $transcript_token);
?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-text"></i> My Transcript</h3>
    </div>
    <div class="col-sm-6 text-end pt-0">
        <a href="<?= base_url('examination/student/transcript') ?>" class="btn btn-secondary">
            <i class="fa fa-list"></i> Back to Transcripts
        </a>
        <?php if ($transcript_token): ?>
            <a href="<?= $download_pdf_url ?>" class="btn btn-danger">
                <i class="bi bi-filetype-pdf"></i> Download PDF
            </a>
            <button type="button" class="btn btn-info" onclick="copyShareLink()">
                <i class="bi bi-share"></i> Share Link
            </button>
        <?php endif; ?>
        <button type="button" class="btn btn-success" onclick="printTranscript()">
            <i class="bi bi-printer"></i> Print Transcript
        </button>
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                
                <!-- Transcript Content -->
                
                <?php if (!empty($rendered_content)): ?>
                    <div class="transcript-body" style="padding: 20px;">
                        <?php if ($template_style): ?>
                            <style type="text/css">
                                <?= $template_style ?>
                            </style>
                        <?php endif; ?>
                        <?= $rendered_content ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i> Transcript not generated yet. Please contact your school administrator.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function printTranscript() {
    var transcriptBody = document.querySelector('.transcript-body');
    var clone = transcriptBody.cloneNode(true);
    var styleTags = clone.querySelectorAll('style');
    styleTags.forEach(function(tag) { tag.remove(); });
    
    var printWindow = window.open('', '_blank');
    printWindow.document.write('<html><head><title>Print Transcript</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; padding: 20px; margin: 0; }');
    <?php if ($template_style): ?>
    printWindow.document.write('<?= addslashes(str_replace(["\r\n", "\r", "\n"], ' ', $template_style)) ?>');
    <?php endif; ?>
    printWindow.document.write('</style>');
    printWindow.document.write('</head><body>');
    printWindow.document.write(clone.innerHTML);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    printWindow.focus();
    setTimeout(function() { printWindow.print(); }, 500);
}

<?php if ($transcript_token): ?>
function copyShareLink() {
    var link = '<?= $public_link ?>';
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(link).then(function() {
            alert('Share link copied to clipboard!');
        }).catch(function() {
            fallbackCopyLink(link);
        });
    } else {
        fallbackCopyLink(link);
    }
}

function fallbackCopyLink(link) {
    var textarea = document.createElement('textarea');
    textarea.value = link;
    textarea.style.position = 'fixed';
    textarea.style.left = '-9999px';
    document.body.appendChild(textarea);
    textarea.select();
    try {
        document.execCommand('copy');
        alert('Share link copied to clipboard!');
    } catch (e) {
        prompt('Copy this link to share:', link);
    }
    document.body.removeChild(textarea);
}
<?php endif; ?>
</script>