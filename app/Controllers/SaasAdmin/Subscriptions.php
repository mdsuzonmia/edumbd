<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\PlanModel;
use App\Models\SchoolModel;
use App\Models\SubscriptionModel;
use App\Models\PaymentModel;
use Throwable;

class Subscriptions extends BaseController
{
    protected $SubscriptionModel;
    protected $SchoolModel;
    protected $PlanModel;
    protected $PaymentModel;

    public function __construct()
    {
        $this->SubscriptionModel = new SubscriptionModel();
        $this->SchoolModel       = new SchoolModel();
        $this->PlanModel         = new PlanModel();
        $this->PaymentModel      = new PaymentModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('Subscription.page_title_list'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        // Filters
        $text          = $this->request->getGet('text');
        $status        = $this->request->getGet('status');
        $billing_cycle = $this->request->getGet('billing_cycle');
        $show          = $this->request->getGet('show');

        $data = compact('text', 'status', 'billing_cycle', 'show');

        $this->SubscriptionModel
            ->select([
                'subscriptions.*',
                'users.name AS user_name',
                'users.email AS user_email',
                'subscription_plans.name AS plan_name',
            ])
            ->join('users', 'users.id = subscriptions.user_id', 'left')
            ->join('subscription_plans', 'subscription_plans.id = subscriptions.plan_id', 'left');

        /* ------------------------------
        * WHERE
        * ------------------------------ */
        if ($text) {
            $this->SubscriptionModel->groupStart()
                ->like('users.name', $text)
                ->orLike('users.email', $text)
                ->orLike('subscription_plans.name', $text)
                ->orLike('subscriptions.payment_gateway', $text)
                ->groupEnd();
        }
        if (isset($status) && $status !== '') {
            $this->SubscriptionModel->where('subscriptions.status', $status);
        }
        if ($billing_cycle) {
            $this->SubscriptionModel->where('subscriptions.billing_cycle', $billing_cycle);
        }

        $this->SubscriptionModel->orderBy('subscriptions.id', 'DESC');

        /* ------------------------------
        * Pagination
        * ------------------------------ */
        $defaultPerPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $perPage        = $show ? (int) $show : $defaultPerPage;

        // paginate() returns OBJECT because returnType = object
        $data['items'] = $this->SubscriptionModel->paginate($perPage);
        $data['pager'] = $this->SubscriptionModel->pager;
        $data['pagerTemplate'] = 'custom_pagination';

        return view('header', $header_data)
            . view('saas_admin/subscriptions/list', $data)
            . view('footer', $footer_data);
    }

    public function create()
    {
        $header_data['page_title'] = lang('Subscription.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['subscription_data'] = '';
        $data['plans']             = $this->PlanModel->where('status', 1)->findAll();
        $data['is_edit']           = false;

        return view('header', $header_data)
            . view('saas_admin/subscriptions/form', $data)
            . view('footer', $footer_data);
    }

    public function store()
    {
        // Post data
        $post_data = $this->request->getPost();

        // Set validation rules
        $validationRule = [
            'plan_id'       => 'required',
            'status'        => 'required',
            'start_date'    => 'required|valid_date',
            'billing_cycle' => 'required',
            'amount'        => 'required|decimal',
            'currency'      => 'required',
        ];

        $header_data['page_title'] = lang('Subscription.page_title_new');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $form_data['post_data'] = $post_data;
        $form_data['schools']   = $this->SchoolModel->where('status', 1)->findAll();
        $form_data['plans']     = $this->PlanModel->where('status', 1)->findAll();
        $form_data['is_edit']   = ! empty($post_data['id']);

        if (! $this->validate($validationRule)) {
            $form_data['validation'] = $this->validator;

            return view('header', $header_data)
                . view('saas_admin/subscriptions/form', $form_data)
                . view('footer', $footer_data);
        }

        // Get logged in user id from session
        $logget_user_id = session()->get('user_id');

        // Set subscription data
        $subscription_data = [
            'user_id'         => $post_data['user_id'],
            'plan_id'         => $post_data['plan_id'],
            'status'          => $post_data['status'],
            'is_trial'        => $post_data['is_trial'] ?? 0,
            'start_date'      => $post_data['start_date'],
            'end_date'        => ! empty($post_data['end_date']) ? $post_data['end_date'] : null,
            'trial_end_date'  => ! empty($post_data['trial_end_date']) ? $post_data['trial_end_date'] : null,
            'amount'          => $post_data['amount'],
            'currency'        => $post_data['currency'],
            'billing_cycle'   => $post_data['billing_cycle'],
            'payment_gateway' => ! empty($post_data['payment_gateway']) ? $post_data['payment_gateway'] : null,
            'meta'            => ! empty($post_data['meta']) ? $post_data['meta'] : null,
            'updated_at'      => date('Y-m-d H:i:s'),
            'updated_by'      => $logget_user_id,
        ];

        // Check exit ID for update or insert
        if (! empty($post_data['id'])) {
            // Get Update subscription
            $exit_id = $post_data['id'];
            $this->SubscriptionModel->update($exit_id, $subscription_data);
        } else {
            // Get Insert subscription
            $subscription_data['created_at'] = date('Y-m-d H:i:s');
            $subscription_data['created_by'] = $logget_user_id;
            $this->SubscriptionModel->insert($subscription_data);
        }

        // Redirect to subscription list with success message
        return redirect()->to('saas-admin/subscriptions')->with('success', lang('Subscription.sys_saved'));
    }

    public function view($subscription_id)
    {
        $header_data['page_title'] = lang('Subscription.page_title_view');
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data['subscription_data'] = $this->SubscriptionModel->getSubscriptionDetails($subscription_id);
        $data['related_payments'] = $this->PaymentModel->getPaymentsBySubscription($subscription_id);

        return view('header', $header_data)
            . view('saas_admin/subscriptions/view', $data)
            . view('footer', $footer_data);
    }

    public function renew($subscription_id)
    {
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            $end_date = $this->request->getPost('end_date');

            if ($subscription_id && $end_date) {
                $subscription_data = [
                    'status'     => 2,
                    'end_date'   => $end_date,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => session()->get('user_id'),
                ];

                if ($this->SubscriptionModel->update($subscription_id, $subscription_data)) {
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Subscription.sys_renewed'));
                } else {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Subscription.sys_error_renewed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Subscription.sys_error_renewed');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function cancel($subscription_id)
    {
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            if ($subscription_id) {
                $subscription_data = [
                    'status'     => 5,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => session()->get('user_id'),
                ];

                if ($this->SubscriptionModel->update($subscription_id, $subscription_data)) {
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Subscription.sys_cancelled'));
                } else {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Subscription.sys_error_cancelled'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Subscription.sys_error_cancelled');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function changeStatus($subscription_id)
    {
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            $status = $this->request->getPost('status');

            if ($subscription_id && isset($status) && $status !== '') {
                $subscription_data = [
                    'status'     => $status,
                    'updated_at' => date('Y-m-d H:i:s'),
                    'updated_by' => session()->get('user_id'),
                ];

                try {
                    if ($this->SubscriptionModel->update($subscription_id, $subscription_data)) {
                        $response['status'] = true;
                        $html .= message_generator('success', lang('Subscription.sys_status_changed'));
                    } else {
                        $response['status'] = false;
                        $html .= message_generator('error', lang('Subscription.sys_error_status_changed'));
                    }
                } catch (Throwable $e) {
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Subscription.sys_error_status_changed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Subscription.sys_error_status_changed');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash())
                ->setJSON($response);
        }
    }

    public function trash()
    {
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            // Retrieve the data sent via AJAX
            $subscription_id = $this->request->getPost('subscription_id');

            if ($subscription_id) {
                $subscription_data['status'] = 6;
                $subscription_data['updated_at'] = date('Y-m-d H:i:s');
                $subscription_data['updated_by'] = session()->get('user_id');
                if ($this->SubscriptionModel->update($subscription_id, $subscription_data)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_trashed'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_trashed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_trashed');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

    public function empty_trash()
    {
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            // Retrieve the data sent via AJAX
            $subscription_id = $this->request->getPost('subscription_id');

            if ($subscription_id) {
                // Get Delete Subscription
                if ($this->SubscriptionModel->delete($subscription_id)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_empty_trashed'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_empty_trashed'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }

    public function restore()
    {
        // Check if the request is an AJAX request
        if ($this->request->isAJAX()) {
            $response = array('status' => true);
            $html     = '';

            // Retrieve the data sent via AJAX
            $subscription_id = $this->request->getPost('subscription_id');

            if ($subscription_id) {
                $subscription_data['status'] = 2;
                $subscription_data['updated_at'] = date('Y-m-d H:i:s');
                $subscription_data['updated_by'] = session()->get('user_id');
                if ($this->SubscriptionModel->update($subscription_id, $subscription_data)) {
                    // Success message
                    $response['status'] = true;
                    $html .= message_generator('success', lang('Common.data_restored'));
                } else {
                    // Error message
                    $response['status'] = false;
                    $html .= message_generator('error', lang('Common.data_error_restored'));
                }
            } else {
                $response['status'] = false;
                $mesg = lang('Common.data_error_id_missing') . '<br>';
                $mesg .= lang('Common.data_error_deleted');
                $html .= message_generator('error', $mesg);
            }

            $response['html'] = $html;

            // Send the response back to the client
            return $this->response
                ->setHeader('X-CSRF-TOKEN', csrf_hash()) // Send new CSRF token in header
                ->setJSON($response); // Send response as JSON
        }
    }
}
