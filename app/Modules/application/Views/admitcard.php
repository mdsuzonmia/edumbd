<?php 
use App\Models\FieldbuilderModel;
$field_model = new FieldbuilderModel();
$section_id  = get_item('id', 'fields_section', 'title', 'Student');
$fields      = $field_model->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

// Student Data
if($student_data){
    $student_id      = $student_data->id;
    $registration_id = $student_data->registration_id;
    $student_name    = $student_data->name;
    $photo           = $student_data->photo;
    $student_phone   = $student_data->phone;
}else{
    $student_id    = '';
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

$student_phone_enabled    = esc(get_setting_value('student_phone'));
?>

    <?php $print_header_data['caption']  = lang('Student.admitcard_caption'); ?>
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

            <?php if($student_phone_enabled){ ?>
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