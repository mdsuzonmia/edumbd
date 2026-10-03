<?php 

// Get Role
use App\Models\RoleModel;
$role_model = new RoleModel();
$role_items = $role_model->where('status', 1)->orderBy('field_order', 'ASC')->findAll();
$role_options = [];
$role_options[''] = lang('User.filter_by_role');
foreach ($role_items as $item) {
    $role_options[$item->id] = $item->name;
}
$role_data = [
    'id'       => 'field_role',
    'class'    => 'form-control mb-3'
];
$role_id       = isset($role_id) ? $role_id: '';
$role_field = form_dropdown('role_id', $role_options, $role_id, $role_data);

// Get School
// use App\Models\SchoolModel;
// $school_model = new SchoolModel();
// $school_items = $school_model->where('status', 1)->orderBy('id', 'ASC')->findAll();
// $school_options = [];
// $school_options[''] = lang('User.filter_by_school');
// foreach ($school_items as $item) {
//     $school_options[$item->id] = $item->name;
// }
// $school_data = [
//     'id' => 'field_school',
//     'class' => 'form-control mb-3'
// ];
// $school       = isset($school) ? $school: '';
// $school_field = form_dropdown('school_id', $school_options, $school, $school_data);

// Set list items
$show_list_array = array(
    ''  => lang('Student.show_list'), 
    '10' => '10', 
    '50' => '50',
    '100' => '100',
    '200' => '200',
    '500' => '500',
    '1000' => '1000'
);
$show_list_data = [
    'id'    => 'field_show_list',
    'class' => 'form-control mb-3 mt-0 filter-select pull-right',
];
if(isset($show)){$show = $show;}else{$show = '';}
$show_list = form_dropdown('show', $show_list_array, $show, $show_list_data);


// Get Status
$status_array = get_status();
if(isset($status)){$status = $status;}else{$status = '';}
$status_list  = get_status_list(lang('Common.filter_status'), 'status', 'field_status', $status, false, true, 'mt-0 mr-1 pull-right filter-select');


if(isset($text)){$text = $text;}else{$text = '';}


?>

    <?= form_open('saas-admin/users', [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'user_search', 
        'method'  => 'get', 
        'data-parsley-validate'=>'' 
        ]); 
    ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-sm-3 ">
            <h3 class="text-secondary mb-0"><i class="fa fa-users"></i> <?= lang('User.heading_list'); ?></h3>
        </div>

        <div class="col-sm-9 text-end pt-0">
            <div class="row  g-2 justify-content-end">
                <div class="col-auto ">
                    <div class="input-group">
                        <input type="text" id="search_text" name="text" value="<?= $text ?>" class="form-control" placeholder="Search for...">
                        <button type="submit" class="btn btn-primary" ><i class="fa fa-search" aria-hidden="true"></i> <?= lang('Common.btn_search') ?></button>
                        <!-- Reset Button -->
                        <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i> <?= lang('Common.btn_reset') ?></button>
                    </div>
                </div>
                
                <div class="col-auto"><?= $role_field; ?></div>
                <div class="col-auto"><?= $status_list; ?></div>
                <div class="col-auto"><?= $show_list; ?></div>
                <div class="col-auto">
                    <a href="<?= base_url('saas-admin/users/create') ?>" class=" edit btn  btn-primary" ><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('User.btn_add_new') ?></a>
                </div>
            </div>
        </div>
    </div>
    <?= form_close() ?>

    

    <div class="row">
        <div class="col-md-12 col-sm-12 ">
            <div class="card">
                <div class="card-body">
                    <?= get_system_message(); ?>
                    <div id="result"></div>

                    <table id="table" class="table student-table student-list ">

                        <thead>
                            <tr>
                                <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th> 
                                <th></th> 
                                <th><?= lang('User.th_name') ?></th>   
                                <th><?= lang('User.th_role') ?></th> 
                                <th class="text-left" ><?= lang('User.th_email') ?></th> 
                                <th class="text-center" ><?= lang('Common.th_status') ?></th> 
                                <th class="text-center" ><?= lang('User.th_verified') ?></th>
                                <th width="190px" class="text-center" ><?= lang('Common.th_action') ?></th>
                                <th width="50px" class="text-end" ><?= lang('User.th_id') ?></th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            
                            foreach ($items as $key => $item) {
                                $user_id        = $item->id;
                                $name           = $item->name;
                                $email          = $item->email;
                                $phone          = $item->phone;
                                $photo          = $item->photo;
                                $school_id      = $item->school_id;
                                $school_name    = $item->school_name;
                                $role_name      = $item->roles;
                                $created_at     = date( 'd M, Y', strtotime($item->created_at));
                                $updated_at     = date( 'd M, Y', strtotime($item->updated_at));

                                // Check $item->is_email_verified_status
                                $is_email_verified = ($item->is_email_verified_status == 1) ? 1 : 0;

                                if($photo){
                                    $photo_path = base_url('uploads/' . esc($photo));
                                }else{
                                    $photo_path = base_url('uploads/photo.png');
                                }

                                $edit_link      = base_url('saas-admin/users/edit/'.$user_id);

                                echo '<tr id="item_'.$item->id.'">
                                    <td class="text-left pl-0">'.++$key.'</td>
                                    <td class="pl-0" width="70px">
                                    <img src="'.$photo_path.'" class="img-fluid rounded-circle" style="width: 50px; height: 50px;" alt="'.$name.'" />
                                    </td>
                                    <td class="pl-0" >
                                        <p class="mb-0"><b class="title">'.$name.'</b></p>';
                                        if($created_at == $updated_at){
                                            echo '<p class="mb-0"><small class="text-muted">'.lang('Common.th_created_at').': '.$created_at.'</small></p>';
                                        }else{
                                            echo '<p class="mb-0"><small class="text-muted">'.lang('Common.th_created_at').': '.$created_at.'</small></p>
                                            <p class="mb-0"><small class="text-muted">'.lang('Common.th_updated_at').': '.$updated_at.'</small></p>';
                                        }

                                        // Display phone, school name, and role if available
                                        if($phone){
                                            echo '<p class="mb-0"><small class="text-muted">'.lang('User.phone').': '.$phone.'</small></p>';
                                        }

                                        if($school_name){
                                            echo '<p class="mb-0"><small class="text-muted">'.lang('User.th_school').': '.$school_name.'</small></p>';
                                        }
                                        

                                        echo '
                                        
                                    </td>
                                    <td class="pl-0" >'.$role_name.'</td>
                                    <td class="text-left" >'.$email.'</td>';

                                    $status_html = '';
                                    $restore_btn = '';
                                    $empty_trash_btn = '';
                                    $trash_btn = '';
                                    if($item->status == 0){
                                        $status_html .= '<span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> '.$status_array[$item->status].'</span>';
                                    }

                                    if($item->status == 1){
                                        $status_html .= '<span class="badge text-bg-success"><i class="fa fa-check-circle"></i> '.$status_array[$item->status].'</span>';
                                        $trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" trash btn btn-sm btn-danger mb-1" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                    }

                                    if($item->status == 2){
                                        $status_html .= '<span class="badge text-bg-danger"><i class="fa fa-ban"></i> '.$status_array[$item->status].'</span>';
                                        $restore_btn .= '<button type="button" data-id="'.$item->id.'" class=" restore btn btn-sm btn-success mb-1" title="'.lang('Common.text_restore').'"><i class="fa fa-check-circle"></i> '.lang('Common.text_restore').'</button>';
                                        $empty_trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" empty_trash btn btn-sm btn-danger mb-1" title="'.lang('Common.empty_trash').'"><i class="fa fa-trash"></i> '.lang('Common.empty_trash').'</button>';
                                        
                                    }

                                    echo '<td class="text-center">'.$status_html.'</td>';
                                    echo '<td class="text-center">'.get_yes_no($is_email_verified).'</td>';
                                    
                                    echo'<td class="text-center pr-0" >
                                    '.$restore_btn.'
                                    
                                    <a href="'.$edit_link.'" class=" edit btn btn-sm btn-info mb-1" title="'.lang('Common.text_edit').'"><i class="fa fa-edit"></i> '.lang('Common.text_edit').'</a> 
                                    '.$empty_trash_btn.'
                                    '.$trash_btn.'
                                    </td>';
                                    echo '<td class="text-end">'.$user_id.'</td>';
                                    echo '</tr>';
                            }
                            ?>
                            
                        </tbody>
                    </table>

                    <!-- Display pagination links -->
                    <div class="row">
                        <div class="col-sm-6">
                        <p>Showing <?= $pager->getCurrentPage() ?> to <?= $pager->getPerPage() ?> of <?= $pager->getTotal() ?> records.</p>
                        </div>
                        <div class="col-sm-6 text-right">
                            <?php if($pager->getTotal() > $pager->getPerPage() && !empty($pager->getTotal())): ?>
                            <?= $pager->links('default', $pagerTemplate); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    
                </div>
            </div>
        </div>
    </div>

<script type="text/javascript">
$(document).ready(function() {

    // Reset form on click of reset button
    $(document).on('click', '.clear', function(e) {
        
        // Clear search field
        $("#search_text").val('');

        // clear field_school
        $("#field_school").val('');

        // clear field_status
        $("#field_status").val('');

        // clear field_show_list
        $("#field_show_list").val('');

        // clear field_role
        $("#field_role").val('');

        // clear field_status
        $('#user_search').submit();

    })

    $('#field_school').on('change', function() {
        $('#user_search').submit();
    });

    $('#field_status').on('change', function() {
        $('#user_search').submit();
    });

    $('#field_show_list').on('change', function() {
        $('#user_search').submit();
    });

    $('#field_role').on('change', function() {
        $('#user_search').submit();
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
                url : '<?= base_url('saas-admin/users/trash'); ?>',
                data : {
                    user_id:id
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
                url : '<?= base_url('saas-admin/users/empty-trash'); ?>',
                data : {
                    user_id:id
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

    // Restoree
    $(document).on('click', '.restore', function(e) { 
        var id           = $(this).data("id");
        var currentRow   = $(this);
        var confirmation = confirm('<?= lang('Common.restore_warning_message') ?>');
        if(confirmation)
        {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');

            $.ajax({
                type : "post",
                dataType : "json",
                url : '<?= base_url('saas-admin/users/restore'); ?>',
                data : {
                    user_id:id
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
