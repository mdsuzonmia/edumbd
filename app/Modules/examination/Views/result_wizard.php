<?php
ob_start();
$labels=['Class','Exam','Subjects','Students','Marks','Result'];
$action=site_url('examination/result-wizard/save/'.$step);
$preview=json_decode((string)$progress->student_preview,true)?:[];
$demoEligible=count($enrollments)<=$demo_limit;
?>
<style>
.rw{max-width:1150px;margin:auto}html[lang="bn"] .rw{font-family:"Noto Sans Bengali","Hind Siliguri",sans-serif}.rw-card{border:0;border-radius:20px;box-shadow:0 8px 28px rgba(31,50,81,.09)}.rw-progress{display:grid;grid-template-columns:repeat(6,1fr);gap:8px}.rw-progress a{text-decoration:none;color:#667085;text-align:center;font-size:13px}.rw-dot{width:38px;height:38px;border-radius:50%;display:grid;place-items:center;background:#e8edf4;margin:0 auto 6px;font-weight:800}.rw-progress .active .rw-dot,.rw-progress .done .rw-dot{background:#2457a6;color:#fff}.choice{border:2px solid #e3e9f2;border-radius:14px;padding:14px;display:block;margin-bottom:10px}.big-btn{min-height:52px;border-radius:12px;font-size:17px;font-weight:700}.marks-wrap{overflow:auto;max-height:62vh}.marks-table{min-width:760px}.marks-table th{position:sticky;top:0;background:#f5f8fc;z-index:2}.marks-table input{min-width:85px;height:44px;text-align:center}.quick-box{background:#f7f9fc;border-radius:14px;padding:18px}.result-summary{font-size:18px}.error-box{background:#fff3f2;color:#b42318;border-radius:12px;padding:12px}@media(max-width:650px){.rw-progress{overflow-x:auto;grid-template-columns:repeat(6,85px);padding-bottom:8px}.rw-card .card-body{padding:18px}.rw-progress a{font-size:12px}}
</style>
<main class="rw">
 <?=get_system_message()?>
 <div class="card rw-card mb-4"><div class="card-body">
  <div class="rw-progress">
   <?php foreach($labels as $i=>$label):$n=$i+1;?><a href="<?=site_url('examination/result-wizard/step/'.$n)?>" class="<?=$n===$step?'active':($n<$step?'done':'')?>"><span class="rw-dot"><?=$n<$step?'✓':$n?></span><?=$label?></a><?php endforeach?>
  </div>
 </div></div>
 <div class="card rw-card"><div class="card-body p-lg-4">
  <h2 class="fw-bold mb-1">Step <?=$step?> — <?=$labels[$step-1]?></h2>
  <p class="text-muted mb-4">Your progress is saved automatically, so you can continue from here later.</p>
  <?php if(!empty($pricing)&&$step>=4):?><div class="alert alert-info d-flex flex-wrap justify-content-between align-items-center gap-2"><div><strong>Estimated Result Service bill</strong><br>Students: <?=number_format($pricing['student_count'])?> · Applied rate: ৳<?=number_format($pricing['applied_rate'],2)?><?php if(!empty($pricing['details']['minimum_applied'])):?> · Minimum charge applies<?php endif?></div><div class="fs-3 fw-bold">Total: ৳<?=number_format($pricing['subtotal'],2)?></div></div><?php endif?>

 <?php if($step===1):?>
  <?=form_open($action)?>
  <label class="form-label fw-bold">Select a class</label>
  <?php foreach($classes as $c):?><label class="choice"><input type="radio" name="class_id" value="<?=$c->id?>" <?=$progress->class_id==$c->id?'checked':''?>> <strong><?=esc($c->title)?></strong></label><?php endforeach?>
  <div class="quick-box mt-3"><label class="form-label fw-bold">Or enter a new class name</label><input class="form-control form-control-lg" name="new_class" placeholder="e.g. Grade Six"></div>
  <div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label fw-bold">Default Section</label><select class="form-select form-select-lg" name="section_id" required><?php foreach($sections as $section):?><option value="<?=$section->id?>" <?=$progress->section_id==$section->id?'selected':''?>><?=esc($section->title)?></option><?php endforeach?></select></div><div class="col-md-6"><label class="form-label fw-bold">Default Category</label><select class="form-select form-select-lg" name="category_id" required><?php foreach($categories as $category):?><option value="<?=$category->id?>" <?=$progress->category_id==$category->id?'selected':''?>><?=esc($category->title)?></option><?php endforeach?></select></div></div>
  <p class="small text-muted mt-2 mb-0">Class roll, section, and category are always enabled for Bangladesh-focused schools.</p>
  <div class="text-end mt-4"><button class="btn btn-primary big-btn px-5">Next step →</button></div><?=form_close()?>

 <?php elseif($step===2):?>
  <?=form_open($action)?>
  <label class="form-label fw-bold">Select an exam</label>
  <?php foreach($exams as $e):?><label class="choice"><input type="radio" name="exam_id" value="<?=$e->id?>" <?=$progress->exam_id==$e->id?'checked':''?>> <strong><?=esc($e->title)?></strong></label><?php endforeach?>
  <div class="quick-box mt-3"><label class="form-label fw-bold">Or enter an exam name</label><input class="form-control form-control-lg" name="new_exam" list="exam-names" placeholder="e.g. Half-yearly Examination"><datalist id="exam-names"><option value="First Term Examination"><option value="Half-yearly Examination"><option value="Annual Examination"></datalist><small class="text-muted">The current academic year will be used automatically.</small></div>
  <div class="d-flex justify-content-between mt-4"><a class="btn btn-light big-btn" href="<?=site_url('examination/result-wizard/step/1')?>">← Back</a><button class="btn btn-primary big-btn px-5">Next step →</button></div><?=form_close()?>

 <?php elseif($step===3):?>
  <?=form_open($action)?>
  <label class="form-label fw-bold">Select subjects for the result</label><div class="row">
  <?php foreach($all_subjects as $s):?><div class="col-md-6"><label class="choice"><input type="checkbox" name="subject_ids[]" value="<?=$s->id?>" <?=in_array((int)$s->id,$subject_ids,true)?'checked':''?>> <strong><?=esc($s->title)?></strong></label></div><?php endforeach?></div>
  <div class="quick-box mt-3"><label class="form-label fw-bold">Quickly add new subjects</label><textarea class="form-control" name="new_subjects" rows="3" placeholder="Enter one subject per line&#10;Bangla&#10;English&#10;Mathematics"></textarea></div>
  <details class="mt-3"><summary class="fw-bold text-primary" style="cursor:pointer">Advanced Settings</summary><div class="pt-3"><a href="<?=site_url('examination/subjects')?>">Combined paper, optional/4th subject, and grading settings</a><p class="small text-muted">Existing advanced rules will remain unchanged. New subjects created here will use 100 full marks and 33 pass marks.</p></div></details>
  <div class="d-flex justify-content-between mt-4"><a class="btn btn-light big-btn" href="<?=site_url('examination/result-wizard/step/2')?>">← Back</a><button class="btn btn-primary big-btn px-5">Next step →</button></div><?=form_close()?>

 <?php elseif($step===4):?>
  <div class="row g-4"><div class="col-lg-7"><div class="quick-box border border-primary">
   <span class="badge bg-primary mb-2">Easiest option</span><h4 class="fw-bold">A. Add students with Excel</h4>
   <a class="btn btn-outline-primary big-btn me-2 mb-2" href="<?=site_url('examination/result-wizard/student-template')?>">1. Download Excel template</a>
   <?=form_open_multipart('examination/result-wizard/student-preview',['class'=>'mt-3'])?><label class="form-label fw-bold">2. Upload the completed CSV file</label><input class="form-control form-control-lg" type="file" name="student_file" accept=".csv" required><small class="text-muted">Open the template in Excel, complete it, and save it as a CSV file.</small><button class="btn btn-primary big-btn w-100 mt-3">Validate and preview</button><?=form_close()?>
  </div></div><div class="col-lg-5"><div class="quick-box h-100"><h4 class="fw-bold">B. Enter students manually</h4><p class="small text-muted">One per line: name, roll, section, category</p><?=form_open($action)?><textarea name="manual_students" class="form-control" rows="8" placeholder="Rahim Uddin, 1, A, Regular&#10;Karima Akter, 2, A, Regular"></textarea><button class="btn btn-outline-primary big-btn w-100 mt-3">Save and continue</button><?=form_close()?></div></div></div>
  <?php if($preview):?><div class="mt-4"><h4 class="fw-bold">Review before saving</h4><div class="table-responsive"><table class="table table-bordered"><thead><tr><th>Name</th><th>Roll</th><th>Section</th><th>Category</th></tr></thead><tbody><?php foreach($preview as $r):?><tr><td><?=esc($r[0])?></td><td><?=esc($r[1]??'')?></td><td><?=esc($r[2]??'')?></td><td><?=esc($r[3]??'')?></td></tr><?php endforeach?></tbody></table></div><?=form_open('examination/result-wizard/student-import')?><button class="btn btn-success big-btn w-100"><?=esc(lang('ResultWizard.save_students', [count($preview)]))?></button><?=form_close()?></div><?php endif?>
  <div class="mt-4"><a class="btn btn-light big-btn" href="<?=site_url('examination/result-wizard/step/3')?>">← Back</a></div>

 <?php elseif($step===5):?>
  <ul class="nav nav-pills mb-3"><li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#excel">Excel Import</button></li><li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#manual">Enter marks manually</button></li></ul>
  <div class="tab-content"><div class="tab-pane fade show active" id="excel"><div class="quick-box"><h4 class="fw-bold">Add marks with Excel</h4><a class="btn btn-outline-primary big-btn" href="<?=site_url('examination/result-wizard/marks-template')?>">1. Download marks template</a><?=form_open_multipart('examination/result-wizard/marks-import',['class'=>'mt-3'])?><input class="form-control form-control-lg" type="file" name="marks_file" accept=".csv" required><small class="text-muted">Invalid marks or values outside 0–100 will not be saved.</small><button class="btn btn-primary big-btn w-100 mt-3">Validate and save marks</button><?=form_close()?></div></div>
  <div class="tab-pane fade" id="manual"><?=form_open($action)?><div class="marks-wrap"><table class="table table-bordered marks-table"><thead><tr><th>Roll</th><th>Student</th><?php foreach($subjects as $s):?><th><?=esc($s->title)?></th><?php endforeach?></tr></thead><tbody><?php foreach($enrollments as $e):?><tr><td><?=esc($e->roll_no)?></td><td><?=esc(trim($e->first_name.' '.$e->middle_name.' '.$e->last_name))?></td><?php foreach($subjects as $s):$m=(new \App\Modules\examination\Models\MarkModel())->where(['exam_id'=>$progress->exam_id,'student_id'=>$e->student_id,'subject_id'=>$s->id])->first();?><td><input type="number" min="0" max="100" step="0.01" class="form-control mark-cell" name="marks[<?=$e->student_id?>][<?=$s->id?>]" value="<?=esc($m->obtained_mark??'')?>"></td><?php endforeach?></tr><?php endforeach?></tbody></table></div><button class="btn btn-primary big-btn w-100 mt-3">Save marks and review</button><?=form_close()?></div></div>
  <div class="mt-4"><a class="btn btn-light big-btn" href="<?=site_url('examination/result-wizard/step/4')?>">← Back</a></div>

  <?php else:?>
  <?=form_open($action,['class'=>'quick-box mb-4'])?><h4 class="fw-bold">Select a result service</h4><div class="row g-2"><div class="col-md-6"><label class="choice h-100"><input type="radio" name="service_mode" value="SELF_SERVICE" <?=($progress->service_mode??'SELF_SERVICE')==='SELF_SERVICE'?'checked':''?>> <strong>SELF_SERVICE</strong><br><small>Create the result yourself</small></label></div><div class="col-md-6"><label class="choice h-100"><input type="radio" name="service_mode" value="MANAGED_SERVICE" <?=($progress->service_mode??'')==='MANAGED_SERVICE'?'checked':''?>> <strong>MANAGED_SERVICE</strong><br><small>The Edum team will help create the result</small></label></div></div><button class="btn btn-outline-primary">Save service</button><?=form_close()?>
  <div class="row g-3 result-summary mb-4"><div class="col-md-4"><div class="quick-box"><strong>Total students</strong><div class="display-6"><?=count($enrollments)?></div></div></div><div class="col-md-4"><div class="quick-box"><strong>Subjects</strong><div class="display-6"><?=count($subjects)?></div></div></div><div class="col-md-4"><div class="quick-box <?=$missing_marks?'error-box':''?>"><strong>Missing marks</strong><div class="display-6"><?=$missing_marks?></div></div></div></div>
  <?php if($missing_marks):?><div class="alert alert-warning">Some marks are still missing. Return to the marks step and complete them before generating the result.</div><?php endif?>
  <?php if(!$is_paid):?><div class="alert alert-secondary"><strong>Demo / Preview</strong> — <?=$demoEligible?'Your student count is within the demo limit. You can preview the result now.':'Final PDF, Print, and Publish will be available after payment.'?></div><?php endif?>
  <button id="generate-result" class="btn btn-primary big-btn w-100 py-3" <?=$missing_marks?'disabled':''?>><?=$is_paid?'Generate result':'Generate demo / preview'?></button><div id="generate-message" class="mt-3"></div>
  <?php if(!$is_paid&&$order):?><a class="btn btn-success big-btn w-100 py-3 mt-3" href="<?=site_url('school-owner/billing/'.$order->token)?>">Pay and complete result — ৳<?=number_format($order->total,2)?></a><?php endif?>
  <div id="result-links" class="row g-2 mt-3 <?=($has_results&&($demoEligible||$is_paid))?'':'d-none'?>"><div class="col-md"><a class="btn btn-success big-btn w-100" href="<?=site_url('examination/reports/individual-result')?>"><?=$is_paid?'Result Sheet':'View demo result'?></a></div><?php if($is_paid):?><div class="col-md"><a class="btn btn-success big-btn w-100" href="<?=site_url('examination/reports/transcript')?>">Transcript</a></div><div class="col-md"><a class="btn btn-success big-btn w-100" href="<?=site_url('examination/reports/tabulation-sheet')?>">Tabulation Sheet</a></div><div class="col-md"><a class="btn btn-success big-btn w-100" href="<?=site_url('examination/reports/merit-list')?>">Merit List</a></div><div class="col-md"><a class="btn btn-outline-success big-btn w-100" href="<?=site_url('examination/reports/individual-result')?>">Print / PDF</a></div><?php endif?></div>
  <div class="mt-4"><a class="btn btn-light big-btn" href="<?=site_url('examination/result-wizard/step/5')?>">← Edit marks</a></div>
  <script>document.getElementById('generate-result')?.addEventListener('click',async function(){this.disabled=true;this.textContent='Generating result…';const body=new URLSearchParams({school_id:'<?=session('school_id')?>',exam_id:'<?=$progress->exam_id?>',class_id:'<?=$progress->class_id?>',section_id:'0',year_id:'<?=$progress->year_id?>',preview_only:'<?=$is_paid?'0':'1'?>','<?=csrf_token()?>':'<?=csrf_hash()?>'});try{const r=await fetch('<?=site_url('examination/results/generate')?>',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},body});const j=await r.json();document.getElementById('generate-message').innerHTML='<div class="alert alert-'+(j.status?'success':'danger')+'">'+(j.status?'<?=$is_paid?'Final result generated successfully.':'Demo result generated. Use the button below to view it.'?>':j.message)+'</div>';if(j.status&&<?=($is_paid||$demoEligible)?'true':'false'?>)document.getElementById('result-links').classList.remove('d-none');if(!j.status)this.disabled=false}catch(e){document.getElementById('generate-message').innerHTML='<div class="alert alert-danger">The result could not be generated. Please try again.</div>';this.disabled=false}this.textContent='<?=$is_paid?'Generate result':'Generate demo / preview'?>'});</script>
 <?php endif?>
 </div></div>
</main>
<script>document.querySelectorAll('.mark-cell').forEach((el,i,a)=>el.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();a[i+1]?.focus()}}));</script>
<?php
$resultWizardHtml = ob_get_clean();
$translations = lang('ResultWizard.translations');
echo is_array($translations) && $translations !== [] ? strtr($resultWizardHtml, $translations) : $resultWizardHtml;
?>
