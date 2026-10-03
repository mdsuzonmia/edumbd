<?php 
use App\Models\FieldbuilderModel;
$field_model = new FieldbuilderModel();
$section_id = get_item('id', 'fields_section', 'title', 'Student');
$fields = $field_model->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

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

$student_name  = isset($post_data['student_name']) ? $post_data['student_name']: $student_name;
$student_phone = isset($post_data['student_phone']) ? $post_data['student_phone']: $student_phone;

$student_phone_enabled    = esc(get_setting_value('student_phone'));

?>

<div id="profile_preview" class="row " style="display: none;">
    <div class="col-sm-6">
        <p><label><?= lang('Student.field_name') ?></label>: <b id="profile_preview_name"></b></p>
        <?php 
        if($student_phone_enabled){
            echo '<p><label>'.lang('Student.field_phone').'</label>: <b id="profile_preview_phone"></b></p>';
        }
 
        foreach ($fields as $key => $field) {
            $field_id           = $field->id;
            $field_title        = $field->title;
            echo '<p><label>'.$field_title.'</label>: <b id="profile_preview_'.$field_id.'"></b></p>';
        }
        ?>
    </div>
    <div class="col-sm-6">
        <p class="mt-0">
            <img src="" id="preview_profile_image_1" alt="Profile Image" />
        </p>
    </div>
</div>

<div id="profile_form" class="row ">
    <input type="hidden" name="student_id" value="<?= $student_id ?>" />
    <div class="col-sm-6">
        <?= field_text(lang('Student.field_name'), 'student_name', 'student_name', '', $student_name, true); ?>
        
        <?php 
        if($student_phone_enabled){
            echo field_text(lang('Student.field_phone'), 'student_phone', 'student_phone', '', $student_phone, true);
        }
        ?>
    

        <?php 
        foreach ($fields as $key => $field) {
            $field_id           = $field->id;
            $field_title        = $field->title;
            $field_type         = $field->type;
            $field_required     = $field->required;
            $field_option_param = $field->option_param;
            $item_id            = '';

            // get field exit value
            $field_data = get_field_data('data', $field_id, $section_id, $student_id);
            $field_value = isset($post_data['field_'.$field_id]) ? $post_data['field_'.$field_id]: $field_data;
            
            echo get_field($field_id, $section_id, $item_id, $field_title, $field_type, $field_required, $field_option_param, $field_value);
        }
        ?>
    </div>
    <div class="col-sm-6">
        <div class="row form-group">
            <label class="col-form-label col-sm-12 control-label" for="name"><?= lang('Student.student_photo') ?></label>
            <input type="file" name="student_photo"  value="" id="student_photo" onchange="readURL(this, 1);" class="form-control  ">
            <input type="hidden" name="old_student_photo" value="<?= $photo ?>" />
        </div>

        <div class="row form-group">
            <p class="mt-0">
            <img src="<?= $photo_path ?>" id="preview_1" alt="<?= lang('Student.student_photo') ?>" />
            </p>
        </div>
    </div>

</div>

