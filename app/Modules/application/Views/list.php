<?php 
$class_roll             = esc(get_setting_value('class_roll'));
$academic_shift         = esc(get_setting_value('academic_shift'));
$academic_department    = esc(get_setting_value('academic_department'));
$academic_category      = esc(get_setting_value('academic_category'));
$extra_skill            = esc(get_setting_value('extra_skill'));
$student_phone_enabled  = esc(get_setting_value('student_phone'));

// Get status list
$status_array = array(
    ''  => lang('Student.filter_by_status'), 
    '0' => lang('Common.sys_unpublished'), 
    '1' => lang('Common.sys_published'),
    '2' => lang('Common.text_trash'),
    '3' => lang('Common.text_application')
);
$status_data = [
    'id'    => 'field_status',
    'class' => 'form-control mb-3',
];
if(isset($status)){$status = $status;}else{$status = '';}
$status_list = form_dropdown('status', $status_array, $status, $status_data);

if(isset($text)){$text = $text;}else{$text = '';}

?>

<!-- page content -->
<div class="right_col" role="main">
    <div class="">

    <?= form_open('application-list', [
    'class'   => 'form-horizontal form-label-left', 
    'id'      => 'student_search', 
    'method'  => 'get', 
    'data-parsley-validate'=>'' 
    ]); 
    ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />
    <div class="page-title">
        <div class="title_left">
        <h3><?= lang('Student.application_list'); ?></h3>
        </div>

        <div class="title_right">
            
            <div class="col-sm-5 form-group pull-right pr-0 mb-0"><?= $status_list; ?></div>
            <div class="col-sm-2 form-group pull-right pr-0 mb-0"><button type="button" class="clear btn btn-sm btn-primary mt-1" ><?= lang('Common.btn_reset') ?></button></div>
            <div class="col-sm-5  form-group pull-right top_search mb-0">
                <div class="input-group">
                    <input type="text" id="search_text" name="text" value="<?= $text ?>" class="form-control" placeholder="Search for...">
                    <span class="input-group-btn">
                        <button type="submit" class="btn btn-default" ><?= lang('Common.btn_search') ?></button>
                    </span>
                </div>

                
            </div>
        </div>
    </div>

    <?= form_close() ?>
    <div class="clearfix"></div>

    <div class="row">
        
        <div class="col-md-12 col-sm-12 ">
            <div class="x_panel">
                
                <div class="x_content">
                    <?= get_system_message(); ?>
                    <div id="result"></div>

                    <table id="table" class="table student-table student-list m-b-0 c_list">

                        <thead>
                            <tr>
                                <th width="30%"><?= lang('Student.field_name') ?></th>    
                                <th class="text-center" ><?= lang('Common.th_status') ?></th> 
                                <th width="20%" class="text-right" ><?= lang('Common.th_action') ?></th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            foreach ($items as $key => $item) {
                                $student_id      = $item->id;
                                $registration_id = $item->registration_id;
                                $name            = $item->name;
                                $phone           = $item->phone;
                                $photo           = $item->photo;
                                $user_id         = $item->user_id;

                                if($photo){
                                    $photo_path = base_url('uploads/' . esc($photo));
                                }else{
                                    $photo_path = base_url('uploads/photo.png');
                                }

                               
                                $review_link = base_url('/application/review/'.$student_id);

                                echo '<tr id="item_'.$item->id.'">
                                    <td class="pl-0" width="40%">
                                        <div class="profile ">
                                            <div class="profile_pic">
                                                <img src="'.$photo_path.'" class="img-circle profile_img mt-0" alt="'.$name.'" /> 
                                            </div>
                                            <div class="profile_info pt-0 pl-0">
                                                
                                                <b class="title">'.$name.'</b>';
                                                echo '<p> '.lang('Student.field_registration').' : <b style="color: red;">'.$registration_id.'</b></p>';

                                                if($student_phone_enabled && !empty($phone)){
                                                    echo '<p> '.lang('Student.field_phone').': '.$phone.'</p>';
                                                }
                                           echo '</div>
                                        </div>
                                    
                                    </td>
                                    ';

                                    $status_html = '';
                                    $restore_btn = '';
                                    $empty_trash_btn = '';
                                    $trash_btn = '';
                                    if($item->status == 0){
                                        $status_html .= '<span class="badge badge-warning"><i class="fa fa-times-circle"></i> '.$status_array[$item->status].'</span>';
                                        $trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" trash btn btn-sm btn-danger" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                   
                                    }

                                    if($item->status == 1){
                                        $status_html .= '<span class="badge badge-success"><i class="fa fa-check-circle"></i> '.$status_array[$item->status].'</span>';
                                        $trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" trash btn btn-sm btn-danger" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                   
                                    }

                                    if($item->status == 2){
                                        $status_html .= '<span class="badge badge-danger"><i class="fa fa-ban"></i> '.$status_array[$item->status].'</span>';
                                        $empty_trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" empty_trash btn btn-sm btn-danger" title="'.lang('Common.empty_trash').'"><i class="fa fa-trash"></i> '.lang('Common.empty_trash').'</button>';
                                        
                                    }

                                    if($item->status == 3){
                                        $status_html .= '<span class="badge badge-warning"><i class="fa fa-ban"></i> '.$status_array[$item->status].'</span>';
                                        
                                        $trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" trash btn btn-sm btn-danger" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                   
                                    }

                                    echo '<td class="text-center">'.$status_html.'</td>';

                                    echo'<td class="text-right pr-0" width="20%">
                                    <a href="'.$review_link.'" class=" edit btn btn-sm btn-info" title="'.lang('Common.text_review').'"><i class="fa fa-edit"></i> '.lang('Common.text_review').'</a> 
                                    '.$empty_trash_btn.'
                                    '.$trash_btn.'
                                    </td>
                                    </tr>';
                            }
                            ?>
                            
                        </tbody>
                    </table>
                    
                </div>
            </div>
        </div>
    </div>

    <div class="clearfix"></div>
    </div>
</div>

<script type="text/javascript">
$(document).ready(function() {

    $('#field_status').on('change', function() {
        $('#student_search').submit();
    });
    
    var csrfToken   = $('#csrf_token').val();
    // Set the CSRF token in all AJAX requests globally
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': csrfToken
        }
    });

    // Reset
    $(document).on('click', '.clear', function(e) {
        $("#search_text").val('');
        $("#field_status").val('');
        $('html, body').animate({
            scrollTop: $("body").offset().top
        }, 2000);
        $( "#search_text" ).focus();
    });

    // Trash
    $(document).on('click', '.trash', function(e) { 
        var id           = $(this).data("id");
        var currentRow   = $(this);
        var confirmation = confirm('<?= lang('Common.trash_warning_message') ?>');
        if(confirmation)
        {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

            $.ajax({
                type : "post",
                dataType : "json",
                url : '<?= base_url('/student/trash'); ?>',
                data : {
                    student_id:id
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Set the CSRF token in the headers
                },
                success: function(response, status, xhr) {
                    $("#result").html(response.html); 
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status = true) {
                        currentRow.parents('tr').remove();
                    }
                }
            }) 
        }
    });

    // Empty Trash
    $(document).on('click', '.empty_trash', function(e) { 
        var id           = $(this).data("id");
        var currentRow   = $(this);
        var confirmation = confirm('<?= lang('Common.empty_trash_warning_message') ?>');
        if(confirmation)
        {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

            $.ajax({
                type : "post",
                dataType : "json",
                url : '<?= base_url('/student/empty-trash'); ?>',
                data : {
                    student_id:id
                },
                headers: {
                    'X-CSRF-TOKEN': csrfToken // Set the CSRF token in the headers
                },
                success: function(response, status, xhr) {
                    $("#result").html(response.html); 
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status = true) {
                        currentRow.parents('tr').remove();
                    }
                }
            }) 
        }
    });

    
});
</script>
