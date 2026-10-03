<?php
$post_data = $post_data ?? [];
$recipient_type = $post_data['recipient_type'] ?? '';
$subject = $post_data['subject'] ?? '';
$message = $post_data['message'] ?? '';
$custom_emails = $post_data['custom_emails'] ?? '';
$selected_schools = (array) ($post_data['school_ids'] ?? []);
$selected_users = (array) ($post_data['user_ids'] ?? []);
$status_badges = [
    'pending' => 'badge text-bg-secondary',
    'sent' => 'badge text-bg-success',
    'partial' => 'badge text-bg-warning',
    'failed' => 'badge text-bg-danger',
];
?>

<?= form_open('saas-admin/notifications/send', [
    'class' => 'form-horizontal form-label-left',
    'id' => 'notification_form',
    'method' => 'post',
    'data-parsley-validate' => '',
]); ?>

<div class="row mb-3">
    <div class="col-sm-6">
        <h3 class="text-secondary mb-0"><i class="bi bi-bell"></i> Email / Notification</h3>
    </div>
    <div class="col-sm-6 text-end">
        <button type="submit" class="btn btn-success"><i class="fa fa-paper-plane"></i> Send Notification</button>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <?= get_system_message(); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">Recipients</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Send To</label>
                    <select name="recipient_type" id="recipient_type" class="form-control" required>
                        <option value="">Choose recipients</option>
                        <option value="active_schools" <?= $recipient_type === 'active_schools' ? 'selected' : '' ?>>Active Schools</option>
                        <option value="all_schools" <?= $recipient_type === 'all_schools' ? 'selected' : '' ?>>All Schools</option>
                        <option value="active_users" <?= $recipient_type === 'active_users' ? 'selected' : '' ?>>Active Users</option>
                        <option value="all_users" <?= $recipient_type === 'all_users' ? 'selected' : '' ?>>All Users</option>
                        <option value="selected_schools" <?= $recipient_type === 'selected_schools' ? 'selected' : '' ?>>Selected Schools</option>
                        <option value="selected_users" <?= $recipient_type === 'selected_users' ? 'selected' : '' ?>>Selected Users</option>
                        <option value="custom" <?= $recipient_type === 'custom' ? 'selected' : '' ?>>Custom Emails</option>
                    </select>
                </div>

                <div class="mb-3 recipient-panel" id="panel_schools">
                    <label class="form-label">Schools</label>
                    <select name="school_ids[]" class="form-control" multiple size="8">
                        <?php foreach ($schools as $school): ?>
                            <option value="<?= (int) $school['id'] ?>" <?= in_array((string) $school['id'], $selected_schools, true) ? 'selected' : '' ?>>
                                <?= esc($school['name']) ?> - <?= esc($school['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 recipient-panel" id="panel_users">
                    <label class="form-label">Users</label>
                    <select name="user_ids[]" class="form-control" multiple size="8">
                        <?php foreach ($users as $user): ?>
                            <option value="<?= (int) $user['id'] ?>" <?= in_array((string) $user['id'], $selected_users, true) ? 'selected' : '' ?>>
                                <?= esc($user['name']) ?> - <?= esc($user['email']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="mb-3 recipient-panel" id="panel_custom">
                    <label class="form-label">Custom Emails</label>
                    <textarea name="custom_emails" class="form-control" rows="6" placeholder="one@example.com, two@example.com"><?= esc($custom_emails) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">Message</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Subject</label>
                    <input type="text" name="subject" class="form-control" value="<?= esc($subject) ?>" required maxlength="255">
                </div>

                <div class="mb-3">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-control" rows="14" required><?= esc($message) ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>

<?= form_close(); ?>

<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">Sent History</div>
            <div class="card-body">
                <table class="table student-table student-list">
                    <thead>
                        <tr>
                            <th width="45"><?= lang('Common.th_sn') ?></th>
                            <th>Subject</th>
                            <th width="150">Recipients</th>
                            <th width="110" class="text-center">Status</th>
                            <th width="160" class="text-center">Sent At</th>
                            <th width="220">Failed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($history)): ?>
                            <?php foreach ($history as $key => $item): ?>
                                <?php
                                $status = strtolower((string) $item->status);
                                $badge = $status_badges[$status] ?? 'badge text-bg-secondary';
                                $failed_recipients = json_decode((string) $item->failed_recipients, true);
                                $failed_recipients = is_array($failed_recipients) ? $failed_recipients : [];
                                ?>
                                <tr>
                                    <td><?= $key + 1 ?></td>
                                    <td>
                                        <p class="mb-0"><b><?= esc($item->subject) ?></b></p>
                                        <small class="text-muted"><?= esc(str_replace('_', ' ', ucwords($item->recipient_type, '_'))) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge text-bg-primary"><?= (int) $item->sent_count ?> sent</span>
                                        <span class="badge text-bg-danger"><?= (int) $item->failed_count ?> failed</span>
                                        <span class="badge text-bg-secondary"><?= (int) $item->total_recipients ?> total</span>
                                    </td>
                                    <td class="text-center"><span class="<?= esc($badge) ?>"><?= esc(ucfirst($status)) ?></span></td>
                                    <td class="text-center"><?= $item->sent_at ? esc(date('d M, Y H:i', strtotime($item->sent_at))) : '-' ?></td>
                                    <td>
                                        <?php if (!empty($failed_recipients)): ?>
                                            <details>
                                                <summary><?= count($failed_recipients) ?> failed email(s)</summary>
                                                <div class="small text-muted mt-2" style="white-space: normal; word-break: break-word;">
                                                    <?= esc(implode(', ', $failed_recipients)) ?>
                                                </div>
                                            </details>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center text-muted">No notification history found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function updateRecipientPanels() {
    const type = document.getElementById('recipient_type').value;
    document.querySelectorAll('.recipient-panel').forEach(panel => panel.style.display = 'none');

    if (type === 'selected_schools') {
        document.getElementById('panel_schools').style.display = 'block';
    }

    if (type === 'selected_users') {
        document.getElementById('panel_users').style.display = 'block';
    }

    if (type === 'custom') {
        document.getElementById('panel_custom').style.display = 'block';
    }
}

document.getElementById('recipient_type').addEventListener('change', updateRecipientPanels);
updateRecipientPanels();
</script>
