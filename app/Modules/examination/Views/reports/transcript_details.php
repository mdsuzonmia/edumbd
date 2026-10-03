<?php 
$rendered_content = $rendered_content ?? '';
$template_style = $template_style ?? '';
$student_id = $student_id ?? 0;
$school_id = $school_id ?? 0;
$year_id = $year_id ?? 0;
$class_id = $class_id ?? 0;
$transcript_token = $transcript_token ?? '';
$final_result = $final_result ?? null;
$not_found = $not_found ?? false;

$template_bg = $template_bg ?? '';
$orientation = $orientation ?? 'portrait';
?>

<style type="text/css">
    <?php if ($orientation === 'portrait'): ?>
    .result-body { 
        width: 210mm; 
        min-height: 297mm; 
        margin: 0 auto; 
        padding: 10mm 15mm; 
        box-sizing: border-box; 
    }
    
    <?php elseif ($orientation === 'landscape'): ?>
    .result-body { 
        width: 348mm; 
        min-height: 210mm; 
        margin: 0 auto; 
        padding: 10mm 15mm !important; 
        box-sizing: border-box; 
    }
    .result-inner-wrap{padding: 20px;}
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
        <h3 class="text-secondary mb-0"><i class="bi bi-file-earmark-text"></i> <?= lang('Reports.heading_transcript') ?? 'Official Academic Transcript' ?></h3>
    </div>

    <div class="col-sm-6 text-end pt-0">
        <!-- PDF download -->
        <a href="<?= base_url('examination/reports/transcript/download-pdf/' . $transcript_token) ?>" class="btn btn-primary" target="_blank"><i class="bi bi-file-earmark-pdf"></i> PDF Download</a>
        <button type="button" class="btn btn-success" onclick="printTranscript()"><i class="bi bi-printer"></i> Print Transcript</button>
        <a href="<?= base_url('examination/reports/transcript?school_id=' . $school_id . '&year_id=' . $year_id . '&class_id=' . $class_id) ?>" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to List</a>
    </div>
</div>

<?php if ($not_found && !$rendered_content): ?>
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="alert alert-warning">
                <i class="bi bi-exclamation-triangle"></i> Transcript not generated for this student. Please generate final results using the Aggregate module first.
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if ($rendered_content): ?>
    <div class="row">
        <div class="col-md-12">
            
            <div class="card">
                <div class="card-body result-body" style="padding: 20px;">
                    <div class="result-inner-wrap">
                    <?php if ($template_style): ?>
                        <style type="text/css">
                            <?= $template_style ?>
                            .subject-academic-table th, .subject-academic-table td {
                                text-align: center;
                                vertical-align: middle;
                            }
                            .subject-academic-table td.subject-name{ text-align: left;}
                        </style>
                    <?php endif; ?>
                    <?= $rendered_content ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
        function printTranscript() {
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
            printWindow.document.write('    padding: 10mm 8mm;');
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
    </script>
<?php endif; ?>
