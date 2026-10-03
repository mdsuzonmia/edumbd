<?php

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
                    <p>Dear [Student's Name],</p>

                    <p>Thank you for submitting your application to [School/Program Name]. We have successfully received your submission and appreciate the time and effort you put into it.
                    Our admissions team will carefully review your application, and we will be in touch with you regarding the next steps soon. Should you have any questions in the meantime, feel free to contact us at [contact email or phone number].
                    We’re excited about the possibility of you joining our community and wish you the best of luck!</p>

                    <p>Warm regards,</p>
                    <p>[Your School/Program Name]</p>
                    <p>[Contact Information]</p>
                    
                    </div>

                    <div class="x_title">
                        <h2><?= lang('Student.admitcard_title'); ?></h2>
                        <div class="clearfix"></div>
                    </div>

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

                </div>
            </div>
        </div>
    </div>
</div>

 