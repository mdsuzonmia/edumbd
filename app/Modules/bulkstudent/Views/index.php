<?php 

// Get School List
$school_field = get_school_field(lang('School.select_school'), 'school_id', 'field_school', '', '', true);
?>

<div class="right_col" role="main">

    <?= form_open('bulkstudent', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'search', 
    'method'  => 'get', 
    'data-parsley-validate'=>'' 
    ]); 
    ?>
    <input type="hidden" name="task" value="form" />
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-md-6 col-sm-6 ">
            <h3><i class="fa fa-upload"></i> <?= lang('Bulkstudent.page_title'); ?></h3>
        </div>

        <div class="col-md-6 col-sm-6 text-right pt-2">
            <button type="submit" class="btn btn-sm btn-success add"><i class="fa fa-pencil-square-o"></i> <?= lang('Mark.btn_get_input') ?></button>
        </div>
    </div>

    <div class="clearfix"></div>
    
    
    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                <div class="x_content">
                    <div class="col-sm-6">
                        <div class="row form-group">
                            <label class="col-form-label col-sm-4 control-label" for="field_school"><?= lang('Mark.field_school') ?> *</label>
                            <div class="col-sm-8">
                            <?= $school_field ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-sm-6">
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    

    <div class="clearfix"></div>
    <?= form_close() ?>
</div>