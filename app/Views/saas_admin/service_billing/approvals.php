<?= get_system_message() ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div><h2 class="mb-1"><i class="bi bi-check2-square"></i> Service Payment Approvals</h2><p class="text-muted mb-0">Verify submitted transactions before enabling the paid service.</p></div>
    <a class="btn btn-outline-primary" href="<?= site_url('saas-admin/service-billing') ?>">Pricing &amp; all orders</a>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Waiting for approval</div><div class="display-6 fw-bold"><?= count($orders) ?></div></div></div></div>
    <div class="col-sm-6 col-lg-3"><div class="card border-0 shadow-sm"><div class="card-body"><div class="text-muted">Pending amount</div><div class="display-6 fw-bold">৳<?= number_format(array_sum(array_map(static fn($o)=>(float)$o->total,$orders)),2) ?></div></div></div></div>
</div>

<div class="card border-0 shadow-sm"><div class="card-body table-responsive">
<table class="table table-hover align-middle"><thead><tr><th>Submitted</th><th>Invoice / School</th><th>Service</th><th>Students</th><th>Amount</th><th>Payment evidence</th><th class="text-end">Decision</th></tr></thead><tbody>
<?php if($orders): foreach($orders as $o): ?>
<tr>
    <td><?= esc(!empty($o->updated_at)?date('d M Y, h:i A',strtotime($o->updated_at)):'-') ?></td>
    <td><strong><?= esc($o->invoice_no) ?></strong><br><?= esc($o->school_name??'-') ?><br><small class="text-muted"><?= esc($o->school_phone??'') ?></small></td>
    <td><span class="badge bg-primary"><?= esc($o->service) ?></span><?php if(!empty($o->service_mode)):?><br><small><?= esc($o->service_mode) ?></small><?php endif?><br><small class="text-muted"><?= esc($o->exam_title??'-') ?></small></td>
    <td><?= number_format((int)$o->student_count) ?></td>
    <td><strong>৳<?= number_format((float)$o->total,2) ?></strong><?php if((float)$o->discount>0):?><br><small class="text-success">Discount: ৳<?=number_format((float)$o->discount,2)?></small><?php endif?></td>
    <td><strong><?= esc(strtoupper((string)($o->payment_method??'manual'))) ?></strong><br>Transaction: <code><?= esc($o->transaction_id??'-') ?></code><?php if(!empty($o->payment_note)):?><br><small><?= nl2br(esc($o->payment_note)) ?></small><?php endif?></td>
    <td class="text-end"><div class="d-inline-flex gap-2"><?=form_open('saas-admin/service-billing/approve/'.$o->id)?><button class="btn btn-success" onclick="return confirm('Approve and enable this service?')"><i class="bi bi-check-lg"></i> <?=($o->status==='draft'&&(float)$o->total<=0)?'Grant access':'Approve payment'?></button><?=form_close()?><?=form_open('saas-admin/service-billing/cancel/'.$o->id)?><button class="btn btn-outline-danger" onclick="return confirm('Cancel this service order?')">Cancel</button><?=form_close()?></div></td>
</tr>
<?php endforeach; else: ?><tr><td colspan="7" class="text-center text-muted py-5"><i class="bi bi-check-circle display-4 d-block mb-2 text-success"></i>No payments are waiting for approval.</td></tr><?php endif ?>
</tbody></table></div></div>
