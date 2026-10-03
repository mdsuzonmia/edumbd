<?php 

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

    <?= form_open('saas-admin/plans', [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'plan_search', 
        'method'  => 'get', 
        'data-parsley-validate'=>'' 
        ]); 
    ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-sm-3 ">
            <h3 class="text-secondary mb-0"><i class="bi bi-box"></i> <?= lang('Plan.heading_list'); ?></h3>
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
                
                
                <div class="col-auto"><?= $status_list; ?></div>
                <div class="col-auto"><?= $show_list; ?></div>
                <div class="col-auto">
                    <a href="<?= base_url('saas-admin/plans/create') ?>" class=" edit btn  btn-success" ><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('Plan.btn_add_new') ?></a>
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
                                <th><?= lang('Plan.th_name') ?></th>   
                                <th class="text-center"><?= lang('Plan.th_monthly_price') ?></th> 
                                <th class="text-center"><?= lang('Plan.th_yearly_price') ?></th> 
                                <th class="text-center"><?= lang('Plan.th_lifetime_price') ?></th>
                                
                                <th class="text-center"><?= lang('Plan.th_currency') ?></th> 
                                <th class="text-center"><?= lang('Plan.th_trial_days') ?></th> 
                                <th class="text-center"><?= lang('Plan.th_student_limit') ?></th> 
                                <th class="text-center"><?= lang('Plan.th_teachers_limit') ?></th>
                                <th class="text-center"><?= lang('Plan.th_branch_limit') ?></th>
                                <th class="text-center"><?= lang('Plan.th_admin_limit') ?></th>
                                <th class="text-center"><?= lang('Plan.th_sms_limit') ?></th>
                                
                                <th width="190px" class="text-center" ><?= lang('Common.th_action') ?></th>
                                
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            
                            foreach ($items as $key => $item) {
                                $plan_id        = $item->id;
                                $name           = $item->name;

                                $monthly_price  = $item->monthly_price;
                                $yearly_price   = $item->yearly_price;
                                $lifetime_price = $item->lifetime_price;
                               
                                $currency        = $item->currency;
                                $trial_days      = $item->trial_days;
                                $student_limit   = $item->student_limit;
                                $teachers_limit  = $item->teachers_limit;
                                $branch_limit    = $item->branch_limit;
                                $admin_limit     = $item->admin_limit;
                                $sms_limit       = $item->sms_limit;

                                // Decode multi-currency prices (JSON stored in the prices column)
                                $prices_data = [];
                                if (!empty($item->prices)) {
                                    $decoded_prices = json_decode((string) $item->prices, true);
                                    if (is_array($decoded_prices)) {
                                        $prices_data = $decoded_prices;
                                    }
                                }

                                // BDT prices (fallback to the legacy price columns)
                                $bdt_prices = isset($prices_data['BDT']) ? $prices_data['BDT'] : [
                                    'monthly_price'  => $monthly_price,
                                    'yearly_price'   => $yearly_price,
                                    'lifetime_price' => $lifetime_price,
                                ];

                                // USD prices
                                $usd_prices = isset($prices_data['USD']) ? $prices_data['USD'] : [];

                                // Price cell HTML (BDT on top, USD below)
                                $monthly_price_html  = '<span class="d-block">Tk '.number_format((float) $bdt_prices['monthly_price'], 2).'</span>';
                                $yearly_price_html   = '<span class="d-block">Tk '.number_format((float) $bdt_prices['yearly_price'], 2).'</span>';
                                $lifetime_price_html = '<span class="d-block">Tk '.number_format((float) $bdt_prices['lifetime_price'], 2).'</span>';

                                if (isset($usd_prices['monthly_price'])) {
                                    $monthly_price_html  .= '<span class="d-block text-muted small">$'.number_format((float) $usd_prices['monthly_price'], 2).'</span>';
                                }
                                if (isset($usd_prices['yearly_price'])) {
                                    $yearly_price_html   .= '<span class="d-block text-muted small">$'.number_format((float) $usd_prices['yearly_price'], 2).'</span>';
                                }
                                if (isset($usd_prices['lifetime_price'])) {
                                    $lifetime_price_html .= '<span class="d-block text-muted small">$'.number_format((float) $usd_prices['lifetime_price'], 2).'</span>';
                                }

                                $currency_html = '<span class="d-block">BDT</span><span class="d-block text-muted small">USD</span>';

                                $status         = $item->status;
                                $created_at     = date( 'd M, Y', strtotime($item->created_at));
                                $updated_at     = date( 'd M, Y', strtotime($item->updated_at));

                                // Get Status
                                $status_html = '';
                                if($status == 0){
                                    $status_html = '<span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> '.lang('Common.sys_unpublished').'</span>';
                                }
                                if($status == 1){
                                    $status_html = '<span class="badge text-bg-success"><i class="fa fa-check-circle"></i> '.lang('Common.sys_published').'</span>';
                                }

                               // Get Edit Link
                                $edit_link      = base_url('saas-admin/plans/edit/'.$plan_id);

                                // student limit
                                if($student_limit == 0){
                                    $student_limit = lang('Common.unlimited');
                                }

                                // teacher limit
                                if($teachers_limit == 0){
                                    $teachers_limit = lang('Common.unlimited');
                                }

                                // branch limit
                                if($branch_limit == 0){
                                    $branch_limit = lang('Common.unlimited');
                                }

                                // admin limit
                                if($admin_limit == 0){
                                    $admin_limit = lang('Common.unlimited');
                                }

                                // sms limit
                                if($sms_limit == 0){
                                    $sms_limit = lang('Common.unlimited');
                                }

                                echo '<tr id="item_'.$item->id.'">
                                    <td class="text-left pl-0">'.++$key.'</td>
                                    <td class="text-left" >'.$name.'</td>

                                    <td class="text-center" >'.$monthly_price_html.'</td>
                                    <td class="text-center" >'.$yearly_price_html.'</td>
                                    <td class="text-center" >'.$lifetime_price_html.'</td>
                                    
                                    
                                    
                                    <td class="text-center" >'.$currency_html.'</td>
                                    <td class="text-center" >'.$trial_days.'</td>
                                    <td class="text-center" >'.$student_limit.'</td>
                                    <td class="text-center" >'.$teachers_limit.'</td>
                                    <td class="text-center" >'.$branch_limit.'</td>
                                    <td class="text-center" >'.$admin_limit.'</td>
                                    <td class="text-center" >'.$sms_limit.'</td>';

                                    $restore_btn = '';
                                    $empty_trash_btn = '';
                                    $trash_btn = '';
                                    

                                    if($item->status == 1){
                                        $trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" trash btn btn-sm btn-danger mb-1" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                    }

                                    if($item->status == 2){
                                        $restore_btn .= '<button type="button" data-id="'.$item->id.'" class=" restore btn btn-sm btn-success mb-1" title="'.lang('Common.text_restore').'"><i class="fa fa-check-circle"></i> '.lang('Common.text_restore').'</button>';
                                        $empty_trash_btn .= '<button type="button" data-id="'.$item->id.'" class=" empty_trash btn btn-sm btn-danger mb-1" title="'.lang('Common.empty_trash').'"><i class="fa fa-trash"></i> '.lang('Common.empty_trash').'</button>';
                                        
                                    }

                                    
                                    
                                    echo'<td class="text-center pr-0" >
                                    '.$restore_btn.'
                                    
                                    <a href="'.$edit_link.'" class=" edit btn btn-sm btn-info mb-1" title="'.lang('Common.text_edit').'"><i class="fa fa-edit"></i> '.lang('Common.text_edit').'</a> 
                                    '.$empty_trash_btn.'
                                    '.$trash_btn.'
                                    </td>';
                                    
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

        
        // clear field_status
        $("#field_status").val('');

        // clear field_show_list
        $("#field_show_list").val('');


        // clear field_status
        $('#plan_search').submit();

    })


    $('#field_status').on('change', function() {
        $('#plan_search').submit();
    });

    $('#field_show_list').on('change', function() {
        $('#plan_search').submit();
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
                url : '<?= base_url('saas-admin/plans/trash'); ?>',
                data : {
                    plan_id:id
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
                url : '<?= base_url('saas-admin/plans/empty-trash'); ?>',
                data : {
                    plan_id:id
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
                url : '<?= base_url('saas-admin/plans/restore'); ?>',
                data : {
                    plan_id:id
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
