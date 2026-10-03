<?php
$showOptions = ['', 10, 50, 100, 200, 500, 1000];
$permissions = $permissions ?? ['create' => false, 'edit' => false, 'delete' => false];
?>

<?= form_open('examination/exam-rooms', ['id' => 'room_search', 'method' => 'get']) ?>
<input type="hidden" id="csrf_token" value="<?= csrf_hash() ?>">

<div class="row mb-3">
    <div class="col-lg-3">
        <h3 class="text-secondary mb-0"><i class="bi bi-door-open"></i> <?= esc(lang('ExamRoom.page_title_list')) ?></h3>
    </div>
    <div class="col-lg-9">
        <div class="row g-2 justify-content-end">
            <div class="col-auto">
                <div class="input-group">
                    <input type="text" name="text" id="search_text" class="form-control"
                           value="<?= esc($text ?? '') ?>" placeholder="<?= esc(lang('ExamRoom.search_placeholder')) ?>">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
                    <button type="button" id="clear_filters" class="btn btn-secondary"><i class="fa fa-refresh"></i></button>
                </div>
            </div>
            <div class="col-auto">
                <select name="school_id" id="field_school_id" class="form-control filter-select">
                    <option value=""><?= esc(lang('ExamRoom.all_schools')) ?></option>
                    <?php foreach (($school_list ?? []) as $schoolId => $schoolName): ?>
                        <option value="<?= (int) $schoolId ?>" <?= (int) ($selected_school ?? 0) === (int) $schoolId ? 'selected' : '' ?>>
                            <?= esc($schoolName) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="status" id="field_status" class="form-control filter-select">
                    <option value="">All Statuses</option>
                    <option value="1" <?= (string) ($status ?? '') === '1' ? 'selected' : '' ?>><?= esc(lang('ExamRoom.enabled')) ?></option>
                    <option value="0" <?= (string) ($status ?? '') === '0' ? 'selected' : '' ?>><?= esc(lang('ExamRoom.disabled')) ?></option>
                    <option value="2" <?= (string) ($status ?? '') === '2' ? 'selected' : '' ?>><?= esc(lang('ExamRoom.trashed_status')) ?></option>
                </select>
            </div>
            <div class="col-auto">
                <select name="show" id="field_show" class="form-control filter-select">
                    <?php foreach ($showOptions as $option): ?>
                        <option value="<?= esc($option) ?>" <?= (string) ($show ?? '') === (string) $option ? 'selected' : '' ?>>
                            <?= $option === '' ? 'Show' : (int) $option ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <?php if ($permissions['create']): ?>
                <div class="col-auto">
                    <a href="<?= base_url('examination/exam-rooms/create') ?>" class="btn btn-success">
                        <i class="fa fa-plus"></i> <?= esc(lang('ExamRoom.add_room')) ?>
                    </a>
                </div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="card">
    <div class="card-body">
        <?= get_system_message() ?>
        <div id="result"></div>

        <div class="table-responsive">
            <table class="table student-table align-middle">
                <thead>
                <tr>
                    <th>#</th>
                    <th><?= esc(lang('ExamRoom.room_name')) ?></th>
                    <th><?= esc(lang('ExamRoom.school')) ?></th>
                    <th><?= esc(lang('ExamRoom.building')) ?></th>
                    <th class="text-center"><?= esc(lang('ExamRoom.layout')) ?></th>
                    <th class="text-center"><?= esc(lang('ExamRoom.capacity')) ?></th>
                    <th class="text-center"><?= esc(lang('ExamRoom.status')) ?></th>
                    <th class="text-end"><?= esc(lang('ExamRoom.actions')) ?></th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($items)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><?= esc(lang('ExamRoom.no_rooms')) ?></td></tr>
                <?php else: ?>
                    <?php foreach ($items as $index => $room): ?>
                        <?php $roomStatus = (int) $room->status ?>
                        <tr id="room_<?= esc($room->token) ?>">
                            <td><?= (int) $index + 1 ?></td>
                            <td>
                                <strong><?= esc($room->room_name) ?></strong>
                                <div class="small text-muted"><?= esc(lang('ExamRoom.room_no')) ?>: <?= esc($room->room_no) ?></div>
                            </td>
                            <td><?= esc($room->school_name ?? '-') ?></td>
                            <td>
                                <?= esc($room->building ?: '-') ?>
                                <?php if (!empty($room->floor)): ?><div class="small text-muted"><?= esc($room->floor) ?></div><?php endif ?>
                            </td>
                            <td class="text-center"><?= (int) $room->rows_count ?> × <?= (int) $room->columns_count ?></td>
                            <td class="text-center"><?= (int) $room->capacity ?></td>
                            <td class="text-center room-status">
                                <?php if ($roomStatus === 1): ?>
                                    <span class="badge text-bg-success"><?= esc(lang('ExamRoom.enabled')) ?></span>
                                <?php elseif ($roomStatus === 0): ?>
                                    <span class="badge text-bg-warning"><?= esc(lang('ExamRoom.disabled')) ?></span>
                                <?php else: ?>
                                    <span class="badge text-bg-danger"><?= esc(lang('ExamRoom.trashed_status')) ?></span>
                                <?php endif ?>
                            </td>
                            <td class="text-end">
                                <?php if ($roomStatus !== 2 && $permissions['edit']): ?>
                                    <a href="<?= base_url('examination/exam-rooms/edit/' . rawurlencode($room->token)) ?>" class="btn btn-sm btn-info mb-1">
                                        <i class="fa fa-edit"></i> <?= esc(lang('ExamRoom.edit')) ?>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary mb-1 room-action"
                                            data-action="status" data-status="<?= $roomStatus === 1 ? 0 : 1 ?>" data-token="<?= esc($room->token) ?>"
                                            data-confirm="<?= esc(lang('ExamRoom.confirm_status')) ?>">
                                        <?= esc($roomStatus === 1 ? lang('ExamRoom.disable') : lang('ExamRoom.enable')) ?>
                                    </button>
                                <?php endif ?>
                                <?php if ($permissions['delete']): ?>
                                    <?php if ($roomStatus === 2): ?>
                                        <button type="button" class="btn btn-sm btn-success mb-1 room-action" data-action="restore"
                                                data-token="<?= esc($room->token) ?>"><?= esc(lang('ExamRoom.restore')) ?></button>
                                        <button type="button" class="btn btn-sm btn-danger mb-1 room-action" data-action="delete"
                                                data-token="<?= esc($room->token) ?>" data-confirm="<?= esc(lang('ExamRoom.confirm_delete')) ?>">
                                            <?= esc(lang('ExamRoom.delete')) ?>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-danger mb-1 room-action" data-action="trash"
                                                data-token="<?= esc($room->token) ?>" data-confirm="<?= esc(lang('ExamRoom.confirm_trash')) ?>">
                                            <i class="fa fa-trash"></i> <?= esc(lang('ExamRoom.trash')) ?>
                                        </button>
                                    <?php endif ?>
                                <?php endif ?>
                            </td>
                        </tr>
                    <?php endforeach ?>
                <?php endif ?>
                </tbody>
            </table>
        </div>

        <?php if (!empty($pager) && $pager->getTotal() > $pager->getPerPage()): ?>
            <div class="d-flex justify-content-end"><?= $pager->links('default', $pagerTemplate) ?></div>
        <?php endif ?>
    </div>
</div>

<script>
$(function () {
    var csrfToken = $('#csrf_token').val();
    var baseUrl = <?= json_encode(rtrim(base_url(), '/') . '/examination/exam-rooms/') ?>;

    $('.filter-select').on('change', function () { $('#room_search').trigger('submit'); });
    $('#clear_filters').on('click', function () {
        $('#search_text, #field_school_id, #field_status, #field_show').val('');
        $('#room_search').trigger('submit');
    });

    $(document).on('click', '.room-action', function () {
        var $button = $(this);
        var action = $button.data('action');
        var token = $button.data('token');
        var confirmation = $button.data('confirm');
        if (confirmation && !window.confirm(confirmation)) return;

        var postData = {};
        if (action === 'status') postData.status = $button.data('status');

        $button.prop('disabled', true);
        $.ajax({
            method: 'POST',
            url: baseUrl + action + '/' + encodeURIComponent(token),
            data: postData,
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': csrfToken}
        }).done(function (response, textStatus, xhr) {
            csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN') || csrfToken;
            $('#csrf_token').val(csrfToken);
            $('#result').html('<div class="alert alert-' + (response.status ? 'success' : 'danger') + '">' + $('<div>').text(response.message || '').html() + '</div>');
            if (response.status) window.location.reload();
        }).fail(function (xhr) {
            csrfToken = xhr.getResponseHeader('X-CSRF-TOKEN') || csrfToken;
            var response = xhr.responseJSON || {};
            $('#result').html('<div class="alert alert-danger">' + $('<div>').text(response.message || 'Request failed.').html() + '</div>');
        }).always(function () {
            $button.prop('disabled', false);
        });
    });
});
</script>
