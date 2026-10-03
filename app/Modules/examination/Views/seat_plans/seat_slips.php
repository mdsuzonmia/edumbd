<div class="row mb-3">
    <div class="col-sm-8">
        <h3 class="text-secondary mb-0"><i class="fa fa-th-large"></i> <?= esc(lang('SeatPlan.seat_slips')) ?></h3>
        <div class="text-muted"><?= esc($plan->title) ?> &middot; <?= esc($exam->title ?? '') ?></div>
    </div>
    <div class="col-sm-4 text-end">
        <a href="<?= base_url('examination/seat-plans/view/' . rawurlencode($plan->token)) ?>" class="btn btn-info btn-sm"><i class="fa fa-arrow-left"></i> <?= esc(lang('SeatPlan.back_to_list')) ?></a>
    </div>
</div>

<?= get_system_message() ?>

<div class="card">
    <div class="card-header"><strong><?= esc(lang('SeatPlan.seat_slip_filters')) ?></strong></div>
    <div class="card-body">
        <form method="get" action="<?= base_url('examination/seat-plans/print/' . rawurlencode($plan->token) . '/seat-slips') ?>" id="seat_slip_form">
            <div class="row mb-3">
                <div class="col-md-5">
                    <label for="seat_slip_room" class="form-label"><?= esc(lang('SeatPlan.room')) ?></label>
                    <select class="form-select" name="room_id" id="seat_slip_room">
                        <option value="0"><?= esc(lang('SeatPlan.all_rooms')) ?></option>
                        <?php foreach ($rooms as $room): ?>
                        <option value="<?= (int) $room->room_id ?>"><?= esc($room->room_name_snapshot . ' (' . $room->room_no_snapshot . ')') ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="col-md-7 d-flex align-items-end justify-content-md-end gap-2 mt-3 mt-md-0">
                    <button type="submit" class="btn btn-outline-primary" formtarget="_blank"><i class="fa fa-eye"></i> <?= esc(lang('SeatPlan.preview_report')) ?></button>
                    <button type="submit" class="btn btn-primary" name="auto_print" value="1" formtarget="_blank"><i class="fa fa-print"></i> <?= esc(lang('SeatPlan.print')) ?></button>
                    <button type="submit" class="btn btn-danger" formaction="<?= base_url('examination/seat-plans/download-pdf/' . rawurlencode($plan->token) . '/seat-slips') ?>"><i class="fa fa-file-pdf-o"></i> <?= esc(lang('SeatPlan.download_pdf')) ?></button>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-2">
                <div>
                    <strong><?= esc(lang('SeatPlan.selected_students_optional')) ?></strong>
                    <div class="small text-muted"><?= esc(lang('SeatPlan.selected_students_help')) ?></div>
                </div>
                <div class="btn-group btn-group-sm">
                    <button type="button" class="btn btn-outline-secondary" id="select_all_slips"><?= esc(lang('SeatPlan.select_all')) ?></button>
                    <button type="button" class="btn btn-outline-secondary" id="clear_all_slips"><?= esc(lang('SeatPlan.clear_all')) ?></button>
                </div>
            </div>

            <div class="table-responsive" style="max-height: 430px; overflow-y: auto;">
                <table class="table table-sm table-bordered align-middle mb-0">
                    <thead class="table-light"><tr><th style="width:45px"></th><th><?= esc(lang('SeatPlan.room')) ?></th><th><?= esc(lang('SeatPlan.seat')) ?></th><th><?= esc(lang('SeatPlan.roll')) ?></th><th><?= esc(lang('SeatPlan.student_name')) ?></th><th><?= esc(lang('SeatPlan.class')) ?></th><th><?= esc(lang('SeatPlan.section')) ?></th></tr></thead>
                    <tbody>
                    <?php $roomMap = []; foreach ($rooms as $room) { $roomMap[(int) $room->room_id] = $room; } ?>
                    <?php foreach ($seat_slip_allocations as $allocation): $allocationRoom = $roomMap[(int) $allocation->room_id] ?? null; ?>
                    <tr class="seat-slip-student" data-room-id="<?= (int) $allocation->room_id ?>">
                        <td class="text-center"><input class="form-check-input slip-student" type="checkbox" name="student_ids[]" value="<?= (int) $allocation->student_id ?>"></td>
                        <td><?= esc($allocationRoom ? $allocationRoom->room_name_snapshot . ' (' . $allocationRoom->room_no_snapshot . ')' : '-') ?></td>
                        <td><strong><?= esc($allocation->seat_no) ?></strong></td>
                        <td><?= esc($allocation->roll_no_snapshot ?? '-') ?></td>
                        <td><?= esc($allocation->student_name ?? '-') ?></td>
                        <td><?= esc($allocation->class_name ?? '-') ?></td>
                        <td><?= esc($allocation->section_name ?? '-') ?></td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </form>
    </div>
</div>

<script>
$(function () {
    function visibleStudentChecks() {
        return $('.seat-slip-student:visible .slip-student');
    }
    $('#seat_slip_room').on('change', function () {
        var roomId = String($(this).val());
        $('.seat-slip-student').each(function () {
            $(this).toggle(roomId === '0' || String($(this).data('room-id')) === roomId);
        });
    });
    $('#select_all_slips').on('click', function () { visibleStudentChecks().prop('checked', true); });
    $('#clear_all_slips').on('click', function () { $('.slip-student').prop('checked', false); });
});
</script>
