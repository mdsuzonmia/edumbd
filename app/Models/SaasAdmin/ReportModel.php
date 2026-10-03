<?php

namespace App\Models\SaasAdmin;

use CodeIgniter\Model;

class ReportModel extends Model
{
    protected $db;

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getOverview(): array
    {
        return [
            'totalSchools'        => $this->db->table('schools')->countAllResults(),
            'publishedSchools'    => $this->db->table('schools')->where('status', 1)->countAllResults(),
            'totalSubscriptions'  => $this->db->table('subscriptions')->countAllResults(),
            'activeSubscriptions' => $this->db->table('subscriptions')->where('status', 2)->countAllResults(),
            'trialSubscriptions'  => $this->db->table('subscriptions')->where('status', 1)->countAllResults(),
            'expiredSubscriptions'=> $this->db->table('subscriptions')->whereIn('status', [4, 5])->countAllResults(),
            'totalRevenue'        => $this->getRevenueTotal(),
            'monthlyRevenue'      => $this->getRevenueTotal(date('Y-m-01'), date('Y-m-t')),
        ];
    }

    public function getRevenueTotal(?string $dateFrom = null, ?string $dateTo = null): float
    {
        $builder = $this->db->table('payments')
            ->selectSum('amount')
            ->where('status', 'paid');

        if ($dateFrom) {
            $builder->where('DATE(paid_at) >=', $dateFrom);
        }
        if ($dateTo) {
            $builder->where('DATE(paid_at) <=', $dateTo);
        }

        $row = $builder->get()->getRow();

        return (float) ($row->amount ?? 0);
    }

    public function getSchoolsReport(?string $status = null): array
    {
        $builder = $this->db->table('schools s')
            ->select([
                's.id',
                's.name',
                's.email',
                's.phone',
                's.status',
                's.created_at',
                'sp.name AS plan_name',
                'sub.status AS subscription_status',
                'sub.billing_cycle',
                'sub.end_date',
            ])
            ->join('subscriptions sub', 'sub.school_id = s.id', 'left')
            ->join('subscription_plans sp', 'sp.id = sub.plan_id', 'left')
            ->orderBy('s.id', 'DESC');

        if (isset($status) && $status !== '') {
            $builder->where('s.status', $status);
        }

        return $builder->get()->getResult();
    }

    public function getRevenueReport(?string $dateFrom = null, ?string $dateTo = null, ?string $gateway = null): array
    {
        $builder = $this->db->table('payments p')
            ->select([
                'p.id',
                'p.school_id',
                'p.plan_id',
                'p.gateway',
                'p.billing_cycle',
                'p.amount',
                'p.currency',
                'p.transaction_id',
                'p.status',
                'p.paid_at',
                'p.created_at',
                's.name AS school_name',
                'sp.name AS plan_name',
            ])
            ->join('schools s', 's.id = p.school_id', 'left')
            ->join('subscription_plans sp', 'sp.id = p.plan_id', 'left')
            ->where('p.status', 'paid')
            ->orderBy('p.id', 'DESC');

        if ($dateFrom) {
            $builder->where('DATE(p.paid_at) >=', $dateFrom);
        }
        if ($dateTo) {
            $builder->where('DATE(p.paid_at) <=', $dateTo);
        }
        if ($gateway) {
            $builder->where('p.gateway', $gateway);
        }

        return $builder->get()->getResult();
    }

    public function getSubscriptionsReport(?string $status = null, ?string $billingCycle = null): array
    {
        $builder = $this->db->table('subscriptions sub')
            ->select([
                'sub.id',
                'sub.status',
                'sub.is_trial',
                'sub.start_date',
                'sub.end_date',
                'sub.trial_end_date',
                'sub.amount',
                'sub.currency',
                'sub.billing_cycle',
                'sub.created_at',
                's.name AS school_name',
                's.email AS school_email',
                'sp.name AS plan_name',
            ])
            ->join('schools s', 's.id = sub.school_id', 'left')
            ->join('subscription_plans sp', 'sp.id = sub.plan_id', 'left')
            ->orderBy('sub.id', 'DESC');

        if (isset($status) && $status !== '') {
            $builder->where('sub.status', $status);
        }
        if ($billingCycle) {
            $builder->where('sub.billing_cycle', $billingCycle);
        }

        return $builder->get()->getResult();
    }
}
