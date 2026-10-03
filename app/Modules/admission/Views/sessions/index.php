<div class="right_col" role="main">
  <div class="d-flex justify-content-between mb-3"><h2>Admission Sessions</h2><a class="btn btn-primary" href="<?= base_url('school/admission/sessions/create') ?>">Create session</a></div>
  <div class="card shadow-sm"><div class="card-body table-responsive"><table class="table table-hover"><thead><tr><th>Title</th><th>School</th><th>Applications</th><th>Admission</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($items as $item): ?><tr><td><?= esc($item->title) ?></td><td><?= esc($item->school_name) ?></td><td><?= esc($item->application_start) ?><br><?= esc($item->application_end) ?></td><td><?= esc($item->admission_start?:'—') ?><br><?= esc($item->admission_end?:'—') ?></td><td><span class="badge bg-secondary"><?= esc(ucfirst($item->status)) ?></span></td><td><a href="<?= base_url('school/admission/sessions/edit/'.$item->token) ?>">Edit</a></td></tr><?php endforeach ?>
  <?php if (!$items): ?><tr><td colspan="6" class="text-center text-muted">No admission sessions yet.</td></tr><?php endif ?>
  </tbody></table></div></div>
</div>
