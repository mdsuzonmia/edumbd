<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\SaasAdmin\NotificationLogModel;
use Config\Email as EmailConfig;

class Notifications extends BaseController
{
    protected NotificationLogModel $notificationLogModel;

    public function __construct()
    {
        $this->notificationLogModel = new NotificationLogModel();
    }

    public function index()
    {
        $this->ensureNotificationLogTable();

        $header_data = [
            'page_title' => 'Email / Notification',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $data = [
            'schools' => $this->getSchools(),
            'users' => $this->getUsers(),
            'history' => $this->notificationLogModel->orderBy('id', 'DESC')->findAll(20),
            'post_data' => session()->getFlashdata('post_data') ?? [],
        ];

        return view('header', $header_data)
            . view('saas_admin/notifications/index', $data)
            . view('footer', $footer_data);
    }

    public function send()
    {
        $this->ensureNotificationLogTable();

        $post = $this->request->getPost();

        $rules = [
            'recipient_type' => 'required|in_list[all_schools,active_schools,all_users,active_users,selected_schools,selected_users,custom]',
            'subject' => 'required|min_length[3]|max_length[255]',
            'message' => 'required|min_length[5]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('post_data', $post)
                ->with('error', implode('<br>', $this->validator->getErrors()));
        }

        $recipients = $this->resolveRecipients($post);

        if (empty($recipients)) {
            return redirect()->back()
                ->withInput()
                ->with('post_data', $post)
                ->with('error', 'No valid recipients found.');
        }

        $subject = trim((string) $post['subject']);
        $message = trim((string) $post['message']);
        $historyId = $this->notificationLogModel->insert([
            'recipient_type' => $post['recipient_type'],
            'subject' => $subject,
            'message' => $message,
            'total_recipients' => count($recipients),
            'sent_count' => 0,
            'failed_count' => 0,
            'status' => 'pending',
            'recipients' => json_encode(array_keys($recipients)),
            'failed_recipients' => json_encode([]),
            'sent_by' => session('user_id'),
        ]);

        $sent = 0;
        $failed = [];

        foreach ($recipients as $email => $name) {
            if ($this->sendEmail($email, $subject, $message)) {
                $sent++;
                continue;
            }

            $failed[] = $email;
        }

        $status = 'sent';
        if ($sent === 0) {
            $status = 'failed';
        } elseif (!empty($failed)) {
            $status = 'partial';
        }

        $this->notificationLogModel->update($historyId, [
            'sent_count' => $sent,
            'failed_count' => count($failed),
            'status' => $status,
            'failed_recipients' => json_encode($failed),
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        log_message(
            empty($failed) ? 'info' : 'warning',
            'SaaS notification sent by user #{user_id}. Subject: {subject}. Sent: {sent}. Failed: {failed}.',
            [
                'user_id' => session('user_id') ?? 'unknown',
                'subject' => $subject,
                'sent' => $sent,
                'failed' => count($failed),
            ]
        );

        if (!empty($failed)) {
            return redirect()->to('saas-admin/notifications')
                ->with('error', $sent . ' email(s) sent. Failed: ' . implode(', ', array_slice($failed, 0, 10)));
        }

        return redirect()->to('saas-admin/notifications')
            ->with('success', $sent . ' email(s) sent successfully.');
    }

    protected function getSchools(): array
    {
        return db_connect()->table('schools')
            ->select('id, name, email, status')
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function ensureNotificationLogTable(): void
    {
        $db = db_connect();

        if ($db->tableExists('notification_logs')) {
            return;
        }

        $forge = \Config\Database::forge();
        $forge->addField([
            'id' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'recipient_type' => [
                'type' => 'VARCHAR',
                'constraint' => 50,
            ],
            'subject' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'total_recipients' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'sent_count' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'failed_count' => [
                'type' => 'INT',
                'constraint' => 10,
                'default' => 0,
            ],
            'status' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'default' => 'pending',
            ],
            'recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'failed_recipients' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'sent_by' => [
                'type' => 'BIGINT',
                'unsigned' => true,
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $forge->addKey('id', true);
        $forge->addKey('status');
        $forge->addKey('sent_at');
        $forge->createTable('notification_logs', true);
    }

    protected function getUsers(): array
    {
        return db_connect()->table('users')
            ->select('id, name, email, status')
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->orderBy('name', 'ASC')
            ->get()
            ->getResultArray();
    }

    protected function resolveRecipients(array $post): array
    {
        $type = $post['recipient_type'] ?? '';

        return match ($type) {
            'all_schools' => $this->schoolRecipients(false),
            'active_schools' => $this->schoolRecipients(true),
            'all_users' => $this->userRecipients(false),
            'active_users' => $this->userRecipients(true),
            'selected_schools' => $this->selectedSchoolRecipients($post['school_ids'] ?? []),
            'selected_users' => $this->selectedUserRecipients($post['user_ids'] ?? []),
            'custom' => $this->customRecipients((string) ($post['custom_emails'] ?? '')),
            default => [],
        };
    }

    protected function schoolRecipients(bool $activeOnly): array
    {
        $builder = db_connect()->table('schools')
            ->select('name, email')
            ->where('email IS NOT NULL')
            ->where('email !=', '');

        if ($activeOnly) {
            $builder->where('status', 1);
        }

        return $this->formatRecipients($builder->get()->getResultArray());
    }

    protected function userRecipients(bool $activeOnly): array
    {
        $builder = db_connect()->table('users')
            ->select('name, email')
            ->where('email IS NOT NULL')
            ->where('email !=', '');

        if ($activeOnly) {
            $builder->where('status', 1);
        }

        return $this->formatRecipients($builder->get()->getResultArray());
    }

    protected function selectedSchoolRecipients(array|string $ids): array
    {
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) {
            return [];
        }

        $rows = db_connect()->table('schools')
            ->select('name, email')
            ->whereIn('id', $ids)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->get()
            ->getResultArray();

        return $this->formatRecipients($rows);
    }

    protected function selectedUserRecipients(array|string $ids): array
    {
        $ids = array_filter(array_map('intval', (array) $ids));
        if (empty($ids)) {
            return [];
        }

        $rows = db_connect()->table('users')
            ->select('name, email')
            ->whereIn('id', $ids)
            ->where('email IS NOT NULL')
            ->where('email !=', '')
            ->get()
            ->getResultArray();

        return $this->formatRecipients($rows);
    }

    protected function customRecipients(string $emails): array
    {
        $items = preg_split('/[\s,;]+/', $emails) ?: [];
        $recipients = [];

        foreach ($items as $email) {
            $email = trim($email);
            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $recipients[strtolower($email)] = $email;
            }
        }

        return $recipients;
    }

    protected function formatRecipients(array $rows): array
    {
        $recipients = [];

        foreach ($rows as $row) {
            $email = trim((string) ($row['email'] ?? ''));
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }

            $recipients[strtolower($email)] = $row['name'] ?: $email;
        }

        return $recipients;
    }

    protected function sendEmail(string $to, string $subject, string $message): bool
    {
        $config = config(EmailConfig::class);
        $email = service('email');
        $email->clear(true);

        $fromEmail = $config->fromEmail ?: 'no-reply@' . parse_url(site_url('/'), PHP_URL_HOST);
        $fromName = $config->fromName ?: (setting('application', 'app_name', 'EDUM') ?: 'EDUM');

        $email->setFrom($fromEmail, $fromName);
        $email->setTo($to);
        $email->setSubject($subject);
        $email->setMailType('html');
        $email->setMessage(nl2br(esc($message)));

        return $email->send(false);
    }
}
