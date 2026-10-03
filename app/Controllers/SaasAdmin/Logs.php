<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;

class Logs extends BaseController
{
    protected array $levels = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    public function index()
    {
        $header_data = [
            'page_title' => 'Audit Logs',
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $text = trim((string) $this->request->getGet('text'));
        $level = strtolower(trim((string) $this->request->getGet('level')));
        $date = trim((string) $this->request->getGet('date'));
        $show = (int) $this->request->getGet('show');
        $page = max(1, (int) ($this->request->getGet('page') ?? 1));

        if (!in_array($level, $this->levels, true)) {
            $level = '';
        }

        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = '';
        }

        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage = $show > 0 ? $show : $defaultPerPage;

        $logs = $this->filterLogs($this->readLogFiles($date), $text, $level);
        $total = count($logs);
        $items = array_slice($logs, ($page - 1) * $perPage, $perPage);

        $pager = service('pager');
        $pagination = $pager->makeLinks($page, $perPage, $total, 'custom_pagination');

        $data = compact('items', 'pagination', 'total', 'text', 'level', 'date', 'show', 'page', 'perPage');
        $data['levels'] = $this->levels;

        return view('header', $header_data)
            . view('saas_admin/logs/list', $data)
            . view('footer', $footer_data);
    }

    protected function readLogFiles(string $date = ''): array
    {
        $files = $this->getLogFiles($date);
        $logs = [];

        foreach ($files as $file) {
            foreach ($this->parseLogFile($file) as $entry) {
                $logs[] = $entry;
            }
        }

        usort($logs, static function ($a, $b) {
            return strcmp($b->logged_at, $a->logged_at);
        });

        return $logs;
    }

    protected function getLogFiles(string $date = ''): array
    {
        $logPath = rtrim(WRITEPATH . 'logs', DIRECTORY_SEPARATOR);

        if ($date !== '') {
            $file = $logPath . DIRECTORY_SEPARATOR . 'log-' . $date . '.php';
            return is_file($file) ? [$file] : [];
        }

        $files = glob($logPath . DIRECTORY_SEPARATOR . 'log-*.php') ?: [];
        rsort($files);

        return array_slice($files, 0, 30);
    }

    protected function parseLogFile(string $file): array
    {
        $entries = [];
        $current = null;
        $source = basename($file);

        foreach (file($file, FILE_IGNORE_NEW_LINES) ?: [] as $line) {
            if ($line === '' || str_starts_with($line, '<?php') || str_contains($line, 'defined(')) {
                continue;
            }

            if (preg_match('/^([A-Z]+)\s+-\s+(.+?)\s+-->\s+(.*)$/', $line, $matches)) {
                if ($current !== null) {
                    $entries[] = (object) $current;
                }

                $current = [
                    'level' => strtolower($matches[1]),
                    'logged_at' => $matches[2],
                    'message' => trim($matches[3]),
                    'source' => $source,
                ];

                continue;
            }

            if ($current !== null) {
                $current['message'] .= "\n" . trim($line);
            }
        }

        if ($current !== null) {
            $entries[] = (object) $current;
        }

        return $entries;
    }

    protected function filterLogs(array $logs, string $text, string $level): array
    {
        return array_values(array_filter($logs, static function ($log) use ($text, $level) {
            if ($level !== '' && $log->level !== $level) {
                return false;
            }

            if ($text === '') {
                return true;
            }

            $haystack = strtolower($log->level . ' ' . $log->logged_at . ' ' . $log->message . ' ' . $log->source);
            return str_contains($haystack, strtolower($text));
        }));
    }
}
