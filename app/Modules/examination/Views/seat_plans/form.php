<?= form_open('examination/seat-plans/generate', ['id' => 'seat_plan_form', 'method' => 'post']) ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>">
<input type="hidden" name="random_seed" value="<?= esc(bin2hex(random_bytes(16))) ?>">

<div class="row mb-3"><div class="col-sm-6"><h3 class="text-secondary mb-0"><i class="bi bi-grid-3x3-gap"></i> <?= esc(lang('SeatPlan.page_title_new')) ?></h3></div>
<div class="col-sm-6 text-end"><a href="<?= base_url('examination/seat-plans') ?>" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> <?= esc(lang('SeatPlan.back_to_list')) ?></a></div></div>

<?= get_system_message() ?>
<div id="ajax_message"></div>

<style>
.sp-wizard{max-width:1180px;margin:auto}.sp-steps{display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:20px}.sp-step{border:0;background:#eef2f7;color:#667085;border-radius:14px;padding:12px 8px;font-weight:700}.sp-step .number{display:inline-grid;place-items:center;width:30px;height:30px;border-radius:50%;background:#d8e0eb;margin-right:6px}.sp-step.active{background:#2457a6;color:#fff}.sp-step.active .number,.sp-step.done .number{background:#fff;color:#2457a6}.sp-step.done{background:#dce9ff;color:#2457a6}.wizard-panel{display:none}.wizard-panel.active{display:block}.wizard-actions{display:flex;justify-content:space-between;gap:12px;position:sticky;bottom:10px;z-index:20;background:rgba(255,255,255,.96);border:1px solid #e5e9f0;border-radius:14px;padding:12px;box-shadow:0 8px 24px rgba(31,50,81,.1)}@media(max-width:700px){.sp-steps{grid-template-columns:repeat(4,minmax(120px,1fr));overflow-x:auto}.sp-step{white-space:nowrap}.wizard-actions{bottom:4px}}
</style>
<div class="sp-wizard">
<div class="sp-steps" aria-label="Seat plan creation steps">
    <button type="button" class="sp-step active" data-step="1"><span class="number">1</span>Exam &amp; Scope</button>
    <button type="button" class="sp-step" data-step="2"><span class="number">2</span>Students</button>
    <button type="button" class="sp-step" data-step="3"><span class="number">3</span>Rooms</button>
    <button type="button" class="sp-step" data-step="4"><span class="number">4</span>Review</button>
</div>

<div class="card mb-3 wizard-panel active" data-wizard-panel="1"><div class="card-header"><strong>1. Exam and student scope</strong></div><div class="card-body"><div class="row">
    <div class="col-md-4 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.school')) ?> *</label>
        <select name="school_id" id="school_id" class="form-control" required><option value=""><?= esc(lang('SeatPlan.select_school')) ?></option>
        <?php foreach (($school_list ?? []) as $id => $name): ?><option value="<?= (int)$id ?>" <?= (int)old('school_id', $default_school_id ?? 0)===(int)$id?'selected':'' ?>><?= esc($name) ?></option><?php endforeach ?></select></div>
    <div class="col-md-4 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.session')) ?> *</label><select name="session_id" id="session_id" class="form-control" required><option value=""><?= esc(lang('SeatPlan.select_session')) ?></option></select></div>
    <div class="col-md-4 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.exam')) ?> *</label><select name="exam_id" id="exam_id" class="form-control" required><option value=""><?= esc(lang('SeatPlan.select_exam')) ?></option></select></div>
    <div class="col-md-6 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.title')) ?> *</label><input name="title" id="title" maxlength="255" class="form-control" required value="<?= esc(old('title')) ?>"></div>
    <div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.exam_date')) ?></label><input type="date" name="exam_date" id="exam_date" class="form-control" value="<?= esc(old('exam_date')) ?>"></div>
    <div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.shift')) ?></label><select name="shift_id" id="shift_id" class="form-control"><option value=""><?= esc(lang('SeatPlan.optional_all')) ?></option></select></div>
    <div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.department')) ?></label><select name="department_id" id="department_id" class="form-control"><option value=""><?= esc(lang('SeatPlan.optional_all')) ?></option></select></div>
    <div class="col-md-6 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.classes')) ?> *</label><select name="class_ids[]" id="class_ids" class="form-control" multiple size="5" required></select></div>
    <div class="col-md-6 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.sections')) ?></label><select name="section_ids[]" id="section_ids" class="form-control" multiple size="5"></select><div class="form-text"><?= esc(lang('SeatPlan.optional_all')) ?></div></div>
</div></div></div>

<div class="card mb-3 wizard-panel" data-wizard-panel="2"><div class="card-header d-flex justify-content-between align-items-center"><strong>2. <?= esc(lang('SeatPlan.students')) ?></strong><button type="button" id="load_students" class="btn btn-sm btn-primary"><i class="fa fa-users"></i> <?= esc(lang('SeatPlan.load_students')) ?></button></div>
<div class="card-body"><p id="student_hint" class="text-muted"><?= esc(lang('SeatPlan.select_students_hint')) ?></p>
<div id="student_table_wrap" class="table-responsive d-none"><table class="table table-sm table-bordered"><thead><tr><th><input type="checkbox" id="select_all_students" checked></th><th><?= esc(lang('SeatPlan.roll')) ?></th><th><?= esc(lang('SeatPlan.student_id')) ?></th><th><?= esc(lang('SeatPlan.student_name')) ?></th><th><?= esc(lang('SeatPlan.class')) ?></th><th><?= esc(lang('SeatPlan.section')) ?></th></tr></thead><tbody id="student_rows"></tbody></table></div></div></div>

<div class="card mb-3 wizard-panel" data-wizard-panel="3"><div class="card-header"><strong>3. <?= esc(lang('SeatPlan.rooms')) ?></strong></div><div class="card-body"><p id="room_hint" class="text-muted"><?= esc(lang('SeatPlan.select_rooms_hint')) ?></p><div class="row" id="room_rows"></div></div></div>

<div class="card mb-3 wizard-panel" data-wizard-panel="4"><div class="card-header"><strong>4. Allocation settings and review</strong></div><div class="card-body"><div class="row align-items-end">
    <div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.allocation_method')) ?></label><select name="allocation_method" class="form-control"><option value="sequential"><?= esc(lang('SeatPlan.sequential')) ?></option><option value="mixed_class"><?= esc(lang('SeatPlan.mixed_class')) ?></option><option value="random"><?= esc(lang('SeatPlan.random')) ?></option></select></div>
    <div class="col-md-3 mb-3"><label class="form-label"><?= esc(lang('SeatPlan.seat_format')) ?></label><select name="seat_number_format" class="form-control"><option value="numeric"><?= esc(lang('SeatPlan.numeric')) ?></option><option value="alpha_numeric"><?= esc(lang('SeatPlan.alpha_numeric')) ?></option></select></div>
    <div class="col-md-6 mb-3"><div class="row text-center"><div class="col"><div class="border rounded p-2"><strong id="selected_count">0</strong><div class="small"><?= esc(lang('SeatPlan.selected_students')) ?></div></div></div><div class="col"><div class="border rounded p-2"><strong id="seat_count">0</strong><div class="small"><?= esc(lang('SeatPlan.available_seats')) ?></div></div></div><div class="col"><div id="balance_box" class="border rounded p-2"><strong id="balance_count">0</strong><div id="balance_label" class="small"><?= esc(lang('SeatPlan.unused_seats')) ?></div></div></div></div></div>
</div><div class="text-end"><button type="button" id="preview_button" class="btn btn-primary" disabled><i class="fa fa-eye"></i> <?= esc(lang('SeatPlan.preview')) ?></button> <button type="submit" id="save_button" class="btn btn-success" disabled><i class="fa fa-save"></i> <?= esc(lang('SeatPlan.generate_save')) ?></button></div></div></div>

<div class="wizard-actions mb-3"><button type="button" id="wizard_back" class="btn btn-outline-secondary" disabled><i class="fa fa-arrow-left"></i> Back</button><div class="small text-muted align-self-center" id="wizard_status">Step 1 of 4</div><button type="button" id="wizard_next" class="btn btn-primary">Next <i class="fa fa-arrow-right"></i></button></div>

<div id="preview_wrap"></div>
</div>
<?= form_close() ?>

<script>
$(function(){
    var csrfToken=$('#csrf_token').val(), base=<?= json_encode(rtrim(base_url(),'/').'/examination/seat-plans/') ?>;
    var wizardStep=1, highestStep=1;
    function escapeHtml(v){return $('<div>').text(v==null?'':v).html();}
    function updateToken(xhr){csrfToken=xhr.getResponseHeader('X-CSRF-TOKEN')||csrfToken;$('#csrf_token').val(csrfToken);}
    function ajaxError(xhr){updateToken(xhr);var r=xhr.responseJSON||{};$('#ajax_message').html('<div class="alert alert-danger">'+escapeHtml(r.message||'Request failed.')+'</div>');}
    function fillSelect(id,rows,placeholder){var $s=$(id).empty();if(placeholder!==null)$s.append($('<option>').val('').text(placeholder));$.each(rows||[],function(_,r){$s.append($('<option>').val(r.id).text(r.title));});}
    function loadAcademic(){var school=$('#school_id').val();$('#student_rows,#room_rows').empty();$('#student_table_wrap').addClass('d-none');if(!school)return;$.ajax({method:'POST',url:base+'academic-data',data:{school_id:school},headers:{'X-CSRF-TOKEN':csrfToken},dataType:'json'}).done(function(r,s,x){updateToken(x);fillSelect('#session_id',r.years,'<?= esc(lang('SeatPlan.select_session'), 'js') ?>');fillSelect('#exam_id',[], '<?= esc(lang('SeatPlan.select_exam'), 'js') ?>');fillSelect('#class_ids',r.classes,null);fillSelect('#section_ids',r.sections,null);fillSelect('#shift_id',r.shifts,'<?= esc(lang('SeatPlan.optional_all'), 'js') ?>');fillSelect('#department_id',r.departments,'<?= esc(lang('SeatPlan.optional_all'), 'js') ?>');$('#exam_id').data('all',r.exams||[]);$.each(r.rooms||[],function(_,room){$('#room_rows').append('<div class="col-md-4 mb-2"><label class="border rounded p-3 d-block h-100"><input class="room-check" type="checkbox" name="room_ids[]" value="'+room.id+'" data-capacity="'+room.capacity+'"> <strong>'+escapeHtml(room.room_name)+'</strong> ('+escapeHtml(room.room_no)+')<div class="small text-muted">'+room.rows_count+' × '+room.columns_count+' · '+room.capacity+' seats</div></label></div>');});$('#room_hint').toggleClass('d-none',(r.rooms||[]).length>0);updateCounts();}).fail(ajaxError);}
    function filterExams(){var year=parseInt($('#session_id').val(),10)||0,$exam=$('#exam_id').empty().append($('<option>').val('').text('<?= esc(lang('SeatPlan.select_exam'), 'js') ?>'));$.each($exam.data('all')||[],function(_,e){if(e.year_id===year)$exam.append($('<option>').val(e.id).text(e.title).attr('data-date',e.exam_date||''));});}
    function loadStudents(){$('#ajax_message').empty();$.ajax({method:'POST',url:base+'students',data:$('#seat_plan_form').serialize(),headers:{'X-CSRF-TOKEN':csrfToken},dataType:'json'}).done(function(r,s,x){updateToken(x);var $rows=$('#student_rows').empty();$.each(r.students||[],function(_,st){$rows.append('<tr><td><input class="student-check" type="checkbox" name="student_ids[]" value="'+st.student_id+'" checked></td><td>'+escapeHtml(st.roll_no||'-')+'</td><td>'+escapeHtml(st.student_code||'-')+'</td><td>'+escapeHtml(st.student_name)+'</td><td>'+escapeHtml(st.class_name||'-')+'</td><td>'+escapeHtml(st.section_name||'-')+'</td></tr>');});$('#student_table_wrap').toggleClass('d-none',(r.students||[]).length===0);$('#student_hint').text((r.students||[]).length?'':'<?= esc(lang('SeatPlan.no_students'), 'js') ?>');$('#select_all_students').prop('checked',true);updateCounts();}).fail(ajaxError);}
    function updateCounts(){var students=$('.student-check:checked').length,seats=0;$('.room-check:checked').each(function(){seats+=parseInt($(this).data('capacity'),10)||0;});var balance=seats-students;$('#selected_count').text(students);$('#seat_count').text(seats);$('#balance_count').text(Math.abs(balance));$('#balance_label').text(balance<0?'<?= esc(lang('SeatPlan.shortage'), 'js') ?>':'<?= esc(lang('SeatPlan.unused_seats'), 'js') ?>');$('#balance_box').toggleClass('border-danger text-danger',balance<0);var valid=students>0&&seats>=students;$('#preview_button,#save_button').prop('disabled',!valid);$('#preview_wrap').empty();}
    function wizardError(message){$('#ajax_message').html('<div class="alert alert-warning">'+escapeHtml(message)+'</div>');window.scrollTo({top:0,behavior:'smooth'});}
    function stepValid(step){
        if(step===1){if(!$('#school_id').val()||!$('#session_id').val()||!$('#exam_id').val()||!$.trim($('#title').val())||!($('#class_ids').val()||[]).length){wizardError('Please select the school, session, exam and at least one class, then enter a title.');return false;}}
        if(step===2&&$('.student-check:checked').length===0){wizardError('Load students and select at least one student.');return false;}
        if(step===3){var students=$('.student-check:checked').length,seats=0;$('.room-check:checked').each(function(){seats+=parseInt($(this).data('capacity'),10)||0;});if(!$('.room-check:checked').length){wizardError('Select at least one examination room.');return false;}if(seats<students){wizardError('The selected rooms do not have enough seats for the selected students.');return false;}}
        return true;
    }
    function showStep(step){wizardStep=Math.max(1,Math.min(4,step));highestStep=Math.max(highestStep,wizardStep);$('.wizard-panel').removeClass('active').filter('[data-wizard-panel="'+wizardStep+'"]').addClass('active');$('.sp-step').each(function(){var n=parseInt($(this).data('step'),10);$(this).toggleClass('active',n===wizardStep).toggleClass('done',n<wizardStep);});$('#wizard_back').prop('disabled',wizardStep===1);$('#wizard_next').toggle(wizardStep<4);$('#wizard_status').text('Step '+wizardStep+' of 4');$('#ajax_message').empty();window.scrollTo({top:0,behavior:'smooth'});}
    $('#school_id').on('change',loadAcademic);$('#session_id').on('change',filterExams);$('#exam_id').on('change',function(){var $o=$(this).find(':selected');if($o.data('date'))$('#exam_date').val($o.data('date'));if(!$('#title').val())$('#title').val($o.text()+' Seat Plan');});$('#load_students').on('click',loadStudents);$(document).on('change','.student-check,.room-check',updateCounts);$('#select_all_students').on('change',function(){$('.student-check').prop('checked',this.checked);updateCounts();});$('#preview_button').on('click',function(){$('#ajax_message').empty();$.ajax({method:'POST',url:base+'preview',data:$('#seat_plan_form').serialize(),headers:{'X-CSRF-TOKEN':csrfToken},dataType:'json'}).done(function(r,s,x){updateToken(x);$('#preview_wrap').html(r.html);}).fail(ajaxError);});
    $('#wizard_next').on('click',function(){if(!stepValid(wizardStep))return;if(wizardStep===1)loadStudents();showStep(wizardStep+1);});
    $('#wizard_back').on('click',function(){showStep(wizardStep-1);});
    $('.sp-step').on('click',function(){var target=parseInt($(this).data('step'),10);if(target<wizardStep||target<=highestStep)showStep(target);});
    $('#seat_plan_form').on('submit',function(e){if(!stepValid(1)||!stepValid(2)||!stepValid(3)){e.preventDefault();return;}$('#save_button').prop('disabled',true).html('<i class="fa fa-spinner fa-spin"></i> Generating...');});
    if($('#school_id').val())loadAcademic();
});
</script>
