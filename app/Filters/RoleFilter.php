<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class RoleFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // Check if user is logged in
        if (!$session->get('logged_in')) {
            return redirect()->to('/login')
                ->with('error', 'Please login to continue.');
        }

        // Check if user has the required role
        if (!empty($arguments)) {
            $userRole = $session->get('role'); // e.g., admin, user, etc.

            if (!in_array($userRole, $arguments)) {
                return redirect()->to('/unauthorized')
                    ->with('error', 'You are not authorized to access this page.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Optional: Add post-request logic here
    }
}
