<?php 

// User Data
if($user_data){
    $user_id   = $user_data->id;
    $student_username  = $user_data->username;
    $email     = $user_data->email;
    $required = '';
    $required_star = '';
    
}else{
    $user_id   = '';
    $student_username  = '';
    $email     = '';
    $required = 'required="required"';
    $required_star ='<span class="required">*</span>';
}

$student_username = isset($post_data['student_username']) ? $post_data['student_username']: $student_username;
$student_email = isset($post_data['student_email']) ? $post_data['student_email']: $email;
$password = isset($post_data['password']) ? $post_data['password']: '';
?>

<?= field_text(lang('Student.username'), 'student_username', 'student_username', '', $student_username, true); ?>
<?= field_text(lang('Student.field_email'), 'student_email', 'student_email', '', $student_email, true); ?>

<div class="row form-group">
    <label class="col-form-label col-sm-4 control-label" for="email"><?= lang('Student.password') ?> <?= $required_star ?></label>
    <div class="col-sm-8">
    <input type="password" name="password"  value="<?= $password ?>" id="password" <?= $required ?>  class="form-control ">
    <span style="position: absolute;right:15px;top:7px;" onclick="hideshow()" >
        <i id="slash" class="fa fa-eye-slash"></i>
        <i id="eye" class="fa fa-eye"></i>
    </span>
    </div>
</div>

<input type="hidden" name="user_id"  value="<?= $user_id ?>" >



                   