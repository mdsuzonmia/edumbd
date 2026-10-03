<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= esc($plan->title . ' - ' . $report_titles[$report_type]) ?></title>
    <style>
        @page { margin: 16mm 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; }
        .toolbar { background: #f1f3f5; border-bottom: 1px solid #ccc; margin: -16mm -12mm 14px; padding: 10px 12mm; text-align: right; }
        .toolbar a, .toolbar button { background: #1769aa; border: 0; border-radius: 3px; color: #fff; cursor: pointer; display: inline-block; font-size: 13px; padding: 7px 12px; text-decoration: none; }
        .report-header { border-bottom: 2px solid #111; margin-bottom: 14px; min-height: 58px; padding-bottom: 9px; text-align: center; }
        .school-logo { float: left; height: 52px; max-width: 70px; object-fit: contain; }
        .report-header h1 { font-size: 19px; margin: 0 0 3px; }
        .report-header h2 { font-size: 15px; margin: 4px 0 2px; }
        .report-header p { margin: 2px 0; }
        .meta { margin-bottom: 10px; text-align: center; }
        .room-title { font-size: 15px; margin: 8px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #222; padding: 5px; vertical-align: middle; }
        th { background: #eee; font-weight: bold; text-align: center; }
        .student-table td { text-align: center; }
        .student-table td.name { text-align: left; }
        .seat-grid { table-layout: fixed; }
        .seat-grid td { height: 72px; padding: 6px; text-align: center; vertical-align: top; }
        .seat-no { display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px; }
        .seat-detail { display: block; font-size: 9px; line-height: 1.35; }
        .empty-seat { color: #999; }
        .page-break { page-break-before: always; }
        .door-notice { border: 4px double #111; min-height: 220mm; padding: 24mm 14mm; text-align: center; }
        .door-notice .exam-name { font-size: 24px; font-weight: bold; margin: 12px 0; text-transform: uppercase; }
        .door-notice .room-number { font-size: 42px; font-weight: bold; margin: 25px 0; }
        .door-notice .detail { font-size: 19px; line-height: 1.7; }
        .slip-grid { border-collapse: separate; border-spacing: 4mm 3mm; table-layout: fixed; }
        .slip-grid > tbody > tr > td { border: 0; height: 61mm; padding: 0; vertical-align: top; width: 50%; }
        .seat-slip { border: 1.5px dashed #222; height: 61mm; overflow: hidden; padding: 5mm; page-break-inside: avoid; text-align: center; }
        .seat-slip-logo { float: left; height: 29px; margin-right: 5px; max-width: 38px; object-fit: contain; }
        .seat-slip-school { font-size: 13px; font-weight: bold; margin: 0 0 2px; text-transform: uppercase; }
        .seat-slip-exam { border-bottom: 1px solid #555; font-size: 10px; margin: 0 0 4px; padding-bottom: 4px; }
        .seat-slip-number { font-size: 27px; font-weight: bold; line-height: 1; margin-top: 5px; }
        .seat-slip-label { font-size: 8px; font-weight: bold; letter-spacing: 1px; margin-bottom: 5px; }
        .seat-slip-student { font-size: 12px; font-weight: bold; margin: 3px 0; }
        .seat-slip-info { font-size: 9px; line-height: 1.45; text-align: left; }
        .seat-slip-info strong { display: inline-block; width: 100px; }
        .no-data { border: 1px solid #999; padding: 20px; text-align: center; }
        .generated { color: #666; font-size: 9px; margin-top: 12px; text-align: right; }
        .clearfix::after { clear: both; content: ''; display: table; }
        @media print {
            .toolbar { display: none; }
            body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>
<?php if (empty($is_pdf)): ?>
<div class="toolbar">
    <a href="<?= base_url('examination/seat-plans/view/' . rawurlencode($plan->token)) ?>"><?= esc(lang('SeatPlan.back_to_list')) ?></a>
    <button type="button" onclick="window.print()"><?= esc(lang('SeatPlan.print')) ?></button>
</div>
<?php endif ?>

<?php
$renderHeader = static function (?string $roomTitle = null) use ($school, $school_logo_url, $plan, $exam, $report_titles, $report_type): void {
?>
<header class="report-header clearfix">
    <?php if ($school_logo_url): ?><img class="school-logo" src="<?= esc($school_logo_url, 'attr') ?>" alt=""><?php endif ?>
    <h1><?= esc($school->name ?? '') ?></h1>
    <?php if (!empty($school->address)): ?><p><?= esc($school->address) ?></p><?php endif ?>
    <h2><?= esc($exam->title ?? $plan->title) ?> &mdash; <?= esc($report_titles[$report_type]) ?></h2>
    <?php if ($roomTitle): ?><p><strong><?= esc($roomTitle) ?></strong></p><?php endif ?>
</header>
<?php }; ?>

<?php if ($report_type === 'visual'): ?>
    <?php foreach ($rooms as $roomIndex => $room):
        $roomAllocations = $allocations_by_room[(int) $room->room_id] ?? [];
        $positionMap = [];
        foreach ($roomAllocations as $allocation) {
            $positionMap[(int) $allocation->row_no . ':' . (int) $allocation->column_no] = $allocation;
        }
        $seatIndex = 0;
        $capacity = min((int) $room->capacity, (int) $room->rows_count * (int) $room->columns_count);
    ?>
    <section class="<?= $roomIndex > 0 ? 'page-break' : '' ?>">
        <?php $renderHeader(($room->room_name_snapshot ?? '') . ' (' . ($room->room_no_snapshot ?? '') . ')'); ?>
        <div class="meta"><?= esc(lang('SeatPlan.total_candidates')) ?>: <strong><?= count($roomAllocations) ?></strong> / <?= $capacity ?></div>
        <table class="seat-grid"><tbody>
        <?php for ($row = 1; $row <= (int) $room->rows_count; $row++): ?><tr>
            <?php for ($column = 1; $column <= (int) $room->columns_count; $column++):
                $seatIndex++;
                $allocation = $seatIndex <= $capacity ? ($positionMap[$row . ':' . $column] ?? null) : null;
            ?>
            <td class="<?= $allocation ? '' : 'empty-seat' ?>">
                <?php if ($seatIndex <= $capacity): ?>
                    <span class="seat-no"><?= esc($allocation->seat_no ?? (string) $seatIndex) ?></span>
                    <?php if ($allocation): ?>
                    <span class="seat-detail"><?= esc(lang('SeatPlan.roll')) ?>: <?= esc($allocation->roll_no_snapshot ?? '-') ?></span>
                    <span class="seat-detail"><?= esc($allocation->student_name ?? '-') ?></span>
                    <span class="seat-detail"><?= esc(($allocation->class_name ?? '-') . (!empty($allocation->section_name) ? ' / ' . $allocation->section_name : '')) ?></span>
                    <?php else: ?><span class="seat-detail">&mdash;</span><?php endif ?>
                <?php endif ?>
            </td>
            <?php endfor ?>
        </tr><?php endfor ?>
        </tbody></table>
    </section>
    <?php endforeach ?>

<?php elseif ($report_type === 'room-list'): ?>
    <?php foreach ($rooms as $roomIndex => $room): $roomAllocations = $allocations_by_room[(int) $room->room_id] ?? []; ?>
    <section class="<?= $roomIndex > 0 ? 'page-break' : '' ?>">
        <?php $renderHeader(($room->room_name_snapshot ?? '') . ' (' . ($room->room_no_snapshot ?? '') . ')'); ?>
        <table class="student-table">
            <thead><tr><th><?= esc(lang('SeatPlan.seat')) ?></th><th><?= esc(lang('SeatPlan.roll')) ?></th><th><?= esc(lang('SeatPlan.student_id')) ?></th><th><?= esc(lang('SeatPlan.student_name')) ?></th><th><?= esc(lang('SeatPlan.class')) ?></th><th><?= esc(lang('SeatPlan.section')) ?></th></tr></thead>
            <tbody><?php foreach ($roomAllocations as $allocation): ?><tr>
                <td><?= esc($allocation->seat_no) ?></td><td><?= esc($allocation->roll_no_snapshot ?? '-') ?></td><td><?= esc($allocation->student_code ?? '-') ?></td><td class="name"><?= esc($allocation->student_name ?? '-') ?></td><td><?= esc($allocation->class_name ?? '-') ?></td><td><?= esc($allocation->section_name ?? '-') ?></td>
            </tr><?php endforeach ?></tbody>
        </table>
        <div class="generated"><?= esc(lang('SeatPlan.total_candidates')) ?>: <?= count($roomAllocations) ?></div>
    </section>
    <?php endforeach ?>

<?php elseif ($report_type === 'class-list'): ?>
    <?php $renderHeader(); $roomMap = []; foreach ($rooms as $room) { $roomMap[(int) $room->room_id] = $room; } ?>
    <table class="student-table">
        <thead><tr><th><?= esc(lang('SeatPlan.roll')) ?></th><th><?= esc(lang('SeatPlan.student_id')) ?></th><th><?= esc(lang('SeatPlan.student_name')) ?></th><th><?= esc(lang('SeatPlan.class')) ?></th><th><?= esc(lang('SeatPlan.section')) ?></th><th><?= esc(lang('SeatPlan.room')) ?></th><th><?= esc(lang('SeatPlan.seat')) ?></th></tr></thead>
        <tbody><?php foreach ($class_allocations as $allocation): $allocationRoom = $roomMap[(int) $allocation->room_id] ?? null; ?><tr>
            <td><?= esc($allocation->roll_no_snapshot ?? '-') ?></td><td><?= esc($allocation->student_code ?? '-') ?></td><td class="name"><?= esc($allocation->student_name ?? '-') ?></td><td><?= esc($allocation->class_name ?? '-') ?></td><td><?= esc($allocation->section_name ?? '-') ?></td><td><?= esc($allocationRoom ? $allocationRoom->room_name_snapshot . ' (' . $allocationRoom->room_no_snapshot . ')' : '-') ?></td><td><?= esc($allocation->seat_no) ?></td>
        </tr><?php endforeach ?></tbody>
    </table>

<?php elseif ($report_type === 'seat-slips'): ?>
    <?php
    $roomMap = [];
    foreach ($rooms as $room) { $roomMap[(int) $room->room_id] = $room; }
    $slipPages = array_chunk($seat_slip_allocations, 8);
    ?>
    <?php if ($slipPages === []): ?>
        <?php $renderHeader(); ?><div class="no-data"><?= esc(lang('SeatPlan.no_allocations')) ?></div>
    <?php endif ?>
    <?php foreach ($slipPages as $pageIndex => $pageSlips): ?>
    <section class="<?= $pageIndex > 0 ? 'page-break' : '' ?>">
        <table class="slip-grid"><tbody>
        <?php for ($row = 0; $row < 4; $row++): ?><tr>
            <?php for ($column = 0; $column < 2; $column++):
                $slip = $pageSlips[$row * 2 + $column] ?? null;
                $slipRoom = $slip ? ($roomMap[(int) $slip->room_id] ?? null) : null;
            ?><td>
                <?php if ($slip): ?>
                <article class="seat-slip">
                    <?php if ($school_logo_url): ?><img class="seat-slip-logo" src="<?= esc($school_logo_url, 'attr') ?>" alt=""><?php endif ?>
                    <div class="seat-slip-school"><?= esc($school->name ?? '') ?></div>
                    <div class="seat-slip-exam"><?= esc($exam->title ?? $plan->title) ?></div>
                    <div class="seat-slip-number"><?= esc($slip->seat_no) ?></div>
                    <div class="seat-slip-label"><?= esc(lang('SeatPlan.seat_no_label')) ?></div>
                    <div class="seat-slip-student"><?= esc($slip->student_name ?? '-') ?></div>
                    <div class="seat-slip-info">
                        <div><strong><?= esc(lang('SeatPlan.roll')) ?>:</strong> <?= esc($slip->roll_no_snapshot ?? '-') ?></div>
                        <?php if (!empty($slip->student_code)): ?><div><strong><?= esc(lang('SeatPlan.student_id')) ?>:</strong> <?= esc($slip->student_code) ?></div><?php endif ?>
                        <div><strong><?= esc(lang('SeatPlan.class')) ?>:</strong> <?= esc(($slip->class_name ?? '-') . (!empty($slip->section_name) ? ' - ' . $slip->section_name : '')) ?></div>
                        <div><strong><?= esc(lang('SeatPlan.room')) ?>:</strong> <?= esc($slipRoom ? ($slipRoom->room_no_snapshot ?: $slipRoom->room_name_snapshot) : '-') ?></div>
                    </div>
                </article>
                <?php endif ?>
            </td><?php endfor ?>
        </tr><?php endfor ?>
        </tbody></table>
    </section>
    <?php endforeach ?>

<?php elseif ($report_type === 'door-notice'): ?>
    <?php foreach ($rooms as $roomIndex => $room):
        $roomAllocations = $allocations_by_room[(int) $room->room_id] ?? [];
        $classLabels = [];
        foreach ($roomAllocations as $allocation) {
            $label = (string) ($allocation->class_name ?? '-');
            if (!empty($allocation->section_name)) { $label .= ' - ' . $allocation->section_name; }
            $classLabels[$label] = true;
        }
        $firstSeat = $roomAllocations[0]->seat_no ?? '-';
        $lastSeat = $roomAllocations !== [] ? $roomAllocations[count($roomAllocations) - 1]->seat_no : '-';
    ?>
    <section class="door-notice <?= $roomIndex > 0 ? 'page-break' : '' ?>">
        <?php if ($school_logo_url): ?><img src="<?= esc($school_logo_url, 'attr') ?>" alt="" style="height:75px;max-width:100px"><?php endif ?>
        <h1><?= esc($school->name ?? '') ?></h1>
        <div class="exam-name"><?= esc($exam->title ?? $plan->title) ?></div>
        <div class="room-number"><?= esc(lang('SeatPlan.room')) ?> <?= esc($room->room_no_snapshot ?: $room->room_name_snapshot) ?></div>
        <div class="detail">
            <div><strong><?= esc(lang('SeatPlan.classes')) ?>:</strong><br><?= esc(implode(' + ', array_keys($classLabels)) ?: '-') ?></div>
            <div><strong><?= esc(lang('SeatPlan.total_candidates')) ?>:</strong> <?= count($roomAllocations) ?></div>
            <div><strong><?= esc(lang('SeatPlan.seat_range')) ?>:</strong> <?= esc($firstSeat) ?> &ndash; <?= esc($lastSeat) ?></div>
        </div>
    </section>
    <?php endforeach ?>
<?php endif ?>

<?php if (!in_array($report_type, ['door-notice', 'seat-slips'], true)): ?><div class="generated"><?= esc(lang('SeatPlan.generated_on')) ?>: <?= esc(date('d-m-Y h:i A')) ?></div><?php endif ?>
<?php if (empty($is_pdf) && !empty($auto_print)): ?><script>window.addEventListener('load', function () { window.print(); });</script><?php endif ?>
</body>
</html>
