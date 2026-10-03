<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>">
<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-grid-3x3-gap"></i> <?= esc($plan->title) ?></h3>
        <div class="text-muted"><?= esc($exam->title??'') ?> · <?= esc($school->name??'') ?></div>
    </div>
    <div class="col-sm-6 text-end">
<?php if ((int)$plan->is_locked && !empty($can_unlock)): ?><button class="btn btn-warning btn-sm plan-action me-2" data-action="unlock" data-confirm="<?= esc(lang('SeatPlan.confirm_unlock')) ?>"><i class="fa fa-unlock"></i> <?= esc(lang('SeatPlan.unlock_plan')) ?></button><?php elseif (!(int)$plan->is_locked && !empty($can_lock)): ?><button class="btn btn-danger btn-sm plan-action me-2" data-action="lock" data-confirm="<?= esc(lang('SeatPlan.confirm_lock')) ?>"><i class="fa fa-lock"></i> <?= esc(lang('SeatPlan.lock_plan')) ?></button><?php endif ?>
<?php if (!(int)$plan->is_locked && !empty($can_generate)): ?><button class="btn btn-secondary btn-sm plan-action me-2" data-action="regenerate" data-confirm="<?= esc(lang('SeatPlan.confirm_regenerate')) ?>"><i class="fa fa-refresh"></i> <?= esc(lang('SeatPlan.regenerate_plan')) ?></button><?php endif ?>
<?php if (!empty($can_print)): ?>
<a href="<?= base_url('examination/seat-plans/seat-slips/' . rawurlencode($plan->token)) ?>" class="btn btn-success btn-sm me-2"><i class="fa fa-th-large"></i> <?= esc(lang('SeatPlan.seat_slips')) ?></a>
<div class="btn-group me-2">
    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false"><i class="fa fa-print"></i> <?= esc(lang('SeatPlan.print_reports')) ?></button>
    <ul class="dropdown-menu dropdown-menu-end">
        <?php foreach (['visual' => 'visual_plan', 'room-list' => 'room_student_list', 'class-list' => 'class_wise_list', 'door-notice' => 'door_notice'] as $reportKey => $reportLabel): ?>
        <li><h6 class="dropdown-header"><?= esc(lang('SeatPlan.' . $reportLabel)) ?></h6></li>
        <li><a class="dropdown-item" target="_blank" rel="noopener" href="<?= base_url('examination/seat-plans/print/' . rawurlencode($plan->token) . '/' . $reportKey) ?>"><i class="fa fa-print me-2"></i><?= esc(lang('SeatPlan.print')) ?></a></li>
        <li><a class="dropdown-item" href="<?= base_url('examination/seat-plans/download-pdf/' . rawurlencode($plan->token) . '/' . $reportKey) ?>"><i class="fa fa-file-pdf-o me-2"></i><?= esc(lang('SeatPlan.download_pdf')) ?></a></li>
        <?php if ($reportKey !== 'door-notice'): ?><li><hr class="dropdown-divider"></li><?php endif ?>
        <?php endforeach ?>
        <li><hr class="dropdown-divider"></li>
        <li><h6 class="dropdown-header"><?= esc(lang('SeatPlan.seat_slips')) ?></h6></li>
        <li><a class="dropdown-item" href="<?= base_url('examination/seat-plans/seat-slips/' . rawurlencode($plan->token)) ?>"><i class="fa fa-th-large me-2"></i><?= esc(lang('SeatPlan.preview_print')) ?></a></li>
        <li><a class="dropdown-item" href="<?= base_url('examination/seat-plans/download-pdf/' . rawurlencode($plan->token) . '/seat-slips') ?>"><i class="fa fa-file-pdf-o me-2"></i><?= esc(lang('SeatPlan.download_pdf')) ?></a></li>
    </ul>
</div>
<?php endif ?>
<a href="<?= base_url('examination/seat-plans') ?>" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> <?= esc(lang('SeatPlan.back_to_list')) ?></a></div></div>
<?= get_system_message() ?>
<div id="adjustment_message"></div>
<div class="row mb-3"><div class="col-md-3"><div class="card"><div class="card-body text-center"><strong><?= count($allocations) ?></strong><div class="small"><?= esc(lang('SeatPlan.students')) ?></div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><strong><?= count($rooms) ?></strong><div class="small"><?= esc(lang('SeatPlan.rooms')) ?></div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><strong><?= esc(ucfirst($plan->allocation_method)) ?></strong><div class="small"><?= esc(lang('SeatPlan.method')) ?></div></div></div></div><div class="col-md-3"><div class="card"><div class="card-body text-center"><strong><?= esc(ucfirst($plan->status)) ?></strong><div class="small"><?= esc(lang('SeatPlan.status')) ?></div></div></div></div></div>
<?php foreach ($rooms as $room): $roomAllocations=array_filter($allocations,static fn($a)=>(int)$a->room_id===(int)$room->room_id); ?>
<div class="card mb-3"><div class="card-header"><strong><?= esc($room->room_name_snapshot) ?> (<?= esc($room->room_no_snapshot) ?>)</strong><span class="float-end"><?= count($roomAllocations) ?>/<?= (int)$room->capacity ?></span></div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th><?= esc(lang('SeatPlan.seat')) ?></th><th><?= esc(lang('SeatPlan.roll')) ?></th><th><?= esc(lang('SeatPlan.student_id')) ?></th><th><?= esc(lang('SeatPlan.student_name')) ?></th><th><?= esc(lang('SeatPlan.class')) ?></th><th><?= esc(lang('SeatPlan.section')) ?></th></tr></thead><tbody>
<?php foreach ($roomAllocations as $allocation): ?><tr><td><strong><?= esc($allocation->seat_no) ?></strong></td><td><?= esc($allocation->roll_no_snapshot??'-') ?></td><td><?= esc($allocation->student_code??'-') ?></td><td><?= esc($allocation->student_name??'-') ?></td><td><?= esc($allocation->class_name??'-') ?></td><td><?= esc($allocation->section_name??'-') ?><?php if (!(int)$plan->is_locked && !empty($can_edit)): ?><button type="button" class="btn btn-sm btn-outline-danger float-end remove-allocation" data-id="<?= (int)$allocation->id ?>"><i class="fa fa-times"></i> <?= esc(lang('SeatPlan.remove')) ?></button><?php endif ?></td></tr><?php endforeach ?>
</tbody></table></div></div></div>
<?php endforeach ?>

<?php if (!(int)$plan->is_locked && !empty($can_edit)): ?>
<div class="card mb-3"><div class="card-header"><strong><?= esc(lang('SeatPlan.manual_adjustment')) ?></strong></div><div class="card-body"><div class="row">
    <div class="col-lg-6 mb-4"><h6><?= esc(lang('SeatPlan.move_student')) ?></h6><div class="row g-2"><div class="col-md-5"><select id="move_allocation" class="form-control"><option value=""><?= esc(lang('SeatPlan.select_student')) ?></option><?php foreach($allocations as $a): ?><option value="<?= (int)$a->id ?>"><?= esc(($a->roll_no_snapshot?:'-').' - '.$a->student_name) ?></option><?php endforeach ?></select></div><div class="col-md-5"><select id="move_position" class="form-control"><option value=""><?= esc(lang('SeatPlan.select_position')) ?></option><?php foreach($seat_positions as $p): ?><option value="<?= esc($p['key']) ?>"><?= esc($p['room_name'].' ('.$p['room_no'].') - '.$p['seat_no']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><button class="btn btn-primary w-100 adjustment-action" data-action="move"><?= esc(lang('SeatPlan.apply')) ?></button></div></div></div>
    <div class="col-lg-6 mb-4"><h6><?= esc(lang('SeatPlan.swap_students')) ?></h6><div class="row g-2"><div class="col-md-5"><select id="swap_first" class="form-control"><option value=""><?= esc(lang('SeatPlan.first_student')) ?></option><?php foreach($allocations as $a): ?><option value="<?= (int)$a->id ?>"><?= esc(($a->seat_no?:'-').' - '.$a->student_name) ?></option><?php endforeach ?></select></div><div class="col-md-5"><select id="swap_second" class="form-control"><option value=""><?= esc(lang('SeatPlan.second_student')) ?></option><?php foreach($allocations as $a): ?><option value="<?= (int)$a->id ?>"><?= esc(($a->seat_no?:'-').' - '.$a->student_name) ?></option><?php endforeach ?></select></div><div class="col-md-2"><button class="btn btn-primary w-100 adjustment-action" data-action="swap"><?= esc(lang('SeatPlan.apply')) ?></button></div></div></div>
    <div class="col-lg-12"><h6><?= esc(lang('SeatPlan.add_student')) ?></h6><div class="row g-2"><div class="col-md-5"><select id="add_student" class="form-control"><option value=""><?= esc(lang('SeatPlan.select_student')) ?></option><?php foreach($unallocated_students as $s): ?><option value="<?= (int)$s->student_id ?>"><?= esc(($s->roll_no?:'-').' - '.trim(implode(' ',array_filter([$s->first_name,$s->middle_name,$s->last_name]))).' ('.$s->class_name.')') ?></option><?php endforeach ?></select></div><div class="col-md-5"><select id="add_position" class="form-control"><option value=""><?= esc(lang('SeatPlan.select_position')) ?></option><?php foreach($seat_positions as $p): ?><option value="<?= esc($p['key']) ?>"><?= esc($p['room_name'].' ('.$p['room_no'].') - '.$p['seat_no']) ?></option><?php endforeach ?></select></div><div class="col-md-2"><button class="btn btn-success w-100 adjustment-action" data-action="add"><?= esc(lang('SeatPlan.apply')) ?></button></div></div></div>
</div></div></div>
<?php endif ?>

<script>
$(function(){
    var csrfToken=$('#csrf_token').val(),base=<?= json_encode(rtrim(base_url(),'/').'/examination/seat-plans/') ?>,token=<?= json_encode($plan->token) ?>;
    function message(ok,text){$('#adjustment_message').html('<div class="alert alert-'+(ok?'success':'danger')+'">'+$('<div>').text(text||'').html()+'</div>');}
    function request(action,data,confirmation){if(confirmation&&!window.confirm(confirmation))return;$.ajax({method:'POST',url:base+action+'/'+encodeURIComponent(token),data:data||{},headers:{'X-CSRF-TOKEN':csrfToken},dataType:'json'}).done(function(r,s,x){csrfToken=x.getResponseHeader('X-CSRF-TOKEN')||csrfToken;message(r.status,r.message);if(r.status)window.location.reload();}).fail(function(x){csrfToken=x.getResponseHeader('X-CSRF-TOKEN')||csrfToken;var r=x.responseJSON||{};message(false,r.message||'Request failed.');});}
    $('.plan-action').on('click',function(){request($(this).data('action'),{},$(this).data('confirm'));});
    $('.adjustment-action').on('click',function(){var action=$(this).data('action'),data={};if(action==='move')data={allocation_id:$('#move_allocation').val(),position:$('#move_position').val()};if(action==='swap')data={first_allocation_id:$('#swap_first').val(),second_allocation_id:$('#swap_second').val()};if(action==='add')data={student_id:$('#add_student').val(),position:$('#add_position').val()};request(action,data);});
    $('.remove-allocation').on('click',function(){request('remove',{allocation_id:$(this).data('id')},<?= json_encode(lang('SeatPlan.confirm_remove')) ?>);});
});
</script>
