<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Libraries\Markdown;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * Controller that renders the Markdown documentation in /docs/user-guide as
 * styled HTML pages so they can be browsed from the website.
 */
class Docs extends BaseController
{
    private const DOC_DIR = 'docs/user-guide';

    /**
     * Guides that are written but not yet published. They are kept in the docs
     * folder for future use, but are hidden from the sidebar and cannot be
     * opened directly. To publish a guide later, remove its slug from this list
     * (and restore its entries in the guides' Markdown if needed).
     *
     * @var list<string>
     */
    private const HIDDEN_SLUGS = ['school-admin', 'teacher', 'parent'];

    /**
     * Landing page: shows the guide index (README.md) plus the sidebar.
     */
    public function index()
    {
        return $this->renderPage('README.md', '', 'Documentation Index');
    }

    /**
     * Show a specific guide by its slug (e.g. "examinations").
     */
    public function view(string $slug)
    {
        $slug = strtolower(trim($slug));
        $docs = $this->docsList();

        foreach ($docs as $doc) {
            if ($doc['slug'] === $slug) {
                return $this->renderPage($doc['file'], $slug, $doc['title']);
            }
        }

        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Build and return the HTML page for a documentation file.
     */
    private function renderPage(string $file, string $activeSlug, string $title): string
    {
        $path = ROOTPATH . self::DOC_DIR . '/' . $file;
        if (!is_file($path)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $docs = $this->docsList();

        // Rewrite relative cross-links between guide files to full, working URLs
        // (base_url() includes the app's subfolder, e.g. http://localhost/edum/docs/...).
        $markdown = (string) file_get_contents($path);
        foreach ($docs as $doc) {
            $markdown = str_replace('](' . $doc['file'] . ')', '](' . base_url('docs/' . $doc['slug']) . ')', $markdown);
        }
        $markdown = str_replace('](README.md)', '](' . base_url('docs') . ')', $markdown);

        $pageTitle = esc($title) . ' - Documentation';

        $header_data['page_title'] = $pageTitle;
        $header_data['body_class'] = 'login';
        $header_data['admin_area'] = 'no';
        $footer_data['admin_area'] = 'no';

        $data = [
            'docs'      => $docs,
            'active'    => $activeSlug,
            'content'   => Markdown::toHtml($markdown),
            'doc_title' => $title,
        ];

        return view('header', $header_data)
            . view('pages/docs', $data)
            . view('footer', $footer_data);
    }

    /**
     * Ordered list of documentation guides with file name, slug and title.
     *
     * @return array<int, array{file:string, slug:string, title:string}>
     */
    private function docsList(): array
    {
        $dir   = ROOTPATH . self::DOC_DIR;
        $files = glob($dir . '/*.md');

        if ($files === false) {
            $files = [];
        }
        sort($files);

        $out = [];
        foreach ($files as $path) {
            $base = basename($path, '.md');
            if (strcasecmp($base, 'README') === 0) {
                continue;
            }
            $slug  = preg_replace('/^\d+[-_]+/', '', strtolower($base));
            if (in_array($slug, self::HIDDEN_SLUGS, true)) {
                continue;
            }
            $out[] = [
                'file'  => basename($path),
                'slug'  => $slug,
                'title' => $this->docTitle($path, $base),
            ];
        }

        return $out;
    }

    /**
     * Derive a friendly title from the first H1 of a file.
     */
    private function docTitle(string $path, string $fallback): string
    {
        $handle = @fopen($path, 'r');
        $title  = '';
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (preg_match('/^#\s+(.+)$/', trim($line), $m)) {
                    $title = trim($m[1]);
                    break;
                }
            }
            fclose($handle);
        }

        // Strip a leading number like "80 — " or "80 - " from the heading.
        $title = preg_replace('/^\d+\s*[—–-]\s*/u', '', $title);

        return $title !== '' ? $title : $fallback;
    }
}
