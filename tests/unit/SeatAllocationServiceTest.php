<?php

namespace Tests\Unit;

use App\Modules\examination\Services\SeatAllocationService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SeatAllocationServiceTest extends TestCase
{
    private SeatAllocationService $service;

    protected function setUp(): void
    {
        $this->service = new SeatAllocationService();
    }

    public function testSequentialAllocationUsesClassSectionAndNaturalRollOrder(): void
    {
        $students = [
            $this->student(3, 103, 2, 1, '2'),
            $this->student(2, 102, 1, 1, '10'),
            $this->student(1, 101, 1, 1, '2'),
            $this->student(4, 104, 1, 2, null),
        ];
        $rooms = [$this->room(10, 4, 2, 2)];

        $result = $this->service->generate($students, $rooms);

        self::assertSame([1, 2, 4, 3], array_column($result['allocations'], 'student_id'));
        self::assertSame(['1', '2', '3', '4'], array_column($result['allocations'], 'seat_no'));
        self::assertSame(0, $result['unused_seats']);
    }

    public function testAllocationContinuesIntoNextRoomInConfiguredOrder(): void
    {
        $students = [
            $this->student(1, 101, 1, 1, '1'),
            $this->student(2, 102, 1, 1, '2'),
            $this->student(3, 103, 1, 1, '3'),
        ];
        $rooms = [
            $this->room(20, 2, 1, 2, 2),
            $this->room(10, 2, 1, 2, 1),
        ];

        $result = $this->service->generate($students, $rooms, 'sequential', 'alpha_numeric');

        self::assertSame([10, 10, 20], array_column($result['allocations'], 'room_id'));
        self::assertSame(['A01', 'A02', 'A01'], array_column($result['allocations'], 'seat_no'));
        self::assertSame(1, $result['unused_seats']);
    }

    public function testCapacityShortageIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('shortage 1');

        $this->service->generate([
            $this->student(1, 101, 1, 1, '1'),
            $this->student(2, 102, 1, 1, '2'),
            $this->student(3, 103, 1, 1, '3'),
        ], [$this->room(10, 2, 1, 2)]);
    }

    public function testDuplicateStudentIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate student');

        $this->service->generate([
            $this->student(1, 101, 1, 1, '1'),
            $this->student(1, 102, 1, 1, '2'),
        ], [$this->room(10, 2, 1, 2)]);
    }

    public function testInvalidRoomLayoutIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot exceed');

        $this->service->generate(
            [$this->student(1, 101, 1, 1, '1')],
            [$this->room(10, 3, 1, 2)]
        );
    }

    public function testMixedClassSeparatesClassesWhenPossible(): void
    {
        $students = [
            $this->student(1, 101, 1, 1, '1'),
            $this->student(2, 102, 1, 1, '2'),
            $this->student(3, 103, 2, 1, '1'),
            $this->student(4, 104, 2, 1, '2'),
            $this->student(5, 105, 3, 1, '1'),
        ];

        $result = $this->service->generate($students, [$this->room(10, 5, 1, 5)], 'mixed_class');
        $classes = array_column($result['allocations'], 'class_id');

        for ($index = 1; $index < count($classes); $index++) {
            self::assertNotSame($classes[$index - 1], $classes[$index]);
        }
    }

    public function testRandomAllocationIsStableForSameSeed(): void
    {
        $students = [];
        for ($id = 1; $id <= 8; $id++) {
            $students[] = $this->student($id, 100 + $id, 1, 1, (string) $id);
        }
        $seed = str_repeat('a', 32);

        $first = $this->service->generate($students, [$this->room(10, 8, 2, 4)], 'random', 'numeric', $seed);
        $second = $this->service->generate(array_reverse($students), [$this->room(10, 8, 2, 4)], 'random', 'numeric', $seed);

        self::assertSame(array_column($first['allocations'], 'student_id'), array_column($second['allocations'], 'student_id'));
    }

    private function student(int $id, int $enrollmentId, int $classId, int $sectionId, ?string $roll): array
    {
        return [
            'student_id' => $id,
            'enrollment_id' => $enrollmentId,
            'session_id' => 2026,
            'class_id' => $classId,
            'section_id' => $sectionId,
            'roll_no' => $roll,
            'student_name' => 'Student ' . $id,
        ];
    }

    private function room(int $id, int $capacity, int $rows, int $columns, int $sortOrder = 0): array
    {
        return [
            'room_id' => $id,
            'capacity' => $capacity,
            'rows_count' => $rows,
            'columns_count' => $columns,
            'sort_order' => $sortOrder,
        ];
    }
}
