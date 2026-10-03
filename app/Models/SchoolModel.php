<?php

namespace App\Models;

use CodeIgniter\Model;

class SchoolModel extends Model
{
    protected $table      = 'schools';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'name',
        'slug',
        'country',
        'timezone',
        'address',
        'email',
        'phone_code',
        'phone',
        'logo',
        'params',
        'custom_domain',
        'status',
        'created_at',
        'updated_at',
        'created_by',
        'updated_by'
    ];

    protected $useTimestamps = true;
    protected $returnType    = 'object';

    /**
     * Get all school IDs owned by a given user.
     *
     * A school owner may own multiple schools. This returns the IDs of every
     * school created by the supplied owner, which callers can use to aggregate
     * records (e.g. students) across all of that owner's schools.
     *
     * @param int|null $ownerId The user ID (e.g. session('user_id'))
     * @return int[] List of school IDs owned by the user
     */
    public function getSchoolIdsByOwner(?int $ownerId): array
    {
        if ($ownerId === null || $ownerId <= 0) {
            return [];
        }

        $ownerId = (int) $ownerId;

        $schools = $this->select('schools.id')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $ownerId)
            ->findAll();

        return array_map(static fn($school): int => (int) $school->id, $schools);
    }
}