<?php
 	
 	

use App\Models\SubscriptionModel;
use App\Models\PlanModel;

// load school and student models
use App\Models\SchoolModel;
use App\Models\StudentModel;

    /**
 	 * Check subscription status and redirect if expired.
 	 *
 	 * @param string $redirect_url The URL to redirect to if subscription is expired
 	 * @return \CodeIgniter\HTTP\RedirectResponse|array|null Returns redirect response for expired subscription, JSON response for AJAX, or null if valid
 	 */
 	if (!function_exists('check_subscription')) {
		function check_subscription(string $redirect_url) {
			// Teacher-facing school accounts are billed per service. Historical
			// subscriptions remain available to admins but no longer gate work.
			if (session('role') !== 'super-admin') {
				return null;
			}
 			$subscription_data = calculateSubscriptionPercent();
 			
 			if (isset($subscription_data['is_expired']) && $subscription_data['is_expired']) {
 				$message = $subscription_data['message'] ?? 'Subscription expired.';
 				
 				// For AJAX requests, return JSON response
 				if (service('request')->isAJAX()) {
 					$response = [
 						'status' => false,
 						'html' => message_generator('error', $message),
						'message' => $message
 					];
 					return $response;
 				}
 				
 				// For regular requests, redirect with error message
 				return redirect()->to($redirect_url)
 					->with('error', $message);
 			}
 			
 			return null;
 		}
 	}

	// Get check subscription plan
	// Check subscription plan
if (!function_exists('check_subscription_plan')) {
    function check_subscription_plan($task)
    {
		if (session('role') !== 'super-admin') {
			return ['status' => true, 'plan' => (object)['student_limit' => 0, 'branch_limit' => 0]];
		}
        $user_id = session('user_id') ? (int) session('user_id') : null;
        $plan_id = session('plan_id') ? (int) session('plan_id') : null;
		$subscription_data = calculateSubscriptionPercent();
		$is_trial = (int) ($subscription_data['is_trial'] ?? 0);

		// Trial subscriptions do not have a plan_id. Allow an active trial,
		// but show a clear message after the trial period has ended.
		if ($is_trial === 1) {
			if ($subscription_data === false || !empty($subscription_data['is_expired'])) {
				return [
					'status'  => false,
					'message' => lang('System.trial_period_expired')
				];
			}

			// Trial subscriptions do not have a plan record. Use the trial limits
			// while still applying the normal student and branch limit checks.
			$plan = (object) [
				'student_limit' => 50,
				'branch_limit'  => 1,
			];
		} else {
			// No plan assigned
			if (!$plan_id) {
				return [
					'status'  => false,
					'message' => lang('System.subscription_plan_not_found')
				];
			}

			$plan_model = new PlanModel();
			$plan = $plan_model->find($plan_id);

			// Plan not found
			if (!$plan) {
				return [
					'status'  => false,
					'message' => lang('System.subscription_plan_not_found')
				];
			}
		}

		

        switch ($task) {

            case 'student_limit':
                $limit = (int) ($plan->student_limit ?? 0);

				// Count students across every school owned by the current user,
				// since a school owner can manage multiple schools.
				$school_ids = (new SchoolModel())->getSchoolIdsByOwner($user_id);
				$current_students = empty($school_ids)
					? 0
					: (new StudentModel())->whereIn('school_id', $school_ids)->countAllResults();

				if ($limit <= $current_students) {
					return [
						'status'  => false,
						'message' => lang('System.student_limit_reached')
					];
				}
                break;

            case 'branch_limit':
                $limit = (int) ($plan->branch_limit ?? 0);

				// Count the number of schools (branches) currently owned by the user
                $current_schools = count((new SchoolModel())->getSchoolIdsByOwner($user_id));

                if ($limit <= $current_schools) {
                    return [
                        'status'  => false,
                        'message' => lang('System.branch_limit_reached')
                    ];
                }
                break;


            default:
                return $plan;
				break;
        }

		return [
			'status' => true,
			'plan'   => $plan
		];
    }
}




	// Set Function for Super Admin
	if (!function_exists('is_super_admin')) {
		function is_super_admin() {
			if (session()->get('isLoggedIn') && session()->get('role_id') == 1) {
				return true;
			}
			return false;
		}
	}
	
	if (!function_exists('check_super_admin_access')) {
		function check_super_admin_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if (session()->get('role_id') != 1) {
				return [
					'url' => '/no-access',
					'message' => lang('System.access_denied_msg')
				];
			}

			// Get user permissions from session
			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Admin access granted
		}
	}

	// Set Role only for Admin
	if (!function_exists('is_admin')) {
		function is_admin() {
			if (session()->get('isLoggedIn') && session()->get('role_id') == 1 || session()->get('role_id') == 2) {
				return true;
			}
			return false;
		}
	}
	
	if (!function_exists('check_admin_access')) {
		function check_admin_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if (session()->get('role_id') != 1 && session()->get('role_id') != 2) {
				return [
					'url' => '/no-access',
					'message' => lang('Auth.sys_must_login_access_as_admin')
				];
			}

			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Admin access granted
		}
	}
	

	// Set Role only for Teacher
	if (!function_exists('is_teacher')) {
		function is_teacher() {
			if (session()->get('isLoggedIn') && session()->get('role_id') == 4) {
				return true;
			}
			return false;
		}
	}
	
	if (!function_exists('check_teacher_access')) {
		function check_teacher_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if (session()->get('role_id') != 4) {
				return [
					'url' => '/no-access',
					'message' => lang('Auth.sys_must_login_access_as_teacher')
				];
			}

			// Get user permissions from session
			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Teacher access granted
		}
	}
	

	// Set Role only for Student
	if (!function_exists('is_student')) {
		function is_student() {
			if (session()->get('isLoggedIn') && session()->get('role_id') == 3) {
				return true;
			}
			return false;
		}
	}
	
	if (!function_exists('check_student_access')) {
		function check_student_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if (session()->get('role_id') != 3) {
				return [
					'url' => '/no-access',
					'message' => lang('Auth.sys_must_login_access_as_student')
				];
			}

			// Get user permissions from session
			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Student access granted
		}
	}
	

	// Set Role only for Parent
	if (!function_exists('is_parent')) {
		function is_parent() {
			if (session()->get('isLoggedIn') && session()->get('role_id') == 5) {
				return true;
			}
			return false;
		}
	}
	
	if (!function_exists('check_parent_access')) {
		function check_parent_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if (session()->get('role_id') != 5) {
				return [
					'url' => '/no-access',
					'message' => lang('Auth.sys_must_login_access_as_parent')
				];
			}

			// Get user permissions from session
			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Parent access granted
		}
	}
	
	// Check permission
	if (!function_exists('is_permission')) {
		function is_permission($permission) {
			// Get user permissions from session
			$userPermissions = session()->get('permissions') ?? [];
	
			return isset($userPermissions[$permission]) && $userPermissions[$permission][0] === "on";
		}
	}
	
	if (!function_exists('check_access')) {
		function check_access($permission = null) {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
	
			if ($permission !== null && !is_permission($permission)) {
				return [
					'url' => '/no-access',
					'message' => lang('System.sys_no_permission')
				];
			}
	
			return true; // Access granted
		}
	}
	

	// is logged in
	if (!function_exists('is_logged_in')) {
		function is_logged_in() {
			if (session()->get('isLoggedIn')) {
				return true;
			}
			return false;
		}
	}

	/**
	 * check role
	 */
	if (!function_exists('checkRole')) {
		function checkRole() {
			if (!session()->get('isLoggedIn')) {
				return [
					'url' => '/login',
					'message' => lang('Auth.sys_must_login_access')
				];
			}
		}
	}

	/**
	 * Calculate subscription progress percentage and related data
	 *
	 * @param object|null $subscription Subscription object with date fields
	 * @return array|false Returns array with subscription metrics or false if invalid
	 */
	if (!function_exists('calculateSubscriptionPercent')) {
		function calculateSubscriptionPercent() {

			$user_id = session('user_id') ? (int) session('user_id') : null;
			$plan_id = session('plan_id') ? (int) session('plan_id') : null;

			$subscriptionModel = new SubscriptionModel();

			// Get Active Subscription by User ID
            $subscription = $subscriptionModel->getActiveSubscriptionByUserId($user_id);

			// Validate subscription object
			if (empty($subscription) || !is_object($subscription)) {
				return false;
			}

			// Determine start and end dates based on trial status
			if (!empty($subscription->is_trial) && $subscription->is_trial == 1) {
				$start_date = $subscription->trial_start ?? null;
				$end_date = $subscription->trial_end ?? null;
			} else {
				$start_date = $subscription->start_date ?? null;
				$end_date = $subscription->end_date ?? null;
			}

			// Validate required dates
			if (empty($start_date) || empty($end_date)) {
				return false;
			}

			$start = strtotime($start_date);
			$end = strtotime($end_date);
			$now = time();

			// Validate timestamps
			if ($start === false || $end === false || $now === false) {
				return false;
			}

			// Ensure end date is after start date
			if ($end <= $start) {
				return false;
			}

			// Calculate total duration in days
			$total_days = ($end - $start) / (60 * 60 * 24);
			$total_days = round($total_days, 2);

			// Calculate used days (from start to now)
			$used_days = ($now - $start) / (60 * 60 * 24);
			$used_days = max(0, round($used_days, 2));

			// Calculate remaining days (from now to end)
			$remaining_days = ($end - $now) / (60 * 60 * 24);
			$remaining_days = round($remaining_days, 2);

			// Calculate percentage (clamped between 0 and 100)
			$percent = ($now - $start) / ($end - $start);
			$percent = max(0, min(100, (int) round($percent * 100)));

			// Format expiry date
			$expiry_date = date('Y-m-d', $end);

			// Set Output Message
			$html = '';
			$megs = '';
			$saved_message = '';

			$max_days = 10;
			
			// Set Output Message
			$renew_url = site_url('school-owner/subscriptions/view/' . esc($subscription->token));
			if ($remaining_days < 0) {
				$megs = lang('Auth.sys_subscription_expired');
				$saved_message = lang('Auth.sys_subscription_saved_expired');
				$html .= '<div class="alert alert-danger border-0 shadow-sm mb-4">
							<strong>
								' . $megs . ' Remaining: ' . number_format($remaining_days) . ' days.
							</strong>

							<a href="' . $renew_url . '" class="btn btn-sm btn-danger ms-2">Renew Now</a>
						</div>';
			} elseif ($remaining_days >= 0 && $remaining_days <= $max_days) {
				$megs = lang('Auth.sys_subscription_expiring_soon');
				$html .= '<div class="alert alert-danger border-0 shadow-sm mb-4">
							<strong>
								' . $megs . ' Remaining: ' . number_format($remaining_days) . ' days.
							</strong>

							<a href="' . $renew_url . '" class="btn btn-sm btn-danger ms-2">Renew Now</a>
						</div>';
			}elseif ($remaining_days > $total_days) {
				$html .= '';
			}

			

			


			// Return comprehensive subscription data
			return [
				'percent'        => $percent,
				'total_day'      => $total_days,
				'used_days'      => $used_days,
				'remaining_days' => $remaining_days,
				'expiry_date'    => $expiry_date,
				'is_expired'     => $remaining_days < 0,
				'is_expiring_soon' => $remaining_days >= 0 && $remaining_days <= $total_days,
				'is_trial'       => (int) $subscription->is_trial,
				'html'           => $html,
				'message'        => $saved_message
			];
		}
	}


	
