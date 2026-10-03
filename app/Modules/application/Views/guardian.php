<?php 
use App\Models\FieldbuilderModel;
$field_model = new FieldbuilderModel();
$section_id  = get_item('id', 'fields_section', 'title', 'Guardian');
$fields      = $field_model->where('section', $section_id)->orderBy('field_order', 'ASC')->findAll();

// Guardian Data
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

$guardian_name  = isset($post_data['guardian_name']) ? $post_data['guardian_name']: $guardian_name;
$guardian_email = isset($post_data['guardian_email']) ? $post_data['guardian_email']: $guardian_email;
$guardian_phone = isset($post_data['guardian_phone']) ? $post_data['guardian_phone']: $guardian_phone;
?>

<input type="hidden" name="guardian_id" value="<?= $guardian_id ?>" />


<div id="guardian_preview" class="row " style="display: none;">
    <div class="col-sm-6">
        <p><label><?= lang('Student.guardian_name') ?></label>: <b id="guardian_preview_name"></b></p>
        <p><label><?= lang('Student.guardian_email') ?></label>: <b id="guardian_preview_email"></b></p>
        <p><label><?= lang('Student.guardian_phone') ?></label>: <b id="guardian_preview_phone"></b></p>
        <?php 
        foreach ($fields as $key => $field) {
            $field_id           = $field->id;
            $field_title        = $field->title;
            echo '<p><label>'.$field_title.'</label>: <b id="guardian_preview_'.$field_id.'"></b></p>';
        }
        ?>
    </div>
    <div class="col-sm-6">
        <p class="mt-0">
            <img src="" id="preview_profile_image_2" alt="Profile Image" />
        </p>
    </div>
</div>

<div id="guardian_form" class="row ">
    <div class="col-sm-6">

        <div class="row form-group">
            <label class="col-form-label col-sm-4 control-label" for="name"><?= lang('Student.guardian_name') ?> <span class="required">*</span></label>
            <div class="col-sm-8">
            <input type="text" name="guardian_name"  value="<?= $guardian_name; ?>" id="guardian_name" required="required"  class="form-control  required">
            </div>
        </div>

        <div class="row form-group">
            <label class="col-form-label col-sm-4 control-label" for="email"><?= lang('Student.guardian_email') ?> <span class="required">*</span></label>
            <div class="col-sm-8">
            <input type="email" name="guardian_email"  value="<?= $guardian_email; ?>" id="guardian_email" required="required"  class="form-control required">
            </div>
        </div>

        <div class="row form-group">
            <label class="col-form-label col-sm-4 control-label" for="phone"><?= lang('Student.guardian_phone') ?> <span class="required">*</span></label>
            <div class="col-sm-8">
            <input type="text"  name="guardian_phone" value="<?= $guardian_phone; ?>" id="guardian_phone" required="required"  class="form-control required">
            </div>
        </div>

        <?php 
        foreach ($fields as $key => $field) {
            $field_id           = $field->id;
            $field_title        = $field->title;
            $field_type         = $field->type;
            $field_required     = $field->required;
            $field_option_param = $field->option_param;
            $item_id            = '';

            // get field exit value
            $field_data = get_field_data('data', $field_id, $section_id, $guardian_id);
            $field_value = isset($post_data['field_'.$field_id]) ? $post_data['field_'.$field_id]: $field_data;
            echo get_field($field_id, $section_id, $item_id, $field_title, $field_type, $field_required, $field_option_param, $field_value);
        }
        ?>
    </div>
    <div class="col-sm-6">
        <div class="row form-group">
            <label class="col-form-label col-sm-12 control-label" for="name"><?= lang('Student.guardian_photo') ?></label>
            <input type="file" name="guardian_photo"  value="" id="guardian_photo" onchange="readURL(this, 2);"  class="form-control  ">
            <input type="hidden" name="old_guardian_photo" value="<?= $guardian_photo ?>" />
        </div>

        <div class="row form-group">
            <p class="mt-0">
            <img src="<?= $guardian_photo_path ?>" id="preview_2" alt="<?= lang('Student.guardian_photo') ?>" />
            </p>
        </div>
    </div>
</div>
                    

                   