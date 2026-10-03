<?php
$rendered_content = $rendered_content ?? '';
$template_style   = $template_style ?? '';
$template_bg      = $template_bg ?? '';
$orientation      = $orientation ?? 'portrait';
$school_name      = $school_name ?? 'Transcript';
$not_found        = $not_found ?? false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($school_name) ?> - Academic Transcript</title>
    <style type="text/css">
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            min-height: 100%;
        }

        body {
            background: #f4f4f4;
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        .result-card {
            background: #fff;
            margin: 0 auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .result-body {
            margin: 0 auto;
            box-sizing: border-box;
        }

        <?php if ($orientation === 'landscape'): ?>
        .result-card,
        .result-body {
            width: 348mm;
            min-height: 210mm;
        }

        .result-body {
            padding: 10mm 15mm;
        }

        .result-inner-wrap {
            padding: 20px;
        }
        <?php else: ?>
        .result-card,
        .result-body {
            width: 210mm;
            min-height: 297mm;
        }

        .result-body {
            padding: 10mm 15mm;
        }
        <?php endif; ?>

        <?php if ($template_bg): ?>
        .result-body {
            background-image: url('<?= esc($template_bg, 'attr') ?>');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        <?php endif; ?>

        .subject-academic-table th,
        .subject-academic-table td {
            text-align: center;
            vertical-align: middle;
        }

        .subject-academic-table td.subject-name {
            text-align: left;
        }

        .transcript-warning {
            margin: 20px;
            padding: 15px;
            color: #664d03;
            background: #fff3cd;
            border: 1px solid #ffecb5;
        }

        <?= $template_style ?>

        @media (max-width: 900px) {
            body {
                padding: 0;
                overflow-x: auto;
            }

            .result-card {
                box-shadow: none;
            }
        }

        @media print {
            @page {
                size: A4 <?= $orientation === 'landscape' ? 'landscape' : 'portrait' ?>;
                margin: 0;
            }

            body {
                background: #fff;
                padding: 0;
            }

            .result-card {
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <main class="result-card">
        <?php if ($not_found && !$rendered_content): ?>
            <div class="transcript-warning">
                Transcript not generated for this student. Please generate final results first.
            </div>
        <?php else: ?>
            <div class="result-body">
                <div class="result-inner-wrap">
                    <?= $rendered_content ?>
                </div>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
