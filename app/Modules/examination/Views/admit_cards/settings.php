<div class="row mb-3">
    <div class="col-sm-8">
        <h3 class="text-secondary mb-0"><i class="bi bi-gear"></i> <?= esc(lang('AdmitCard.settings')) ?></h3>
    </div>
    <div class="col-sm-4 text-end">
        <a href="<?= base_url('examination/admit-cards') ?>" class="btn btn-info btn-sm">
            <i class="fa fa-arrow-left"></i> <?= esc(lang('AdmitCard.back')) ?>
        </a>
    </div>
</div>

<?= get_system_message() ?>

<div class="card mb-3">
    <div class="card-header"><strong><?= esc(lang('AdmitCard.configuration')) ?></strong></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-4 mb-3"><strong><?= esc(lang('AdmitCard.school')) ?>:</strong> <?= esc($school->name ?? '-') ?></div>
            <div class="col-md-4 mb-3"><strong><?= esc(lang('AdmitCard.exam')) ?>:</strong> <?= esc($exam->title ?? '-') ?></div>
            <div class="col-md-4 mb-3"><strong><?= esc(lang('AdmitCard.session')) ?>:</strong> <?= esc($session->title ?? '-') ?></div>
        </div>
    </div>
</div>

<?= form_open('examination/admit-cards/settings/' . rawurlencode($setting->token)) ?>
<div class="card">
    <div class="card-body">
        <div class="mb-3">
            <label for="title" class="form-label"><?= esc(lang('AdmitCard.title')) ?></label>
            <input type="text" id="title" name="title" class="form-control" maxlength="255" value="<?= esc(old('title') !== null ? old('title') : $setting->title, 'attr') ?>" required>
        </div>
        <div class="mb-3">
            <label for="instructions" class="form-label"><?= esc(lang('AdmitCard.instructions')) ?></label>
            <textarea id="instructions" name="instructions" class="form-control" rows="5"><?= esc(old('instructions') !== null ? old('instructions') : ($setting->instructions ?? '')) ?></textarea>
        </div>

        <div class="row">
            <?php
            $options = [
                'show_student_photo' => 'show_photo',
                'show_student_id' => 'show_student_id',
                'show_registration_no' => 'show_registration',
                'show_exam_time' => 'show_exam_time',
                'show_room' => 'show_room',
                'show_seat' => 'show_seat',
                'show_qr_code' => 'show_qr',
                'require_seat_plan' => 'require_seat',
            ];
            foreach ($options as $name => $label):
                $checked = old($name) !== null ? (bool) old($name) : (bool) $setting->{$name};
            ?>
                <div class="col-md-4 mb-3">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="<?= esc($name, 'attr') ?>" name="<?= esc($name, 'attr') ?>" value="1" <?= $checked ? 'checked' : '' ?>>
                        <label class="form-check-label" for="<?= esc($name, 'attr') ?>"><?= esc(lang('AdmitCard.' . $label)) ?></label>
                    </div>
                </div>
            <?php endforeach ?>
        </div>

        <button type="submit" class="btn btn-success">
            <i class="fa fa-save"></i> <?= esc(lang('AdmitCard.save_settings')) ?>
        </button>
        <a href="<?= base_url('examination/admit-cards/view/' . rawurlencode($setting->token)) ?>" class="btn btn-outline-primary">
            <i class="fa fa-eye"></i> <?= esc(lang('AdmitCard.view')) ?>
        </a>
    </div>
</div>
<?= form_close() ?>
