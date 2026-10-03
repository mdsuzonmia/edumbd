<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Check login
        if (!$session->get('logged_in')) {
            return redirect()->to('/login')
                ->with('error', 'Please login to continue.');
        }

        // 2. Role check (optional via route filter)
        if (!empty($arguments)) {
            $userRole = $session->get('role'); // e.g. super_admin, school_admin

            if (!in_array($userRole, $arguments)) {
                return redirect()->to('/unauthorized')
                    ->with('error', 'You are not authorized to access this page.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Optional: logging, headers, etc.
    }
}
