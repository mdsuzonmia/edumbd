<?php
$post_data = $post_data ?? [];
$is_edit = !empty($is_edit);
$token = $token ?? ($post_data['token'] ?? '');
$formAction = $is_edit
    ? 'examination/exam-rooms/update/' . rawurlencode($token)
    : 'examination/exam-rooms/store';
$statusValue = isset($post_data['status']) ? (int) $post_data['status'] : 1;
?>

<?= form_open($formAction, [
    'class' => 'form-horizontal form-label-left',
    'id' => 'exam_room_form',
    'method' => 'post',
    'data-parsley-validate' => '',
]) ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0">
            <i class="bi bi-door-open"></i>
            <?= esc($is_edit ? lang('ExamRoom.page_title_edit') : lang('ExamRoom.page_title_new')) ?>
        </h3>
    </div>
    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-sm btn-success">
            <i class="fa-solid fa-floppy-disk"></i> <?= esc(lang('Common.btn_save')) ?>
        </button>
        <a href="<?= base_url('examination/exam-rooms') ?>" class="btn btn-sm btn-info">
            <i class="fa fa-arrow-left"></i> <?= esc(lang('ExamRoom.back_to_list')) ?>
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (!empty($validation)): ?>
            <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
        <?php endif ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= esc($error) ?></div>
        <?php endif ?>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="field_school_id" class="form-label">
                    <?= esc(lang('ExamRoom.school')) ?> <span class="text-danger">*</span>
                </label>
                <select name="school_id" id="field_school_id" class="form-control" required>
                    <option value=""><?= esc(lang('ExamRoom.select_school')) ?></option>
                    <?php foreach (($school_list ?? []) as $schoolId => $schoolName): ?>
                        <option value="<?= (int) $schoolId ?>" <?= (int) ($post_data['school_id'] ?? 0) === (int) $schoolId ? 'selected' : '' ?>>
                            <?= esc($schoolName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="col-md-6 mb-3">
                <label for="field_room_name" class="form-label">
                    <?= esc(lang('ExamRoom.room_name')) ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="room_name" id="field_room_name" class="form-control" maxlength="150" required
                       value="<?= esc($post_data['room_name'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_room_no" class="form-label">
                    <?= esc(lang('ExamRoom.room_no')) ?> <span class="text-danger">*</span>
                </label>
                <input type="text" name="room_no" id="field_room_no" class="form-control" maxlength="50" required
                       value="<?= esc($post_data['room_no'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_building" class="form-label"><?= esc(lang('ExamRoom.building')) ?></label>
                <input type="text" name="building" id="field_building" class="form-control" maxlength="150"
                       value="<?= esc($post_data['building'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_floor" class="form-label"><?= esc(lang('ExamRoom.floor')) ?></label>
                <input type="text" name="floor" id="field_floor" class="form-control" maxlength="50"
                       value="<?= esc($post_data['floor'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_rows_count" class="form-label">
                    <?= esc(lang('ExamRoom.rows_count')) ?> <span class="text-danger">*</span>
                </label>
                <input type="number" name="rows_count" id="field_rows_count" class="form-control layout-value"
                       min="1" max="1000" required value="<?= esc($post_data['rows_count'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_columns_count" class="form-label">
                    <?= esc(lang('ExamRoom.columns_count')) ?> <span class="text-danger">*</span>
                </label>
                <input type="number" name="columns_count" id="field_columns_count" class="form-control layout-value"
                       min="1" max="1000" required value="<?= esc($post_data['columns_count'] ?? '') ?>">
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_capacity" class="form-label">
                    <?= esc(lang('ExamRoom.capacity')) ?> <span class="text-danger">*</span>
                </label>
                <input type="number" name="capacity" id="field_capacity" class="form-control layout-value"
                       min="1" max="100000" required value="<?= esc($post_data['capacity'] ?? '') ?>">
                <div id="layout_help" class="form-text"></div>
            </div>

            <div class="col-md-4 mb-3">
                <label for="field_status" class="form-label"><?= esc(lang('ExamRoom.status')) ?></label>
                <select name="status" id="field_status" class="form-control" required>
                    <option value="1" <?= $statusValue === 1 ? 'selected' : '' ?>><?= esc(lang('ExamRoom.enabled')) ?></option>
                    <option value="0" <?= $statusValue === 0 ? 'selected' : '' ?>><?= esc(lang('ExamRoom.disabled')) ?></option>
                </select>
            </div>
        </div>
    </div>
</div>

<?= form_close() ?>

<script>
$(function () {
    function updateLayoutHelp() {
        var rows = parseInt($('#field_rows_count').val(), 10) || 0;
        var columns = parseInt($('#field_columns_count').val(), 10) || 0;
        var capacity = parseInt($('#field_capacity').val(), 10) || 0;
        var layoutSeats = rows * columns;
        var $help = $('#layout_help');

        if (!layoutSeats) {
            $help.text('');
            return;
        }

        $help.text(rows + ' × ' + columns + ' = ' + layoutSeats + ' layout seats');
        $help.toggleClass('text-danger', capacity > layoutSeats);
    }

    $('.layout-value').on('input change', updateLayoutHelp);
    updateLayoutHelp();
});
</script>
