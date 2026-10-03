<div class="right_col" role="main">
  <div class="d-flex justify-content-between align-items-center mb-3"><div><h2>Admission Dashboard</h2><p class="text-muted">Applications, lottery selection, and completed admissions.</p></div><a class="btn btn-primary" href="<?= base_url('school/admission/circulars/create') ?>">New circular</a></div>
  <div class="row">
    <?php foreach (['applications'=>'Applications','pending'=>'Pending Review','eligible'=>'Eligible','selected'=>'Selected','waiting'=>'Waiting List','admitted'=>'Admission Completed'] as $key=>$label): ?>
      <div class="col-sm-6 col-lg-4 mb-3"><div class="card shadow-sm h-100"><div class="card-body"><div class="text-muted"><?= esc($label) ?></div><div class="display-5"><?= number_format($stats[$key]??0) ?></div></div></div></div>
    <?php endforeach ?>
  </div>
  <div class="card shadow-sm"><div class="card-body d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/sessions') ?>">Admission Sessions</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/circulars') ?>">Circulars</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/applications') ?>">Applications</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/lotteries') ?>">Lottery</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/form-builder') ?>">Form Builder</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/payments') ?>">Payments</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/payment-settings') ?>">Payment Settings</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/documents') ?>">Documents</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/tests') ?>">Admission Tests</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/seat-plans') ?>">Seat Plans</a>
    <a class="btn btn-outline-primary" href="<?= base_url('school/admission/admit-cards') ?>">Admit Cards</a>
  </div></div>
</div>
