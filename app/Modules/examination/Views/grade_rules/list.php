<?php 

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

$status_array = get_status();
if(isset($status)){$status = $status;}else{$status = '';}
$status_list  = get_status_list(lang('Common.filter_status'), 'status', 'field_status', $status, false, true, 'mt-0 mr-1 pull-right filter-select');

if(isset($text)){$text = $text;}else{$text = '';}

$selected_system_id = isset($selected_system_id) ? $selected_system_id : '';

?>

    <?= form_open('examination/grade-systems/rules/'.$selected_system_id, [
        'class'   => 'form-horizontal form-label-left', 
        'id'      => 'grade_rule_search', 
        'method'  => 'get', 
        'data-parsley-validate'=>'' 
        ]); 
    ?>
    <input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>" />

    <div class="row">
        <div class="col-sm-3 ">
            <h3 class="text-secondary mb-0"><i class="bi bi-list-check"></i> <?= lang('GradeRule.heading_list'); ?></h3>
        </div>

        <div class="col-sm-9 text-end pt-0">
            <div class="row  g-2 justify-content-end">
                <div class="col-auto ">
                    <div class="input-group">
                        <input type="text" id="search_text" name="text" value="<?= $text ?>" class="form-control" placeholder="Search for...">
                        <button type="submit" class="btn btn-primary" ><i class="fa fa-search" aria-hidden="true"></i> <?= lang('Common.btn_search') ?></button>
                        <button type="button" class="btn btn-secondary clear"><i class="fa fa-refresh" aria-hidden="true"></i> <?= lang('Common.btn_reset') ?></button>
                    </div>
                </div>

                <!-- Grade System Filter (Hidden) -->
                <div class="col-auto" style="display: none;">
                    <select name="grade_system_id" id="field_grade_system_id" class="form-control filter-select">
                        <option value="">All Grade Systems</option>
                        <?php if (!empty($system_filter_list)): ?>
                            <?php foreach ($system_filter_list as $sid => $stitle): ?>
                                <option value="<?= $sid ?>" <?= (isset($selected_system_id) && $selected_system_id == $sid) ? 'selected' : '' ?>>
                                    <?= esc($stitle) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="col-auto"><?= $status_list; ?></div>
                <div class="col-auto"><?= $show_list; ?></div>
                <div class="col-auto">
                    <a href="<?= base_url('examination/grade-systems') ?>" class="btn btn-primary"><i class="fa fa-cog"></i> Manage Grade Systems</a>
                </div>
                <div class="col-auto">
                    <a href="<?= base_url('examination/grade-systems/rules/create/'.$selected_system_id.'') ?>" class=" edit btn  btn-success" ><i class="fa fa-plus" aria-hidden="true"></i> <?= lang('GradeRule.btn_new') ?></a>
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

                    <table id="table" class="table student-table ">

                        <thead>
                            <tr>
                                <th class="pl-0" width="20px"><?= lang('Common.th_sn') ?></th> 
                                <th><?= lang('GradeRule.field_grade_system') ?></th>
                                <th class="text-center"><?= lang('GradeRule.field_title') ?></th>
                                <th class="text-center"><?= lang('GradeRule.field_grade_point') ?></th>
                                <th class="text-center"><?= lang('GradeRule.field_mark_range') ?></th>
                                <th class="text-center" ><?= lang('Common.th_status') ?></th> 
                                <th width="80px" class="text-center">Order</th>
                                <th width="300px" class="text-end" ><?= lang('Common.th_action') ?></th>
                            </tr>
                        </thead>
                        
                        <tbody>
                            <?php 
                            foreach ($items as $key => $item) {
                                $item_id         = $item->id;
                                $token           = $item->token;
                                $title           = $item->title;
                                $system_title    = $item->system_title ?? '-';
                                $grade_point     = $item->grade_point;
                                $mark_range      = $item->mark_from . ' - ' . $item->mark_to;
                                $field_order     = $item->field_order ?? 0;

                                $edit_link       = base_url('examination/grade-systems/rules/edit/'.$token);

                                echo '<tr id="item_'.$item->id.'" data-token="'.$token.'">
                                    <td class="text-left pl-0">'.++$key.'</td>
                                    <td>'.esc($system_title).'</td>
                                    <td class="text-center">'.$title.'</td>
                                    <td class="text-center">'.$grade_point.'</td>
                                    <td class="text-center">'.$mark_range.'</td>';

                                    $status_html = '';
                                    $restore_btn = '';
                                    $empty_trash_btn = '';
                                    $trash_btn = '';
                                    if($item->status == 0){
                                        $status_html .= '<span class="badge text-bg-warning"><i class="fa fa-times-circle"></i> '.$status_array[$item->status].'</span>';
                                    }
                                    if($item->status == 1){
                                        $status_html .= '<span class="badge text-bg-success"><i class="fa fa-check-circle"></i> '.$status_array[$item->status].'</span>';
                                        $trash_btn .= '<button type="button" data-token="'.$token.'" class=" trash btn btn-sm btn-danger mb-1" title="'.lang('Common.text_trash').'"><i class="fa fa-trash"></i> '.lang('Common.text_trash').'</button>';
                                    }
                                    if($item->status == 2){
                                        $status_html .= '<span class="badge text-bg-danger"><i class="fa fa-ban"></i> '.$status_array[$item->status].'</span>';
                                        $restore_btn .= '<button type="button" data-token="'.$token.'" class=" restore btn btn-sm btn-success mb-1" title="'.lang('Common.text_restore').'"><i class="fa fa-check-circle"></i> '.lang('Common.text_restore').'</button>';
                                        $empty_trash_btn .= '<button type="button" data-token="'.$token.'" class=" empty_trash btn btn-sm btn-danger mb-1" title="'.lang('Common.empty_trash').'"><i class="fa fa-trash"></i> '.lang('Common.empty_trash').'</button>';
                                    }

                                    echo '<td class="text-center">'.$status_html.'</td>';
                                    echo '<td class="text-center">'.$field_order.'</td>';
                                    echo'<td class="text-end pr-0" >
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
    $(document).on('click', '.clear', function(e) {
        $("#search_text").val('');
        $("#field_status").val('');
        $("#field_show_list").val('');
        $("#field_grade_system_id").val('');
        $('#grade_rule_search').submit();
    })

    $('#field_status').on('change', function() { $('#grade_rule_search').submit(); });
    $('#field_show_list').on('change', function() { $('#grade_rule_search').submit(); });
    $('#field_grade_system_id').on('change', function() { $('#grade_rule_search').submit(); });
    
    var csrfToken = $('#csrf_token').val();
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': csrfToken } });

    $(document).on('click', '.clear', function(e) {
        $("#search_text").val('');
        $("#field_status").val('');
        $("#field_grade_system_id").val('');
        $('html, body').animate({ scrollTop: $("body").offset().top }, 2000);
        $("#search_text").focus();
    });

    $(document).on('click', '.trash', function(e) { 
        var token = $(this).data("token");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.trash_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/grade-systems/rules/trash'); ?>/' + token,
                data: { token: token },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.empty_trash', function(e) { 
        var token = $(this).data("token");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.empty_trash_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/grade-systems/rules/empty-trash'); ?>',
                data: { token: token },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });

    $(document).on('click', '.restore', function(e) { 
        var token = $(this).data("token");
        var currentRow = $(this);
        var confirmation = confirm('<?= lang('Common.restore_warning_message') ?>');
        if(confirmation) {
            $('#result').html('<div class="text-center"><div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div></div>');
            $.ajax({
                type: "post", dataType: "json",
                url: '<?= base_url('examination/grade-systems/rules/restore'); ?>/' + token,
                data: { token: token },
                headers: { 'X-CSRF-TOKEN': csrfToken },
                success: function(response, status, xhr) {
                    $("#result").html(response.html);
                    csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    $('#csrf_token').val(csrfToken);
                    if(response.status == true) { currentRow.parents('tr').remove(); }
                }
            });
        }
    });
});
</script>