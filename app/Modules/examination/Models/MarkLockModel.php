<?php

namespace App\Modules\examination\Models;

use CodeIgniter\Model;

class MarkLockModel extends Model
{
    protected $table      = 'examination_mark_locks';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'school_id',
        'school_owner_uid',
        'exam_id',
        'session_id',
        'class_id',
        'section_id',
        'subject_id',
        'is_locked',
        'locked_at',
        'locked_by',
        'unlocked_at',
        'unlocked_by',
        'lock_reason',
        'remarks',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Get lock status for specific criteria
     */
    public function getLockStatus(int $school_id, int $exam_id, int $class_id, int $session_id, int $subject_id): ?object
    {
        $builder = $this->db->table($this->table);
        $builder->where('school_id', $school_id)
                ->where('exam_id', $exam_id)
                ->where('class_id', $class_id)
                ->where('session_id', $session_id)
                ->where('subject_id', $subject_id)
                ->where('is_locked', 1);

        $query = $builder->get();
        return $query->getRow();
    }

    /**
     * Get all locked marks for a school
     */
    public function getLockedMarksBySchool(int $school_id, int $exam_id = null): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('examination_mark_locks.*, 
            examination_exams.title as exam_title,
            academic_classes.title as class_title,
            academic_sections.title as section_title,
            examination_subjects.title as subject_title,
            CONCAT(edum_users.name, " ", edum_users.name) as locked_by_name,
            CONCAT(unlocker.name, " ", unlocker.name) as unlocked_by_name')
                 ->join('examination_exams', 'examination_exams.id = examination_mark_locks.exam_id', 'left')
                 ->join('academic_classes', 'academic_classes.id = examination_mark_locks.class_id', 'left')
                 ->join('academic_sections', 'academic_sections.id = examination_mark_locks.section_id', 'left')
                 ->join('examination_subjects', 'examination_subjects.id = examination_mark_locks.subject_id', 'left')
                 ->join('edum_users', 'edum_users.id = examination_mark_locks.locked_by', 'left')
                 ->join('edum_users as unlocker', 'edum_users.id = examination_mark_locks.unlocked_by', 'left')
                 ->where('examination_mark_locks.school_id', $school_id)
                 ->where('examination_mark_locks.is_locked', 1)
                 ->orderBy('examination_mark_locks.locked_at', 'DESC');

        if ($exam_id) {
            $builder->where('examination_mark_locks.exam_id', $exam_id);
        }

        $query = $builder->get();
        return $query->getResult();
    }

    /**
     * Get lock history for a specific exam/class/subject
     */
    public function getLockHistory(int $school_id, int $exam_id, int $class_id, ?int $section_id = null, ?int $subject_id = null): array
    {
        $builder = $this->db->table($this->table);
        $builder->select('examination_mark_locks.*, 
            CONCAT(edum_users.name, " ", edum_users.name) as locked_by_name,
            CONCAT(unlocker.name, " ", unlocker.name) as unlocked_by_name')
                 ->join('edum_users', 'edum_users.id = examination_mark_locks.locked_by', 'left')
                 ->join('edum_users as unlocker', 'edum_users.id = examination_mark_locks.unlocked_by', 'left')
                 ->where('examination_mark_locks.school_id', $school_id)
                 ->where('examination_mark_locks.exam_id', $exam_id)
                 ->where('examination_mark_locks.class_id', $class_id)
                 ->orderBy('examination_mark_locks.created_at', 'DESC');

        if ($section_id) {
            $builder->where('examination_mark_locks.section_id', $section_id);
        }

        if ($subject_id) {
            $builder->where('examination_mark_locks.subject_id', $subject_id);
        }

        $query = $builder->get();
        return $query->getResult();
    }

    /**
     * Check if marks are locked for given criteria
     */
    public function isLocked(int $school_id, int $exam_id, int $class_id, int $session_id, int $subject_id): bool
    {
        $lock = $this->getLockStatus($school_id, $exam_id, $class_id, $session_id, $subject_id);
        return (bool) ($lock && $lock->is_locked);
    }

    /**
     * Create or update lock record
     */
    public function setLock(array $data): bool
    {
        $lockData = [
            'school_id'        => $data['school_id'],
            'school_owner_uid' => $data['school_owner_uid'] ?? null,
            'exam_id'          => $data['exam_id'],
            'session_id'       => $data['session_id'] ?? null,
            'class_id'         => $data['class_id'],
            'section_id'       => $data['section_id'] ?? null,
            'subject_id'       => $data['subject_id'] ?? null,
            'is_locked'        => $data['is_locked'] ?? 1,
            'locked_at'        => $data['locked_at'] ?? date('Y-m-d H:i:s'),
            'locked_by'        => $data['locked_by'] ?? null,
            'unlocked_at'      => $data['unlocked_at'] ?? null,
            'unlocked_by'      => $data['unlocked_by'] ?? null,
            'lock_reason'      => $data['lock_reason'] ?? null,
            'remarks'          => $data['remarks'] ?? null,
            'updated_at'       => date('Y-m-d H:i:s'),
        ];

        // Try to find existing lock record
        $existing = $this->where('school_id', $data['school_id'])
                         ->where('exam_id', $data['exam_id'])
                         ->where('class_id', $data['class_id'])
                         ->where('session_id', $data['session_id'] ?? null)
                         ->where('subject_id', $data['subject_id'] ?? null)
                         ->first();

        if ($existing) {
            return $this->update($existing->id, $lockData);
        } else {
            $lockData['created_at'] = date('Y-m-d H:i:s');
            return $this->insert($lockData);
        }
    }
}