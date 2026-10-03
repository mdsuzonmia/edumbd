<?php

// Student Data
if($student_data){
    $student_id    = $student_data->id;
    $student_name  = $student_data->name;
    $photo         = $student_data->photo;
    $student_phone = $student_data->phone;
}else{
    $student_id    = '';
    $student_name  = '';
    $photo         = '';
    $student_phone = '';
}

if($photo){
    $photo_path = base_url('uploads/' . esc($photo));
}else{
    $photo_path = base_url('uploads/photo.png');
}

$school_name       = esc(get_setting_value('school_name'));
$school_address    = esc(get_setting_value('school_address'));
$email             = esc(get_setting_value('email'));
$phone             = esc(get_setting_value('phone'));
$website           = esc(get_setting_value('website'));
$logo              = esc(get_setting_value('logo'));
$thankyou_content  = esc(get_setting_value('application_thankyou'));

$school_contact = lang('Common.pint_email').':'.esc(get_setting_value('email')) .' '.lang('Common.pint_phone').' : '. esc(get_setting_value('phone'));

$placeHolders = [
    '[NAME]',
    '[SCHOOL_NAME]',
    '[SCHOOL_EMAIL]',
    '[SCHOOL_PHONE]',
    '[SCHOOL_CONTACT]',
];

$values = [
    '<b>'.$student_name.'</b>',
    '<b>'.$school_name.'</b>',
    '<b>'.$email.'</b>',
    '<b>'.$phone.'</b>',
    '<b>'.$school_contact.'</b>',
];
$rendered = str_replace($placeHolders, $values, $thankyou_content);
$rendered = nl2br($rendered);

?>
<div class="application-form">
    <div class="container-public">
        <div class="row">
            <div class="col-sm-12 col-md-12   ">
                <div class="x_panel">

                    <?php if(isset($validation)): ?>
                        <div style="color: red;">
                            <?= $validation->listErrors(); ?>
                        </div>
                    <?php endif; ?>

                    <div class="x_title">
                        <h2><?= lang('Student.thankyou_title'); ?></h2>
                        <div class="clearfix"></div>
                    </div>

                    <div class="x_content">
                    <?= $rendered ?>
                    </div>

                    
                    <?php if($student_data){ ?>
                    <div class="x_title-none">
                        <ul class="nav navbar-right panel_toolbox">
                        <li><button onclick="print_element('print_area')" class="edit btn btn-sm btn-info"><i class="fa fa-print"></i> Print</button></li>
                        <li><button class="download_pdf btn btn-sm btn-info"><i class="fa fa-file-pdf-o"></i> PDF</button></li>
                        </ul>
                        <div class="clearfix"></div>
                    </div>

                    <div id="print_area" class="x_content">
                    <?= view('application/admitcard') ?>
                    </div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.min.js"></script>

<script type="text/javascript">
$(document).ready(function() {

    // PDF download
    $(".download_pdf").click(function() {

        const element = document.getElementById('print_area');

        // Options for PDF layout
        const options = {
            margin:       0.5,
            filename:     '<?= lang('Student.student_profile_pdf_file_name') ?>.pdf',
            image:        { type: 'jpeg', quality: 0.98 },
            html2canvas:  { scale: 2 },
            jsPDF:        { unit: 'in', format: 'a4', orientation: 'portrait' }
        };
      
        // Convert the element to PDF
        html2pdf().set(options).from(element).save();

    });

});
</script>

 