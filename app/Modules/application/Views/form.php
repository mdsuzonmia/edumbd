<?php
$guardian_info          = esc(get_setting_value('guardian_info'));
$student_account        = esc(get_setting_value('student_account'));
$academic_shift         = esc(get_setting_value('academic_shift'));
$academic_department    = esc(get_setting_value('academic_department'));
$academic_category      = esc(get_setting_value('academic_category'));
$extra_skill            = esc(get_setting_value('extra_skill'));

use App\Models\FieldbuilderModel;
$field_model = new FieldbuilderModel();
$section_id = get_item('id', 'fields_section', 'title', 'Student');
$fields = $field_model->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

if($guardian_info){
    $guardian_section_id  = get_item('id', 'fields_section', 'title', 'Guardian');
    $guardian_fields      = $field_model->where('section', $guardian_section_id)->orderBy('field_order', 'ASC')->findAll();
}

?>
<div class="application-form">
    <div class="container-public">
    
    <?= form_open('application/submit', [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'student_form', 
        'method'  => 'post', 
        'enctype' => 'multipart/form-data',
        'data-parsley-validate'=>'' 
        ]); 
    ?>
    
    <div class="row">
        <div class="col-sm-12 col-md-12">
            <div class="x_panel">

                <?php if(isset($validation)): ?>
                    <div style="color: red;">
                        <?= $validation->listErrors(); ?>
                    </div>
                <?php endif; ?>

                <div class="x_title">
                    <h2><?= lang('Student.cap_profile'); ?></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <?= view('application/profile') ?>
                </div>

                <div class="x_title">
                    <h2><?= lang('Student.cap_academic'); ?></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <?= view('application/academic') ?>
                </div>

                <?php if($guardian_info): ?>
                <div class="x_title">
                    <h2><?= lang('Student.cap_guardian'); ?></h2>
                    <div class="clearfix"></div>
                </div>

                <div class="x_content">
                    <?= view('application/guardian') ?>
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-success add"><?= lang('Student.btn_submit') ?></button>
                <button type="button" class="btn btn-success preview"><?= lang('Student.btn_preview') ?></button>
                <button type="button" class="btn btn-success back" style="display: none;" ><?= lang('Student.btn_back') ?></button>

                <?php if(!$is_edit): ?>
                <button class="btn btn-primary reset"><?= lang('Student.btn_reset') ?></button>
                <?php endif; ?>

            </div>
        </div>


    </div>

    <?= form_close() ?>

    
    </div>
</div>


<!-- Javascript functions	-->
<script>
    function hideshow(){
        var password = document.getElementById("password");
        var slash    = document.getElementById("slash");
        var eye      = document.getElementById("eye");
        
        if(password.type === 'password'){
            password.type       = "text";
            slash.style.display = "block";
            eye.style.display   = "none";
        }
        else{
            password.type       = "password";
            slash.style.display = "none";
            eye.style.display   = "block";
        }

    }

    function readURL(input, id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                jQuery('#preview_'+id).attr('src', e.target.result);
                jQuery('#preview_profile_image_'+id).attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Preview
    $(".preview").click(function() {
        // Get form data
        var student_name  = $("#student_name").val();
        var student_phone = $("#student_phone").val();
        var student_photo = $('#student_photo')[0].files[0];
        
        $("#profile_preview_name").text(student_name);
        $("#profile_preview_phone").text(student_phone);

        // Handle image preview
        if (student_photo) {
            const reader = new FileReader();
            reader.onload = function (e) {
                $('#preview_profile_image_1').attr('src', e.target.result).show();
            };
            reader.readAsDataURL(student_photo);
        } else {
            $('#preview_profile_image_1').hide();
        }

        <?php foreach ($fields as $key => $field) { ?>
            $("#profile_preview_<?= $field->id ?>").text($("#field_<?= $field->id ?>").val());
        <?php } ?>

        // Set Academic Data
        if ($("#field_year").val()) {
            $("#academic_preview_year").text($('#field_year option:selected').text());
        }

        if ($("#field_class").val()) {
            $("#academic_preview_class").text($('#field_class option:selected').text());
        }

        // Get the selected suject
        const selected_subject = $('#field_subject option:selected').map(function () {
                    return $(this).text();
                }).get(); // Convert to array
        $("#academic_preview_subject").text(selected_subject.join(', '));

        <?php if($academic_category): ?>
            if ($("#field_category").val()) {
                $("#profile_preview_category").text($('#field_category option:selected').text());
            }
        <?php endif; ?>

        <?php if($academic_shift): ?>
            if ($("#field_shift").val()) {
                $("#profile_preview_shift").text($('#field_shift option:selected').text());
            }
        <?php endif; ?>

        <?php if($academic_department): ?>
            if ($("#field_department").val()) {
                $("#profile_preview_department").text($('#field_department option:selected').text());
            }
        <?php endif; ?>

        <?php if($extra_skill): ?>
            const selected_skill = $('#field_skill option:selected').map(function () {
                    return $(this).text();
                }).get(); // Convert to array
            $("#profile_preview_skill").text(selected_skill.join(', '));
        <?php endif; ?>

        <?php if($student_account): ?>
            $("#academic_preview_username").text($('#student_username').val());
            $("#academic_preview_email").text($('#student_email').val());
        <?php endif; ?>

        // Guardian data
        <?php if($guardian_info): ?>
            $("#guardian_preview_name").text($('#guardian_name').val());
            $("#guardian_preview_email").text($('#guardian_email').val());
            $("#guardian_preview_phone").text($('#guardian_phone').val());

            <?php foreach ($guardian_fields as $key => $guardian_field) { ?>
                $("#guardian_preview_<?= $guardian_field->id ?>").text($("#field_<?= $guardian_field->id ?>").val());
            <?php } ?>

            var guardian_photo = $('#guardian_photo')[0].files[0];
            // Handle image preview
            if (guardian_photo) {
                const guardian_reader = new FileReader();
                guardian_reader.onload = function (e) {
                    $('#preview_profile_image_2').attr('src', e.target.result).show();
                };
                guardian_reader.readAsDataURL(guardian_photo);
            } else {
                $('#preview_profile_image_2').hide();
            }
        <?php endif; ?>

        // Show the preview section
        $("#profile_form").slideUp();
        $("#academic_form").slideUp();
        $("#guardian_form").slideUp();
        $("#profile_preview").slideDown();
        $("#academic_preview").slideDown();
        $("#guardian_preview").slideDown();
        $(".preview").hide();
        $(".reset").hide();
        $(".back").show();
        $('html, body').animate({ scrollTop: 0 }, 600);
    });

    // Back
    $(".back").click(function() {
        $("#profile_form").slideDown();
        $("#academic_form").slideDown();
        $("#guardian_form").slideDown();
        $("#profile_preview").slideUp();
        $("#academic_preview").slideUp();
        $("#guardian_preview").slideUp();
        $(".preview").show();
        $(".reset").show();
        $(".back").hide();
    });

</script>

 