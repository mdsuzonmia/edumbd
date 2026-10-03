<?= form_open('examination/admit-cards/generate', ['id' => 'admit_card_form']) ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>">
<div class="row mb-3"><div class="col-sm-8"><h3 class="text-secondary mb-0"><i class="bi bi-person-badge"></i> <?= esc(lang('AdmitCard.generate')) ?></h3></div><div class="col-sm-4 text-end"><a href="<?= base_url('examination/admit-cards') ?>" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> <?= esc(lang('AdmitCard.back')) ?></a></div></div>
<?= get_system_message() ?>
<div id="ajax_message"></div>
<div class="card mb-3"><div class="card-header"><strong><?= esc(lang('AdmitCard.configuration')) ?></strong></div><div class="card-body"><div class="row">
<div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.school')) ?></label><select name="school_id" id="school_id" class="form-select" required><option value="">--</option><?php foreach ($school_list as $id => $name): ?><option value="<?= (int) $id ?>" <?= (int)$default_school_id===(int)$id?'selected':'' ?>><?= esc($name) ?></option><?php endforeach ?></select></div>
<div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.session')) ?></label><select name="session_id" id="session_id" class="form-select" required></select></div>
<div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.exam')) ?></label><select name="exam_id" id="exam_id" class="form-select" required></select></div>
<div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.title')) ?></label><input name="title" class="form-control" value="<?= esc(old('title') ?: lang('AdmitCard.admit_card')) ?>" required></div>
<div class="col-md-6 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.classes')) ?></label><select name="class_ids[]" id="class_ids" class="form-select" multiple size="5" required></select></div>
<div class="col-md-6 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.sections')) ?></label><select name="section_ids[]" id="section_ids" class="form-select" multiple size="5"></select></div>
<div class="col-12 mb-3"><label class="form-label"><?= esc(lang('AdmitCard.instructions')) ?></label><textarea name="instructions" class="form-control" rows="3"><?= esc(old('instructions') ?: 'Students must carry this Admit Card and report at least 30 minutes before the examination.') ?></textarea></div>
<?php foreach (['show_student_photo'=>'show_photo','show_student_id'=>'show_student_id','show_registration_no'=>'show_registration','show_exam_time'=>'show_exam_time','show_room'=>'show_room','show_seat'=>'show_seat','show_qr_code'=>'show_qr'] as $name=>$label): ?><div class="col-md-4 mb-2"><label><input type="checkbox" name="<?= $name ?>" value="1" checked> <?= esc(lang('AdmitCard.'.$label)) ?></label></div><?php endforeach ?>
<div class="col-md-6 mt-2"><label><input type="checkbox" name="require_seat_plan" value="1"> <?= esc(lang('AdmitCard.require_seat')) ?></label></div>
</div></div></div>
<div class="card"><div class="card-header d-flex justify-content-between"><strong><?= esc(lang('AdmitCard.students')) ?></strong><button type="button" id="load_students" class="btn btn-primary btn-sm"><i class="fa fa-search"></i> <?= esc(lang('AdmitCard.load_students')) ?></button></div><div class="card-body"><div class="table-responsive"><table class="table table-sm table-bordered"><thead><tr><th><input type="checkbox" id="select_all"></th><th><?= esc(lang('AdmitCard.roll')) ?></th><th><?= esc(lang('AdmitCard.student_id')) ?></th><th><?= esc(lang('AdmitCard.student_name')) ?></th><th><?= esc(lang('AdmitCard.class')) ?></th><th><?= esc(lang('AdmitCard.section')) ?></th></tr></thead><tbody id="student_rows"><tr><td colspan="6" class="text-center text-muted"><?= esc(lang('AdmitCard.no_students')) ?></td></tr></tbody></table></div><div class="text-end"><button class="btn btn-success" type="submit"><i class="fa fa-id-card"></i> <?= esc(lang('AdmitCard.generate')) ?></button></div></div></div>
<?= form_close() ?>
<script>
$(function(){
var base=<?= json_encode(rtrim(base_url(),'/').'/examination/admit-cards/') ?>,csrf=$('#csrf_token').val(),allExams=[];
function esc(v){return $('<div>').text(v==null?'':v).html();} function token(x){csrf=x.getResponseHeader('X-CSRF-TOKEN')||csrf;}
function options(id,rows){var s=$(id).empty();$.each(rows||[],function(_,r){s.append($('<option>').val(r.id).text(r.title));});}
function filterExams(){var y=parseInt($('#session_id').val(),10)||0;options('#exam_id',$.grep(allExams,function(e){return e.year_id===y;}));}
function fail(x){token(x);var r=x.responseJSON||{};$('#ajax_message').html('<div class="alert alert-danger">'+esc(r.message||'Request failed.')+'</div>');}
function academic(){var school=$('#school_id').val();if(!school)return;$.ajax({method:'POST',url:base+'academic-data',data:{school_id:school},headers:{'X-CSRF-TOKEN':csrf},dataType:'json'}).done(function(r,s,x){token(x);options('#session_id',r.years);allExams=r.exams||[];options('#class_ids',r.classes);options('#section_ids',r.sections);filterExams();}).fail(fail);}
$('#school_id').on('change',academic);$('#session_id').on('change',filterExams);
$('#load_students').on('click',function(){$.ajax({method:'POST',url:base+'students',data:$('#admit_card_form').serialize(),headers:{'X-CSRF-TOKEN':csrf},dataType:'json'}).done(function(r,s,x){token(x);var b=$('#student_rows').empty();if(!(r.students||[]).length)b.append('<tr><td colspan="6" class="text-center text-muted"><?= esc(lang('AdmitCard.no_students'),'js') ?></td></tr>');$.each(r.students||[],function(_,st){b.append('<tr><td><input class="student-check" type="checkbox" name="student_ids[]" value="'+st.student_id+'" checked></td><td>'+esc(st.roll_no||'-')+'</td><td>'+esc(st.student_code||'-')+'</td><td>'+esc(st.student_name)+'</td><td>'+esc(st.class_name||'-')+'</td><td>'+esc(st.section_name||'-')+'</td></tr>');});$('#select_all').prop('checked',true);}).fail(fail);});
$('#select_all').on('change',function(){$('.student-check').prop('checked',this.checked);});if($('#school_id').val())academic();
});
</script>
