<?php 
// Get school id from session
$school_id      = session()->get('school_id');

$certificate_bg           = esc(get_school_value($school_id, 'certificate_bg', true));
if($certificate_bg){
    $certificate_bg_path = base_url('uploads/' . esc($certificate_bg));
}else{
    $certificate_bg_path = base_url('uploads/photo.png');
}

?>

<!-- page content -->
<div class="right_col" role="main">

    <div class="row">
        <div class="col-md-6 col-sm-6 ">
            <h3><i class="fa fa-certificate"></i> <?= lang('Certificate.page_title_certificate'); ?></h3>
        </div>

        <div class="col-md-6 col-sm-6 text-right pt-2">
            <a href="<?= base_url('/certificate') ?>" class="pull-right btn btn-sm btn-primary"><i class="fa fa-backward"></i> <?= lang('Certificate.back_to_certificate') ?></a>
            <button class="pull-right printing btn btn-sm btn-info"><i class="fa fa-print"></i> <?= lang('Common.btn_print') ?></button>
            <button class="pull-right download_pdf btn btn-sm btn-info"><i class="fa fa-file-pdf-o"></i> <?= lang('Common.btn_pdf') ?></button>
        </div>
    </div>


    <div id="print_area" >

        <link href="https://fonts.googleapis.com/css?family=UnifrakturCook:700" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css?family=Pinyon+Script" rel="stylesheet">
        <style type="text/css">
            .page{
                width: 297mm;
                min-height: 210mm;
                padding: 10px;

                background: url('<?php echo $certificate_bg_path; ?>');
                background-repeat: no-repeat;
                background-position: center top;
                background-size: 100% 100%;
            }

            .certificate-area{padding: 120px 100px 10px 100px;}
            .certificate-area .print-header h4 {font-family: 'UnifrakturCook', cursive;font-size: 300%;}
            .certificate-area .info h3{font-family: 'UnifrakturCook', cursive;font-size: 350%;}
            .certificate-area .text {font-family: 'Pinyon Script', cursive;font-size: 200%;}
        </style>

    <?php if($student_data){ 
        foreach($student_data as $key => $student){
            $data['student_data']   = $student;

            echo '<div class="page">';
            echo view('certificate/certificate', $data);
            echo '</div>';

        } // End foreach loop
    }else{ ?>
        <div class="row">
            <div class="col-sm-12 card">
                <div class="body p-3">
                <h3 class="text-center red"><i class="fa fa-meh-o"></i> <?= lang('Result.student_not_found'); ?></h3>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    
</div>
<!-- /page content -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>
<script type="text/javascript">
$(document).ready(function() {

    // Get Printing
    $(".printing").click(function() {
        var printContents = document.getElementById("print_area").innerHTML;
        var originalContents = document.body.innerHTML;
         // Add a style tag for landscape printing
        var landscapeStyle = `
            <style>
                @media print {
                    @page { size: A4 landscape; margin: 0mm; padding: 0mm;display: block; }
                    body { margin: 0mm auto 0mm auto; padding: 0mm; box-shadow: 0;}
                }
            </style>
        `;
        document.body.innerHTML = landscapeStyle + printContents;
        window.print();
        window.location.reload();
    });

    // PDF download
    $(".download_pdf").click(function() {
        const element = document.getElementById('print_area');
        // Options for PDF layout
        const options = {
            margin:       0,
            filename:     'certificate_<?= date('Y-m-d') ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'mm', format: [297, 210.35], orientation: 'landscape' }
        };
        // Convert the element to PDF
        html2pdf().set(options).from(element).save();

    });

});
</script>
