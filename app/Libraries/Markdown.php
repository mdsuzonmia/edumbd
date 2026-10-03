<?php

namespace App\Libraries;

/**
 * Lightweight Markdown -> HTML renderer.
 *
 * Tailored to the Edum user-guide documentation (docs/user-guide/*.md). It is a
 * self-contained implementation with no external dependencies and supports the
 * subset of Markdown used by those documents:
 *
 *   - ATX headings (# .. ######)
 *   - paragraphs (soft line breaks become <br>)
 *   - fenced code blocks (``` lang ... ```)
 *   - block quotes (> ...)
 *   - unordered (-, *, +) and ordered (1.) lists
 *   - GFM-style tables (header row + --- separator row)
 *   - horizontal rules
 *   - inline formatting: **bold**, *italic*, `inline code`, [text](url)
 *
 * All raw text is HTML-escaped before markers are applied so the output is safe.
 */
class Markdown
{
    /**
     * Convert a Markdown string to HTML.
     */
    public static function toHtml(string $markdown): string
    {
        $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
        $lines    = explode("\n", $markdown);
        $html     = '';
        $i        = 0;
        $count    = count($lines);

        while ($i < $count) {
            $line = rtrim($lines[$i]);

            // Skip blank lines.
            if (trim($line) === '') {
                $i++;
                continue;
            }

            // Fenced code block.
            if (preg_match('/^```\s*([\w+.\-]*)\s*$/', $line, $m)) {
                $lang = $m[1];
                $i++;
                $code = [];
                while ($i < $count && !preg_match('/^```\s*$/', rtrim($lines[$i]))) {
                    $code[] = $lines[$i];
                    $i++;
                }
                $i++; // skip the closing fence
                $body = htmlspecialchars(implode("\n", $code), ENT_NOQUOTES, 'UTF-8');
                $html .= '<pre><code'
                    . ($lang !== '' ? ' class="language-' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '"' : '')
                    . '>' . $body . "</code></pre>\n";
                continue;
            }

            // Heading.
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m)) {
                $level = strlen($m[1]);
                $html .= '<h' . $level . '>' . self::inline($m[2]) . "</h$level>\n";
                $i++;
                continue;
            }

            // Horizontal rule (at least three identical dashes/stars/underscores).
            if (preg_match('/^\s*([-*_])\s*(\1\s*){2,}$/', $line)) {
                $html .= "<hr>\n";
                $i++;
                continue;
            }

            // Table: a header line followed by a GFM separator row.
            if (strpos($line, '|') !== false && $i + 1 < $count && self::isTableSeparator($lines[$i + 1])) {
                $headers = self::tableCells($line);
                $i += 2; // skip header + separator
                $rows = [];
                while ($i < $count && trim($lines[$i]) !== '' && strpos($lines[$i], '|') !== false) {
                    $rows[] = self::tableCells($lines[$i]);
                    $i++;
                }
                $html .= self::tableHtml($headers, $rows);
                continue;
            }

            // Block quote.
            if (str_starts_with(ltrim($line), '>')) {
                $quote = [];
                while ($i < $count && str_starts_with(ltrim($lines[$i]), '>')) {
                    $quote[] = preg_replace('/^>\s?/', '', ltrim($lines[$i]));
                    $i++;
                }
                $html .= '<blockquote>' . self::toHtml(implode("\n", $quote)) . "</blockquote>\n";
                continue;
            }

            // Unordered / ordered list.
            if (preg_match('/^\s*([-*+])\s+\S/', $line)) {
                $html .= self::listHtml($lines, $i, 'ul', '/^\s*[-*+]\s+(.*)$/');
                continue;
            }
            if (preg_match('/^\s*\d+\.\s+\S/', $line)) {
                $html .= self::listHtml($lines, $i, 'ol', '/^\s*\d+\.\s+(.*)$/');
                continue;
            }

            // Paragraph: gather consecutive non-block lines.
            $para = [];
            while ($i < $count) {
                $pl = rtrim($lines[$i]);
                if (trim($pl) === '' || self::startsBlock($pl, $i, $lines)) {
                    break;
                }
                $para[] = trim($pl);
                $i++;
            }
            $html .= '<p>' . self::inline(implode("\n", $para)) . "</p>\n";
        }

        return $html;
    }

    /**
     * Render a consecutive list block (given an index pointing at the first
     * item) and advance $i past the block.
     *
     * @param array<int,string> $lines
     */
    private static function listHtml(array &$lines, int &$i, string $tag, string $pattern): string
    {
        $count = count($lines);
        $items = [];
        while ($i < $count && preg_match('/^\s*([-*+])\s+\S|^\s*\d+\.\s+\S/', $lines[$i])) {
            if (preg_match($pattern, $lines[$i], $m)) {
                $items[] = self::inline($m[1]);
            }
            $i++;
        }
        $out = '<' . $tag . '>';
        foreach ($items as $item) {
            $out .= '<li>' . $item . '</li>';
        }
        return $out . "</$tag>\n";
    }

    /**
     * Whether a line begins a block that closes a paragraph.
     *
     * @param array<int,string> $lines
     */
    private static function startsBlock(string $line, int $index, array $lines): bool
    {
        if (preg_match('/^(#{1,6})\s+/', $line)) {
            return true;
        }
        if (preg_match('/^```/', $line)) {
            return true;
        }
        if (str_starts_with(ltrim($line), '>')) {
            return true;
        }
        if (preg_match('/^\s*([-*+])\s+\S/', $line)) {
            return true;
        }
        if (preg_match('/^\s*\d+\.\s+\S/', $line)) {
            return true;
        }
        if (preg_match('/^\s*([-*_])\s*(\1\s*){2,}$/', $line)) {
            return true;
        }
        if (strpos($line, '|') !== false && $index + 1 < count($lines) && self::isTableSeparator($lines[$index + 1])) {
            return true;
        }
        return false;
    }

    private static function isTableSeparator(string $line): bool
    {
        $line = trim($line);
        if ($line === '' || strpos($line, '|') === false) {
            return false;
        }
        foreach (self::tableCells($line) as $cell) {
            if (!preg_match('/^:?-{1,}:?$/', $cell)) {
                return false;
            }
        }
        return true;
    }

    /** Split a table line into its trimmed cells. */
    private static function tableCells(string $line): array
    {
        $line  = trim($line);
        $cells = explode('|', $line);
        if (str_starts_with($line, '|')) {
            array_shift($cells);
        }
        if (str_ends_with($line, '|')) {
            array_pop($cells);
        }
        return array_map('trim', $cells);
    }

    /** Render a table with a header row and body rows. */
    private static function tableHtml(array $headers, array $rows): string
    {
        $out = "<table>\n<thead>\n<tr>";
        foreach ($headers as $h) {
            $out .= '<th>' . self::inline($h) . '</th>';
        }
        $out .= "</tr>\n</thead>\n<tbody>\n";
        foreach ($rows as $row) {
            $out .= '<tr>';
            foreach ($row as $cell) {
                $out .= '<td>' . self::inline($cell) . '</td>';
            }
            $out .= "</tr>\n";
        }
        return $out . "</tbody>\n</table>\n";
    }

    /**
     * Convert a single line of inline Markdown to HTML.
     * Raw text is HTML-escaped first, then markers are applied.
     */
    private static function inline(string $text): string
    {
        $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        return self::formatEscaped($text);
    }

    /** Apply inline formatting to already-escaped text. */
    private static function formatEscaped(string $text): string
    {
        // Links (label may itself contain inline formatting).
        $text = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)\s]+)\)/',
            static function ($m) {
                $label = self::formatEscaped($m[1]);
                $href  = htmlspecialchars($m[2], ENT_QUOTES, 'UTF-8');
                return '<a href="' . $href . '">' . $label . '</a>';
            },
            $text
        );

        // Inline code (protect with a placeholder so bold/italic can't touch it).
        $code = [];
        $text = preg_replace_callback('/`([^`]+)`/', static function ($m) use (&$code) {
            $key         = "\x00C" . count($code) . "\xFF";
            $code[$key] = '<code>' . htmlspecialchars($m[1], ENT_NOQUOTES, 'UTF-8') . '</code>';
            return $key;
        }, $text);

        // Bold then italic.
        $text = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*([^*]+)\*/', '<em>$1</em>', $text);

        // Restore inline code placeholders.
        foreach ($code as $key => $value) {
            $text = str_replace($key, $value, $text);
        }

        // Soft breaks.
        $text = str_replace("\n", "<br>\n", $text);

        return $text;
    }
}

