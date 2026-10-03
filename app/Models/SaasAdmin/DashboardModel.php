<?php

namespace App\Models\SaasAdmin;

use CodeIgniter\Model;

class DashboardModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getTotalSchools(): int
    {
        return $this->db->table('schools')
            ->where('status !=', 2)
            ->countAllResults();
    }

    public function getNewSchoolsThisMonth(): int
    {
        return $this->db->table('schools')
            ->where('status !=', 2)
            ->where('created_at >=', date('Y-m-01 00:00:00'))
            ->countAllResults();
    }

    public function getSubscriptionStatusCounts(): array
    {
        $counts = array_fill(0, 7, 0);
        $rows = $this->db->table('subscriptions')
            ->select('status, COUNT(*) AS total')
            ->where('status !=', 6)
            ->groupBy('status')
            ->get()
            ->getResult();

        foreach ($rows as $row) {
            $counts[(int) $row->status] = (int) $row->total;
        }

        return $counts;
    }

    public function getExpiringSubscriptions(int $days = 30): int
    {
        return $this->db->table('subscriptions')
            ->where('status', 2)
            ->where('end_date IS NOT NULL', null, false)
            ->where('end_date >=', date('Y-m-d 00:00:00'))
            ->where('end_date <=', date('Y-m-d 23:59:59', strtotime('+' . $days . ' days')))
            ->countAllResults();
    }

    public function getPendingPayments(): int
    {
        return $this->db->table('payments')
            ->where('status', 'pending')
            ->countAllResults();
    }

    public function getRevenueSummary(): array
    {
        $monthStart = date('Y-m-01 00:00:00');
        $monthEnd = date('Y-m-t 23:59:59');

        $totalRow = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->get()
            ->getRow();

        $monthRow = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid')
            ->where('COALESCE(paid_at, created_at) >=', $monthStart)
            ->where('COALESCE(paid_at, created_at) <=', $monthEnd)
            ->get()
            ->getRow();

        $latestPayment = $this->db->table('payments')
            ->select('currency')
            ->where('status', 'paid')
            ->orderBy('COALESCE(paid_at, created_at)', 'DESC', false)
            ->get(1)
            ->getRow();

        return [
            'total'    => (float) ($totalRow->amount ?? 0),
            'monthly'  => (float) ($monthRow->amount ?? 0),
            'currency' => strtoupper((string) ($latestPayment->currency ?? 'USD')),
        ];
    }

    public function getRevenueTrend(int $months = 6): array
    {
        $trend = [];
        $months = max(1, $months);

        for ($offset = $months - 1; $offset >= 0; $offset--) {
            $timestamp = strtotime('-' . $offset . ' months');
            $start = date('Y-m-01 00:00:00', $timestamp);
            $end = date('Y-m-t 23:59:59', $timestamp);

            $row = $this->db->table('payments')
                ->selectSum('amount')
                ->where('status', 'paid')
                ->where('COALESCE(paid_at, created_at) >=', $start)
                ->where('COALESCE(paid_at, created_at) <=', $end)
                ->get()
                ->getRow();

            $trend[] = [
                'label' => date('M', $timestamp),
                'value' => (float) ($row->amount ?? 0),
            ];
        }

        return $trend;
    }

    public function getRecentSchools(int $limit = 6): array
    {
        $schools = $this->db->table('schools')
            ->select('id, name, email, country, logo, status, created_at')
            ->where('status !=', 2)
            ->orderBy('id', 'DESC')
            ->get(max(1, $limit))
            ->getResult();

        if ($schools === []) {
            return [];
        }

        $schoolIds = array_map(static fn ($school): int => (int) $school->id, $schools);
        $relations = $this->db->table('school_user_relation')
            ->select('school_user_relation.school_id, users.id AS owner_id, users.name AS owner_name, users.email AS owner_email')
            ->join('users', 'users.id = school_user_relation.user_id', 'left')
            ->whereIn('school_user_relation.school_id', $schoolIds)
            ->orderBy('school_user_relation.id', 'ASC')
            ->get()
            ->getResult();

        $ownersBySchool = [];
        foreach ($relations as $relation) {
            $schoolId = (int) $relation->school_id;
            if (! isset($ownersBySchool[$schoolId])) {
                $ownersBySchool[$schoolId] = $relation;
            }
        }

        $ownerIds = array_values(array_unique(array_filter(array_map(
            static fn ($relation): int => (int) ($relation->owner_id ?? 0),
            $ownersBySchool
        ))));

        $subscriptionsByOwner = [];
        if ($ownerIds !== []) {
            $subscriptions = $this->db->table('subscriptions')
                ->select('subscriptions.id, subscriptions.user_id, subscriptions.status, subscriptions.end_date, subscriptions.billing_cycle, subscription_plans.name AS plan_name')
                ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
                ->whereIn('subscriptions.user_id', $ownerIds)
                ->where('subscriptions.status !=', 6)
                ->orderBy('subscriptions.id', 'DESC')
                ->get()
                ->getResult();

            foreach ($subscriptions as $subscription) {
                $ownerId = (int) $subscription->user_id;
                if (! isset($subscriptionsByOwner[$ownerId])) {
                    $subscriptionsByOwner[$ownerId] = $subscription;
                }
            }
        }

        foreach ($schools as $school) {
            $owner = $ownersBySchool[(int) $school->id] ?? null;
            $ownerId = (int) ($owner->owner_id ?? 0);
            $school->owner_name = $owner->owner_name ?? null;
            $school->owner_email = $owner->owner_email ?? null;
            $school->subscription = $subscriptionsByOwner[$ownerId] ?? null;
        }

        return $schools;
    }

    public function getRecentSubscriptions(int $limit = 6): array
    {
        return $this->db->table('subscriptions')
            ->select('subscriptions.id, subscriptions.status, subscriptions.amount, subscriptions.currency, subscriptions.billing_cycle, subscriptions.end_date, subscriptions.created_at, users.name AS user_name, users.email AS user_email, subscription_plans.name AS plan_name')
            ->join('users', 'users.id = subscriptions.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left')
            ->where('subscriptions.status !=', 6)
            ->orderBy('subscriptions.id', 'DESC')
            ->get(max(1, $limit))
            ->getResult();
    }
}
