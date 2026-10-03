<?php

namespace App\Modules\examination\Services;

use InvalidArgumentException;

class SeatAllocationService
{
    public const METHOD_SEQUENTIAL = 'sequential';
    public const METHOD_MIXED_CLASS = 'mixed_class';
    public const METHOD_RANDOM = 'random';
    public const FORMAT_NUMERIC = 'numeric';
    public const FORMAT_ALPHA_NUMERIC = 'alpha_numeric';

    /**
     * Generate deterministic, row-major room allocations without database access.
     *
     * Each student must contain id/student_id, enrollment_id, session_id and class_id.
     * Each room must contain id/room_id, capacity, rows_count and columns_count.
     */
    public function generate(
        array $students,
        array $rooms,
        string $method = self::METHOD_SEQUENTIAL,
        string $seatNumberFormat = self::FORMAT_NUMERIC,
        ?string $randomSeed = null
    ): array {
        if ($students === []) {
            throw new InvalidArgumentException('No students selected.');
        }
        if ($rooms === []) {
            throw new InvalidArgumentException('No rooms selected.');
        }
        if (!in_array($method, [self::METHOD_SEQUENTIAL, self::METHOD_MIXED_CLASS, self::METHOD_RANDOM], true)) {
            throw new InvalidArgumentException('Unsupported allocation method: ' . $method);
        }
        if (!in_array($seatNumberFormat, [self::FORMAT_NUMERIC, self::FORMAT_ALPHA_NUMERIC], true)) {
            throw new InvalidArgumentException('Unsupported seat number format: ' . $seatNumberFormat);
        }

        $students = $this->normalizeStudents($students);
        $rooms = $this->normalizeRooms($rooms);
        $availableSeats = array_sum(array_column($rooms, 'usable_capacity'));
        $studentCount = count($students);

        if ($studentCount > $availableSeats) {
            throw new InvalidArgumentException(
                sprintf('Insufficient room capacity: %d students, %d seats, shortage %d.', $studentCount, $availableSeats, $studentCount - $availableSeats)
            );
        }

        $students = $this->orderStudents($students, $method, $randomSeed);
        $positions = $this->buildSeatPositions($rooms, $seatNumberFormat);
        $allocations = [];

        foreach ($students as $index => $student) {
            $position = $positions[$index];
            $allocations[] = [
                'student_id'    => $student['student_id'],
                'enrollment_id' => $student['enrollment_id'],
                'session_id'    => $student['session_id'],
                'class_id'      => $student['class_id'],
                'section_id'    => $student['section_id'],
                'roll_no'       => $student['roll_no'],
                'room_id'       => $position['room_id'],
                'seat_no'       => $position['seat_no'],
                'row_no'        => $position['row_no'],
                'column_no'     => $position['column_no'],
            ];
        }

        return [
            'method'          => $method,
            'random_seed'     => $method === self::METHOD_RANDOM ? $randomSeed : null,
            'students_count'  => $studentCount,
            'available_seats' => $availableSeats,
            'unused_seats'    => $availableSeats - $studentCount,
            'allocations'     => $allocations,
        ];
    }

    private function orderStudents(array $students, string $method, ?string $randomSeed): array
    {
        usort($students, [$this, 'compareSequentialStudents']);
        if ($method === self::METHOD_SEQUENTIAL) {
            return $students;
        }
        if ($method === self::METHOD_RANDOM) {
            if ($randomSeed === null || !preg_match('/^[a-f0-9]{32,64}$/', $randomSeed)) {
                throw new InvalidArgumentException('A valid random seed is required for random allocation.');
            }
            usort($students, static function (array $left, array $right) use ($randomSeed): int {
                $leftHash = hash_hmac('sha256', $left['student_id'] . ':' . $left['enrollment_id'], $randomSeed);
                $rightHash = hash_hmac('sha256', $right['student_id'] . ':' . $right['enrollment_id'], $randomSeed);
                return $leftHash <=> $rightHash ?: ($left['student_id'] <=> $right['student_id']);
            });
            return $students;
        }

        return $this->mixStudents($students);
    }

    private function mixStudents(array $students): array
    {
        $groups = [];
        foreach ($students as $student) {
            $key = $student['class_id'] . ':' . ($student['section_id'] ?? 0);
            $groups[$key][] = $student;
        }

        $mixed = [];
        $previous = null;
        while ($groups !== []) {
            $bestKey = null;
            $bestScore = PHP_INT_MAX;
            $bestCount = -1;
            foreach ($groups as $key => $group) {
                $candidate = $group[0];
                $score = 0;
                if ($previous !== null) {
                    if ($candidate['class_id'] === $previous['class_id']) {
                        $score = $candidate['section_id'] === $previous['section_id'] ? 2 : 1;
                    }
                }
                $count = count($group);
                if ($score < $bestScore || ($score === $bestScore && $count > $bestCount) || ($score === $bestScore && $count === $bestCount && ($bestKey === null || strcmp($key, $bestKey) < 0))) {
                    $bestKey = $key;
                    $bestScore = $score;
                    $bestCount = $count;
                }
            }

            $student = array_shift($groups[$bestKey]);
            if ($groups[$bestKey] === []) {
                unset($groups[$bestKey]);
            }
            $mixed[] = $student;
            $previous = $student;
        }

        return $mixed;
    }

    private function normalizeStudents(array $students): array
    {
        $normalized = [];
        $seenStudentIds = [];
        $seenEnrollmentIds = [];

        foreach ($students as $student) {
            $student = is_object($student) ? (array) $student : $student;
            if (!is_array($student)) {
                throw new InvalidArgumentException('Invalid student record.');
            }

            $studentId = (int) ($student['student_id'] ?? $student['id'] ?? 0);
            $enrollmentId = (int) ($student['enrollment_id'] ?? 0);
            $sessionId = (int) ($student['session_id'] ?? 0);
            $classId = (int) ($student['class_id'] ?? 0);
            if ($studentId <= 0 || $enrollmentId <= 0 || $sessionId <= 0 || $classId <= 0) {
                throw new InvalidArgumentException('Student, enrollment, session, and class IDs are required.');
            }
            if (isset($seenStudentIds[$studentId])) {
                throw new InvalidArgumentException('Duplicate student selected: ' . $studentId);
            }
            if (isset($seenEnrollmentIds[$enrollmentId])) {
                throw new InvalidArgumentException('Duplicate enrollment selected: ' . $enrollmentId);
            }

            $seenStudentIds[$studentId] = true;
            $seenEnrollmentIds[$enrollmentId] = true;
            $normalized[] = [
                'student_id'    => $studentId,
                'enrollment_id' => $enrollmentId,
                'session_id'    => $sessionId,
                'class_id'      => $classId,
                'section_id'    => !empty($student['section_id']) ? (int) $student['section_id'] : null,
                'roll_no'       => $this->nullableString($student['roll_no'] ?? null),
                'student_name'  => $this->nullableString($student['student_name'] ?? $student['first_name'] ?? null),
            ];
        }

        return $normalized;
    }

    private function normalizeRooms(array $rooms): array
    {
        $normalized = [];
        $seenRoomIds = [];

        foreach ($rooms as $index => $room) {
            $room = is_object($room) ? (array) $room : $room;
            if (!is_array($room)) {
                throw new InvalidArgumentException('Invalid room record.');
            }

            $roomId = (int) ($room['room_id'] ?? $room['id'] ?? 0);
            $capacity = (int) ($room['capacity'] ?? 0);
            $rows = (int) ($room['rows_count'] ?? 0);
            $columns = (int) ($room['columns_count'] ?? 0);
            if ($roomId <= 0 || $capacity <= 0 || $rows <= 0 || $columns <= 0) {
                throw new InvalidArgumentException('Room ID, capacity, rows, and columns must be positive integers.');
            }
            if (isset($seenRoomIds[$roomId])) {
                throw new InvalidArgumentException('Duplicate room selected: ' . $roomId);
            }
            if ($capacity > ($rows * $columns)) {
                throw new InvalidArgumentException('Room capacity cannot exceed its row and column layout.');
            }

            $seenRoomIds[$roomId] = true;
            $normalized[] = [
                'room_id'         => $roomId,
                'capacity'        => $capacity,
                'usable_capacity' => min($capacity, $rows * $columns),
                'rows_count'      => $rows,
                'columns_count'   => $columns,
                'sort_order'      => isset($room['sort_order']) ? (int) $room['sort_order'] : $index,
            ];
        }

        usort($normalized, static function (array $left, array $right): int {
            return [$left['sort_order'], $left['room_id']] <=> [$right['sort_order'], $right['room_id']];
        });
        return $normalized;
    }

    private function compareSequentialStudents(array $left, array $right): int
    {
        $groupComparison = [$left['class_id'], $left['section_id'] ?? 0]
            <=> [$right['class_id'], $right['section_id'] ?? 0];
        if ($groupComparison !== 0) {
            return $groupComparison;
        }

        $leftRoll = $left['roll_no'] ?? '';
        $rightRoll = $right['roll_no'] ?? '';
        if ($leftRoll === '' && $rightRoll !== '') {
            return 1;
        }
        if ($rightRoll === '' && $leftRoll !== '') {
            return -1;
        }

        $rollComparison = strnatcasecmp($leftRoll, $rightRoll);
        if ($rollComparison !== 0) {
            return $rollComparison;
        }

        $nameComparison = strnatcasecmp($left['student_name'] ?? '', $right['student_name'] ?? '');
        return $nameComparison !== 0 ? $nameComparison : ($left['student_id'] <=> $right['student_id']);
    }

    private function buildSeatPositions(array $rooms, string $format): array
    {
        $positions = [];
        foreach ($rooms as $room) {
            $seatIndex = 0;
            for ($row = 1; $row <= $room['rows_count']; $row++) {
                for ($column = 1; $column <= $room['columns_count']; $column++) {
                    if ($seatIndex >= $room['usable_capacity']) {
                        break 2;
                    }
                    $seatIndex++;
                    $positions[] = [
                        'room_id'   => $room['room_id'],
                        'row_no'    => $row,
                        'column_no' => $column,
                        'seat_no'   => $this->formatSeatNumber($seatIndex, $room['usable_capacity'], $format),
                    ];
                }
            }
        }
        return $positions;
    }

    private function formatSeatNumber(int $seatIndex, int $capacity, string $format): string
    {
        if ($format === self::FORMAT_NUMERIC) {
            return (string) $seatIndex;
        }

        $width = max(2, strlen((string) $capacity));
        return 'A' . str_pad((string) $seatIndex, $width, '0', STR_PAD_LEFT);
    }

    private function nullableString($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }
}
