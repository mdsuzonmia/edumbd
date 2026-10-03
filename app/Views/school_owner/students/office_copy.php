<?php
$students = $students ?? [];
$school = $school ?? null;
$year = $year ?? null;
$class = $class ?? null;
$filters = $filters ?? [];
$is_pdf = $is_pdf ?? false;

$schoolName = $school->name ?? 'All Schools';
$yearTitle = $year->title ?? 'All Years';
$classTitle = $class->title ?? 'All Classes';
$printedAt = date('d M, Y h:i A');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Students Office Copy</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 18px;
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #222;
        }

        .report-header {
            text-align: center;
            margin-bottom: 14px;
        }

        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .report-subtitle {
            font-size: 12px;
            margin-bottom: 3px;
        }

        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .meta-table td {
            border: 1px solid #555;
            padding: 5px 7px;
        }

        .student-table {
            width: 100%;
            border-collapse: collapse;
        }

        .student-table th,
        .student-table td {
            border: 1px solid #555;
            padding: 5px;
            vertical-align: top;
        }

        .student-table th {
            background: #f0f0f0;
            font-weight: bold;
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-muted {
            color: #666;
        }

        .nowrap {
            white-space: nowrap;
        }

        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }

            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <div class="report-title">Students Office Copy</div>
        <div class="report-subtitle"><?= esc($schoolName) ?></div>
        <div class="text-muted">Printed: <?= esc($printedAt) ?></div>
    </div>

    <table class="meta-table">
        <tr>
            <td><strong>School:</strong> <?= esc($schoolName) ?></td>
            <td><strong>Academic Year:</strong> <?= esc($yearTitle) ?></td>
            <td><strong>Class:</strong> <?= esc($classTitle) ?></td>
            <td><strong>Total Students:</strong> <?= count($students) ?></td>
        </tr>
    </table>

    <table class="student-table">
        <thead>
            <tr>
                <th width="35" class="text-center">SL</th>
                <th>Student ID</th>
                <th>Name</th>
                <th>Roll</th>
                <th>Year</th>
                <th>Class</th>
                <th>Section</th>
                <th>Department</th>
                <th>Category</th>
                <th>Shift</th>
                <th>Phone</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($students)): ?>
                <?php foreach ($students as $index => $student): ?>
                    <?php
                    $fullName = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
                    ?>
                    <tr>
                        <td class="text-center"><?= $index + 1 ?></td>
                        <td class="nowrap"><?= esc($student->student_code ?: '-') ?></td>
                        <td><?= esc($fullName ?: '-') ?></td>
                        <td class="nowrap"><?= esc($student->roll_no ?: '-') ?></td>
                        <td><?= esc($student->session_title ?? '-') ?></td>
                        <td><?= esc($student->class_title ?? '-') ?></td>
                        <td><?= esc($student->section_title ?? '-') ?></td>
                        <td><?= esc($student->department_title ?? '-') ?></td>
                        <td><?= esc($student->category_title ?? '-') ?></td>
                        <td><?= esc($student->shift_title ?? '-') ?></td>
                        <td class="nowrap"><?= esc($student->phone ?: '-') ?></td>
                        <td><?= esc($student->student_status ?? 'Active') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="12" class="text-center">No students found for the selected filters.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php if (!$is_pdf): ?>
    <script>
        window.onload = function() {
            window.print();
        };
    </script>
    <?php endif; ?>
</body>
</html>
