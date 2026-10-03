<?php

namespace App\Models;

use CodeIgniter\Model;

class AuthModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $returnType = 'object';

    protected $allowedFields = [
        'school_id',
        'name',
        'email',
        'phone',
        'photo',
        'password',
        'status',
        'last_login_at'
    ];

    /**
     * Attempt Login
     */
    public function attemptLogin(string $identifier, string $password): array
    {
        // 1️⃣ Find user
        $normalizedPhone = preg_replace('/\D+/', '', $identifier) ?? '';
        if (str_starts_with($normalizedPhone, '88') && strlen($normalizedPhone) === 13) {
            $normalizedPhone = substr($normalizedPhone, 2);
        }

        // Mobile is primary; email remains supported for existing accounts.
        $builder = $this->groupStart()->where('email', trim($identifier));
        if ($normalizedPhone !== '') {
            $builder->orWhere('phone', $normalizedPhone);
        }
        $builder->groupEnd()->where('status', 1);
        $user = $builder->first();

        if (!$user) {
            return ['status' => false, 'message' => 'Invalid credentials'];
        }

        // 2️⃣ Verify password
        if (!password_verify($password, $user->password)) {
            return ['status' => false, 'message' => 'Invalid credentials'];
        }

        // 3️⃣ Get role
        $role = $this->getUserRole($user->id);

        if (!$role) {
            return ['status' => false, 'message' => 'Role not assigned'];
        }

        // 

        // School access is no longer subscription-gated. Schools pay only when
        // ordering RESULT, SEAT_PLAN or ADMIT_CARD services.
        if (!in_array($role->slug, ['super-admin', 'student'], true)) {
            $relation = $this->db->table('school_user_relation')->where('user_id', $user->id)->get()->getRow();
            $school_id = $relation->school_id ?? null;
            if(!$school_id) {
                return ['status' => false, 'message' => 'Associated school is inactive'];
            }

        } elseif ($role->slug === 'super-admin' || $role->slug === 'student') {
            // For super admin, we can set a default school_id or skip school check
            // Here we will just set school_id to null for super admin
            $school_id = null;
        } else {
            $relation = $this->db->table('school_user_relation')->where('user_id', $user->id)->get()->getRow();
            $school_id = $relation->school_id ?? null;
            if (!$school_id) return ['status' => false, 'message' => 'Associated school is inactive'];
        }

        

        

        // 5️⃣ Update last login
        $this->update($user->id, [
            'last_login_at' => date('Y-m-d H:i:s')
        ]);

        return [
            'status' => true,
            'user'   => $user,
            'role'   => $role,
            'school_id' => $school_id
        ];
    }

    /**
     * Get User Role
     */
    private function getUserRole(int $userId)
    {
        return $this->db->table('user_roles ur')
            ->select('r.id, r.slug')
            ->join('edum_roles r', 'r.id = ur.role_id')
            ->where('ur.user_id', $userId)
            ->get()
            ->getRow();
    }

}
