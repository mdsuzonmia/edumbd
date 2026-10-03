<?php 
    if($user->photo){
        $photo_path = base_url('uploads/' . esc($user->photo));
    }else{
        $photo_path = base_url('uploads/photo.png');
    }

?>
<?= form_open('auth/update-profile', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'edit_user_form', 
    'method'  => 'post', 
    'enctype' => 'multipart/form-data',
    'data-parsley-validate'=>'' 
    ]); 
?>

<div class="row mb-3">
    <div class="col-md-6 col-sm-6 ">
        <h3 class="text-secondary"><i class="fa fa-user"></i> <?= lang('Auth.edit_profile'); ?></h3>
    </div>

    <div class="col-md-6 col-sm-6 text-end ">
        <div class="row  g-2 justify-content-end">
            <div class="col-auto">
                <button type="submit" class="btn btn-success"><i class="fa-solid fa-floppy-disk"></i> <?= lang('Auth.btn_save_change') ?></button>
            </div>
        </div>
    </div>
</div>


<div class="row">
    <div class="col-12 ">
        <?= get_system_message(); ?>
    </div>
</div>

<!-- User Form -->
<div class="row">
    <div class="col-md-6">
        <!-- User Profile -->
        <div class="card mb-4">
            <div class="card-header">General Info</div>
            <div class="card-body">
            <div class="mb-3 row">
                <label class="form-label col-sm-4"><?= lang('Auth.name'); ?>:</label>
                <div class="col-sm-8">
                    <input type="text" name="name" class="form-control " value="<?= esc($user->name) ?>" required>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="form-label col-sm-4"><?= lang('Auth.email'); ?>:</label>
                <div class="col-sm-8">
                    <input type="email" name="email" class="form-control " value="<?= esc($user->email) ?>" required>
                </div>
            </div>

            <div class="mb-3 row">
                <label class="form-label col-sm-4"><?= lang('Auth.phone'); ?>:</label>
                <div class="col-sm-8">
                    <input type="number" name="phone" class="form-control " value="<?= esc($user->phone) ?>" required>
                </div>
            </div>
            
            
            
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <!-- User Photo -->
        <div class="card mb-4">
            <div class="card-header">Profile Photo</div>
            <div class="card-body">
            <div class="mb-3 row">
                <label class="form-label col-sm-4"><?= lang('Auth.photo'); ?>:</label>
                <div class="col-sm-8">
                    <input type="file" name="photo" onchange="readURL(this, 1);" >
                </div>
            </div>
            <div class="mb-3 row">
                <label class="form-label col-sm-4"></label>
                <div class="col-sm-8">
                    <img src="<?= $photo_path ?>" id="preview_1" alt="<?= esc($user->name) ?>" />
                </div>
            </div>
            </div>
        </div>
    </div>
</div>


<?= form_close(); ?>

<script>
    
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
