<?php $can_generate = !empty($can_generate); ?>

<?= form_open('examination/seat-plans', ['id' => 'seat_plan_search', 'method' => 'get']) ?>
<div class="row mb-3">
    <div class="col-lg-4">
        <h3 class="text-secondary mb-0"><i class="bi bi-grid-3x3-gap"></i> <?= esc(lang('SeatPlan.page_title_list')) ?></h3>
    </div>
    <div class="col-lg-8">
        <div class="row g-2 justify-content-end">
            <div class="col-auto"><input name="text" id="search_text" class="form-control" value="<?= esc($text ?? '') ?>" placeholder="Search"></div>
            <div class="col-auto">
                <select name="school_id" id="filter_school" class="form-control filter-submit">
                    <option value="">All Schools</option>
                    <?php foreach (($school_list ?? []) as $id => $name): ?>
                        <option value="<?= (int) $id ?>" <?= (int) ($selected_school ?? 0) === (int) $id ? 'selected' : '' ?>><?= esc($name) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="status" id="filter_status" class="form-control filter-submit">
                    <option value="">All Statuses</option>
                    <?php foreach (['draft', 'generated', 'locked'] as $value): ?>
                        <option value="<?= $value ?>" <?= ($status ?? '') === $value ? 'selected' : '' ?>><?= esc(lang('SeatPlan.' . $value)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-auto"><button class="btn btn-primary"><i class="fa fa-search"></i></button></div>
            <div class="col-auto"><button type="button" id="clear_filters" class="btn btn-secondary"><i class="fa fa-refresh"></i></button></div>
            <?php if ($can_generate): ?>
                <div class="col-auto"><a href="<?= base_url('examination/seat-plans/create') ?>" class="btn btn-success"><i class="fa fa-plus"></i> <?= esc(lang('SeatPlan.add_plan')) ?></a></div>
            <?php endif ?>
        </div>
    </div>
</div>
<?= form_close() ?>

<div class="card"><div class="card-body">
    <?= get_system_message() ?>
    <div class="table-responsive"><table class="table student-table align-middle">
        <thead><tr>
            <th>#</th><th><?= esc(lang('SeatPlan.title')) ?></th><th><?= esc(lang('SeatPlan.school')) ?></th>
            <th><?= esc(lang('SeatPlan.exam')) ?></th><th><?= esc(lang('SeatPlan.method')) ?></th>
            <th class="text-center"><?= esc(lang('SeatPlan.counts')) ?></th><th class="text-center"><?= esc(lang('SeatPlan.status')) ?></th>
            <th class="text-end"><?= esc(lang('SeatPlan.actions')) ?></th>
        </tr></thead>
        <tbody>
        <?php if (empty($items)): ?>
            <tr><td colspan="8" class="text-center text-muted py-4"><?= esc(lang('SeatPlan.no_plans')) ?></td></tr>
        <?php else: foreach ($items as $index => $item): ?>
            <tr>
                <td><?= $index + 1 ?></td>
                <td><strong><?= esc($item->title) ?></strong><?php if ($item->exam_date): ?><div class="small text-muted"><?= esc(date('d M Y', strtotime($item->exam_date))) ?></div><?php endif ?></td>
                <td><?= esc($item->school_name ?? '-') ?></td><td><?= esc($item->exam_name ?? '-') ?></td>
                <td><?= esc(ucwords(str_replace('_', ' ', $item->allocation_method))) ?></td>
                <td class="text-center"><?= (int) $item->student_count ?> / <?= (int) $item->room_count ?></td>
                <td class="text-center"><span class="badge <?= $item->status === 'locked' ? 'text-bg-danger' : 'text-bg-success' ?>"><?= esc(ucfirst($item->status)) ?></span></td>
                <td class="text-end text-nowrap">
                    <?php if (!empty($can_print)): ?><a href="<?= base_url('examination/seat-plans/seat-slips/' . rawurlencode($item->token)) ?>" class="btn btn-sm btn-primary me-1"><i class="fa fa-th-large"></i> <?= esc(lang('SeatPlan.seat_slips')) ?></a><?php endif ?>
                    <a href="<?= base_url('examination/seat-plans/view/' . rawurlencode($item->token)) ?>" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= esc(lang('SeatPlan.view')) ?></a>
                </td>
            </tr>
        <?php endforeach; endif ?>
        </tbody>
    </table></div>
    <?php if (!empty($pager) && $pager->getTotal() > $pager->getPerPage()): ?><div class="d-flex justify-content-end"><?= $pager->links('default', $pagerTemplate) ?></div><?php endif ?>
</div></div>

<script>$(function(){ $('.filter-submit').on('change',function(){$('#seat_plan_search').trigger('submit');}); $('#clear_filters').on('click',function(){$('#search_text,#filter_school,#filter_status').val('');$('#seat_plan_search').trigger('submit');}); });</script>
