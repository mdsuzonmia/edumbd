<?php

// Get Role
use App\Models\RoleModel;
$role_model = new RoleModel();
$role_items = $role_model->orderBy('id', 'ASC')->findAll();
$options = [];
$options[''] = lang('User.select_role');
foreach ($role_items as $item) {
    $options[$item->id] = $item->name;
}
$data = [
    'id' => 'field_role',
    'class' => 'form-control mb-3',
];
$data['required'] = 'required';



// Get Plan
use App\Models\PlanModel;
$plan_model = new PlanModel();
$plan_items = $plan_model->where('status', 1)->orderBy('id', 'ASC')->findAll();
$plan_options = [];
$plan_options[''] = lang('User.select_plan');
foreach ($plan_items as $item) {
    $plan_options[$item->id] = $item->name;
}
$plan_data = [
    'id' => 'field_plan',
    'class' => 'form-control mb-3',
];
$plan_data['required'] = 'required';


// User Data
if($user_data){
    $user_id          = $user_data->id;
    $plan_id          = $user_data->plan_id;
    $name             = $user_data->name;
    $email            = $user_data->email;
    $phone            = $user_data->phone;
    $photo            = $user_data->photo;
    $role             = $user_data->role_id;
    $status           = $user_data->status;
    $is_email_verified           = $user_data->is_email_verified_status;
    $required         = '';
    $required_star    = '';
    
}else{
    $user_id          = '';
    $plan_id          = '';
    $name             = '';
    $username         = '';
    $email            = '';
    $phone            = '';
    $photo            = '';
    $role             = '';
    $status           = '';
    $is_email_verified           = '';
    $required         = 'required="required"';
    $required_star    ='<span class="required">*</span>';
}


if($photo){
    $photo_path = base_url('uploads/' . esc($photo));
}else{
    $photo_path = base_url('uploads/photo.png');
}

$name       = isset($post_data['name']) ? $post_data['name']: $name;
$phone      = isset($post_data['phone']) ? $post_data['phone']: $phone;
$email      = isset($post_data['email']) ? $post_data['email']: $email;
$password   = isset($post_data['password']) ? $post_data['password']: '';
$role       = isset($post_data['role_id']) ? $post_data['role_id']: $role;
$plan       = isset($post_data['plan_id']) ? $post_data['plan_id']: $plan_id;
$role_field = form_dropdown('role_id', $options, $role, $data);

// Plan field 
$plan_field = form_dropdown('plan_id', $plan_options, $plan, $plan_data);


// Get Status
$status_value = isset($post_data['status']) ? $post_data['status']: $status;
$status_list  = get_status_list(lang('Common.th_status'), 'status', 'status', $status_value, true);

// Get Verified
$verified_value = isset($post_data['is_verified']) ? $post_data['is_verified']: $is_email_verified;
$verified_list  = yes_no_dropdown('is_verified', 'is_verified', $verified_value);

?>

    <?= form_open('saas-admin/users/store', [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'user_form', 
        'method'  => 'post', 
        'enctype' => 'multipart/form-data',
        'data-parsley-validate'=>'' 
        ]); 
    ?>

    <div class="row mb-3">
        <div class="col-sm-6 ">
            <h3 class="text-secondary"><i class="fa fa-user"></i> <?= lang('User.heading_form'); ?></h3>
        </div>

        <div class="col-sm-6 title_right text-end ">
            <button type="submit" class="btn btn-sm btn-success add"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> <?= lang('Common.btn_save') ?></button>
            <a href="<?= base_url('/saas-admin/users') ?>" class="btn btn-sm btn-info"><i class="fa fa-arrow-left" aria-hidden="true"></i> <?= lang('User.back_to_user') ?></a>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12 col-md-12   ">
            <div class="card">
                <div class="card-body">

                    <?php if(isset($validation)): ?>
                    <div style="color: red;">
                        <?= $validation->listErrors(); ?>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                    <div class="col-sm-6">
                        <?= field_text(lang('User.name'), 'name', 'name', ' mb-3', $name, true); ?>
                        <?= field_text(lang('User.email'), 'email', 'email', ' mb-3', $email, true); ?>
                        <?= field_text(lang('User.phone'), 'phone', 'phone', ' mb-3', $phone, false); ?>

                        <div class="row form-group mb-3">
                            <label class="col-form-label col-sm-4 control-label" for="email"><?= lang('User.password') ?> <?= $required_star ?></label>
                            <div class="col-sm-8" style="position: relative;">
                            <input type="password" name="password"  value="<?= $password ?>" id="password" <?= $required ?>  class="form-control ">
                            <span style="position: absolute;right:15px;top:7px;" onclick="hideshow()" >
                                <i id="slash" class="fa fa-eye-slash" style="display: none;"></i>
                                <i id="eye" class="fa fa-eye"></i>
                            </span>
                            </div>
                        </div>

                        <div class="row form-group mb-3">
                            <label class="col-form-label col-sm-4 control-label" for="field_role"><?= lang('User.user_role') ?> <?= $required_star ?></label>
                            <div class="col-sm-8"><?= $role_field ?></div>
                        </div>

                        
                        <div class="row form-group mb-3">
                            <label class="col-form-label col-sm-4 control-label" for="field_plan"><?= lang('User.plan') ?> <?= $required_star ?></label>
                            <div class="col-sm-8"><?= $plan_field ?></div>
                        </div>
                        

                        <div class="row form-group mb-3">
                            <label class="col-form-label col-sm-4 control-label" for="active"><?= lang('User.th_verified') ?> <?= $required_star ?></label>
                            <div class="col-sm-8"><?= $verified_list ?></div>
                        </div>

                        <?= $status_list; ?>

                    </div>

                    <div class="col-sm-6">
                        <div class="row form-group mb-3">
                            <label class="col-form-label col-sm-12 control-label" for="name"><?= lang('User.photo') ?></label>
                            <input type="file" name="photo"  value="" id="photo" onchange="readURL(this, 1);"  class="form-control  ">
                            <input type="hidden" name="old_photo" value="<?= $photo ?>" />
                        </div>

                        <div class="row form-group">
                            <p class="mt-0">
                            <img src="<?= $photo_path ?>" id="preview_1" alt="<?= lang('User.photo') ?>" />
                            </p>
                        </div>
                    </div>
                    </div>
                </div>

                <input type="hidden" name="user_id"  value="<?= $user_id ?>" >
            </div>
        </div>


    </div>

    <?= form_close() ?>



<!-- Javascript functions	-->
<script>
    function hideshow(){
        var password = document.getElementById("password");
        var slash = document.getElementById("slash");
        var eye = document.getElementById("eye");
        
        if(password.type === 'password'){
            password.type = "text";
            slash.style.display = "block";
            eye.style.display = "none";
        }
        else{
            password.type = "password";
            slash.style.display = "none";
            eye.style.display = "block";
        }

    }

    function readURL(input, id) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                jQuery('#preview_'+id).attr('src', e.target.result);
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

 