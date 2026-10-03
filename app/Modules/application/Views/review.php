<?php 

use App\Models\FieldbuilderModel;
$field_model = new FieldbuilderModel();
$section_id = get_item('id', 'fields_section', 'title', 'Student');
$fields = $field_model->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

$guardian_section_id  = get_item('id', 'fields_section', 'title', 'Guardian');
$guardian_fields      = $field_model->where('section', $guardian_section_id)->orderBy('field_order', 'ASC')->findAll();

$class_roll             = esc(get_setting_value('class_roll'));
$academic_shift         = esc(get_setting_value('academic_shift'));
$academic_department    = esc(get_setting_value('academic_department'));
$academic_category      = esc(get_setting_value('academic_category'));
$extra_skill            = esc(get_setting_value('extra_skill'));
$student_phone_enabled  = esc(get_setting_value('student_phone'));

$school_name     = esc(get_setting_value('school_name'));
$school_address  = esc(get_setting_value('school_address'));
$email           = esc(get_setting_value('email'));
$phone           = esc(get_setting_value('phone'));
$website         = esc(get_setting_value('website'));
$logo            = esc(get_setting_value('logo'));

$guardian_info    = esc(get_setting_value('guardian_info'));

// Student Data
if($student_data){
    $student_id      = $student_data->id;
    $user_id         = $student_data->user_id;
    $registration_id = $student_data->registration_id;
    $student_name    = $student_data->name;
    $photo           = $student_data->photo;
    $student_phone   = $student_data->phone;
}else{
    $student_id    = '';
    $user_id       = '';
    $student_name  = '';
    $photo         = '';
    $student_phone = '';
    $registration_id = '';
}

if($photo){
    $photo_path = base_url('uploads/' . esc($photo));
}else{
    $photo_path = base_url('uploads/photo.png');
}

// Guardian Data
if($guardian_info):
    if($guardian_data){
        $guardian_id    = $guardian_data->id;
        $guardian_name  = $guardian_data->name;
        $guardian_photo = $guardian_data->photo;
        $guardian_phone = $guardian_data->phone;
        $guardian_email = $guardian_data->email;
    }else{
        $guardian_id    = '';
        $guardian_name  = '';
        $guardian_photo = '';
        $guardian_phone = '';
        $guardian_email = '';
    }


    if($guardian_photo){
        $guardian_photo_path = base_url('uploads/' . esc($guardian_photo));
    }else{
        $guardian_photo_path = base_url('uploads/photo.png');
    }
endif;



?>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">
    
        <div class="page-title">
            <div class="title_left">
            <h3><?= lang('Student.page_title_student_profile') ?></h3>
            </div>

            <div class="title_right text-right">
                <a href="<?= base_url('/application-list') ?>" class="btn btn-sm btn-info"><?= lang('Student.back_to_application') ?></a>
            </div>
        </div>
        <div class="clearfix"></div>
    <div class="row">
        
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                

                <div class="x_title-none">
                    <ul class="nav navbar-right panel_toolbox">
                      <li><button onclick="print_element('print_area')" class="edit btn btn-sm btn-info"><i class="fa fa-print"></i> Print</button></li>
                      <li><button class="download_pdf btn btn-sm btn-info"><i class="fa fa-file-pdf-o"></i> PDF</button></li>
                    </ul>
                    <div class="clearfix"></div>
                </div>
                
                <div id="print_area" class="x_content ">

                    <?php $print_header_data['caption']  = lang('Student.page_title_student_profile'); ?>
                    <?= view('print/print_header', $print_header_data) ?>

                    <table id="profile_table" width="100%" >
                        <tr>
                            <td width="80%" >
                            <p>
                                <label><?= lang('Student.field_name') ?></label> : 
                                <?php echo $student_name; ?>
                            </p>

                            <p>
                                <label><?= lang('Student.field_registration') ?></label> : 
                                <b style="color: red;"><?php echo $registration_id; ?></b>
                            </p>

                            <?php if($phone){ ?>
                            <p>
                                <label><?= lang('Student.field_phone') ?></label> : 
                                <b style="color: red;"><?php echo $student_phone; ?></b>
                            </p>
                            <?php } ?>

                            <?php 
                            foreach ($fields as $key => $field) {
                                $field_id           = $field->id;
                                $field_title        = $field->title;
                                $show_profile       = $field->profile;
                                // get field exit value
                                $field_data = get_field_data('data', $field_id, $section_id, $student_id);
                                if(!empty($show_profile)){
                                    echo '<p><label>'.$field_title.'</label> : '.$field_data.'</p>';
                                } 
                            }
                            ?>
                            </td>
                            <td width="20%" class="student-photo-td text-right" >
                            <img class="img-responsive avatar-view" src="<?= $photo_path ?>" alt="Avatar" title="<?= $student_name ?>">
                            </td>
                        </tr>
                    </table>


                    <?php if($guardian_info): ?>
                    <h2><?= lang('Student.cap_guardian'); ?></h2>
                    <table id="profile_table" width="100%" >
                        <tr>
                            <td width="80%" >
                            <p>
                                <label><?= lang('Student.guardian_name') ?></label> : 
                                <?php echo $guardian_name; ?>
                            </p>
                            <p>
                                <label><?= lang('Student.guardian_email') ?></label> : 
                                <?php echo $guardian_email; ?>
                            </p>

                            <p>
                                <label><?= lang('Student.guardian_phone') ?></label> : 
                                <b style="color: red;"><?php echo $guardian_phone; ?></b>
                            </p>

                            <?php 
                            foreach ($guardian_fields as $key => $guardian_field) {
                                $guardian_field_id           = $guardian_field->id;
                                $guardian_field_title        = $guardian_field->title;
                                $show_guardian_profile       = $guardian_field->profile;
                                // get field exit value
                                $guardian_field_data = get_field_data('data', $guardian_field_id, $guardian_section_id, $guardian_id);
                                if(!empty($show_guardian_profile)){
                                    echo '<p><label>'.$guardian_field_title.'</label> : '.$guardian_field_data.'</p>';
                                } 
                            }
                            ?>
                            </td>
                            <td width="20%" class="student-photo-td text-right" >
                            <img class="img-responsive avatar-view" src="<?= $guardian_photo_path ?>" alt="<?= $guardian_name ?>" title="<?= $guardian_name ?>">
                            </td>
                        </tr>
                    </table>
                    <?php endif; ?>


                    
                    <?php 
                    $academic_html = '<div class="display-data">';
                    foreach($academic_data as $academic_item){
                        $year_title = get_item('title', 'academic_year', 'id', $academic_item->year_id);
                        $class_title = get_item('title', 'academic_class', 'id', $academic_item->class_id);

                        $subject_title = '';
                        $subject_ids = explode(',', $academic_item->subject_ids);
                        $total_subject = count($subject_ids);
                        $sk = 0;
                        foreach($subject_ids as $subject_id){
                            $sk ++;
                            if($sk ==$total_subject){
                                $subject_title .= get_item('title', 'academic_subject', 'id', $subject_id);
                            }else{
                                $subject_title .= get_item('title', 'academic_subject', 'id', $subject_id).', ';
                            }
                            
                        }

                        
                        
                        $academic_html .= '<h2>'.lang('Student.academic_info').' - '.$year_title.'</h2>';

                        $academic_html .='<p><label>'.lang('Student.field_class').'</label>: '.$class_title.'</p>';
                        // If student class roll is active
                        if($class_roll){
                            $roll_number  = $academic_item->roll;
                            $academic_html .='<p><label>'.lang('Student.field_roll').'</label>: '.$roll_number.'</p>';
                        }

                        $academic_html .='<p><label>'.lang('Student.field_subject').' </label>: '.$subject_title.'</p>';
                        // If student academic category is active
                        if($academic_category){
                            $category_title  = get_item('title', 'academic_category', 'id', $academic_item->category_id);
                            $academic_html .='<p><label>'.lang('Student.field_category').' </label>: '.$category_title.'</p>';
                        }

                        // If student academic department is active
                        if($academic_department){
                            $department_title  = get_item('title', 'academic_department', 'id', $academic_item->department_id);
                            $academic_html .='<p><label>'.lang('Student.field_department').'</label>: '.$department_title.'</p>';
                        }

                        // If student academic shift is active
                        if($academic_shift){
                            $shift_title  = get_item('title', 'academic_shift', 'id', $academic_item->shift_id);
                            $academic_html .='<p><label>'.lang('Student.field_shift').'</label>: '.$shift_title.'</p>';
                        }

                        // If student academic skill is active
                        if($extra_skill){
                            $skill_title = '';
                            $skill_ids   = explode(',', $academic_item->skill_ids);
                            $total_skill = count($skill_ids);
                            $skill_k     = 0;
                            foreach($skill_ids as $skill_id){
                                $skill_k ++;
                                if($skill_k == $total_skill){
                                    $skill_title .= get_item('title', 'academic_skill', 'id', $skill_id);
                                }else{
                                    $skill_title .= get_item('title', 'academic_skill', 'id', $skill_id).', ';
                                }
                            }

                            $academic_html .='<p><label>'.lang('Student.field_skill').'</label>: '.$skill_title.'</p>';
                        }
                        
                       
                    }
                    $academic_html .= '</div>';

                    echo $academic_html;
                    ?>


                </div>
            </div>
        </div>
    </div>

    <div class="row">
        
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="col-sm-6">
                <?= form_open('application/approve', [
                    'class'   => 'form-horizontal form-label-left', 
                    'id'      => 'review_form', 
                    'method'  => 'post', 
                    'enctype' => 'multipart/form-data',
                    'data-parsley-validate'=>'' 
                    ]); 

                    if($class_roll){
                        echo field_text(lang('Student.field_roll'), 'roll', 'roll', '', '', true); 
                    }
                ?>
                </div>
                <div class="col-sm-6">
                <input type="hidden" name="student_id"  value="<?= $student_id ?>" >
                <input type="hidden" name="academic_id"  value="<?= $academic_id ?>" >
                <input type="hidden" name="user_id"  value="<?= $user_id ?>" >
                <button type="submit" class="btn btn-success add"><?= lang('Student.btn_aprove') ?></button>
                </div>

                <?= form_close() ?>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>
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