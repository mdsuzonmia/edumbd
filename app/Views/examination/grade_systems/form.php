<!-- Grade System Form -->
<div class="right_col" role="main">
    <?php
    $is_edit = $is_edit ?? false;
    ?>
    <div class="">
        <div class="page-title">
            <div class="title_left">
                <h3><?= $is_edit ? lang('GradeSystem.page_title_edit') : lang('GradeSystem.page_title_new') ?></h3>
            </div>
        </div>

        <div class="clearfix"></div>

        <div class="row">
            <div class="col-md-12 col-sm-12">
                <div class="card">
                    <div class="card-body">
                        <!-- System Message -->
                        <?= get_system_message(); ?>

                        <!-- Form -->
                        <?= form_open($is_edit ? 'examination/grade-systems/update/' . ($post_data['token'] ?? '') : 'examination/grade-systems/store', [
                            'class' => 'form-horizontal form-label-left',
                            'method' => 'post',
                            'data-parsley-validate' => ''
                        ]); ?>

                            <?php if ($is_edit && !empty($post_data['id'])): ?>
                                <input type="hidden" name="id" value="<?= (int) $post_data['id'] ?>">
                            <?php endif; ?>

                            <div class="form-group mb-3">
                                <label class="form-label"><?= lang('GradeSystem.title') ?> <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" value="<?= esc($post_data['title'] ?? '') ?>" required maxlength="255">
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label"><?= lang('GradeSystem.description') ?></label>
                                <textarea name="description" class="form-control" rows="3"><?= esc($post_data['description'] ?? '') ?></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label"><?= lang('GradeSystem.total_mark') ?> <span class="text-danger">*</span></label>
                                <input type="number" name="total_mark" class="form-control" value="<?= esc($post_data['total_mark'] ?? '') ?>" required step="0.01" min="0">
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label"><?= lang('GradeSystem.school') ?> <span class="text-danger">*</span></label>
                                <select name="school_id" class="form-control" required>
                                    <option value="">-- <?= lang('Common.select') ?> --</option>
                                    <?php if (!empty($school_list)): ?>
                                        <?php foreach ($school_list as $id => $name): ?>
                                            <option value="<?= (int) $id ?>" <?= ($post_data['school_id'] ?? '') == $id ? 'selected' : '' ?>>
                                                <?= esc($name) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label"><?= lang('Common.status') ?></label>
                                <select name="status" class="form-control">
                                    <option value="1" <?= ($post_data['status'] ?? 1) == 1 ? 'selected' : '' ?>><?= lang('Common.active') ?></option>
                                    <option value="0" <?= ($post_data['status'] ?? 1) == 0 ? 'selected' : '' ?>><?= lang('Common.inactive') ?></option>
                                </select>
                            </div>

                            <div class="form-group">
                                <div class="col-md-6 col-sm-6 col-xs-12 col-md-offset-3">
                                    <a href="<?= base_url('examination/grade-systems') ?>" class="btn btn-secondary">
                                        <i class="fa fa-arrow-left"></i> <?= lang('Common.btn_back') ?>
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fa fa-save"></i> <?= lang('Common.btn_save') ?>
                                    </button>
                                </div>
                            </div>

                        <?= form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>