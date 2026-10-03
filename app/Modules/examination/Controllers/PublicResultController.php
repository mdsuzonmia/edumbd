<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Controllers\Reports\IndividualResultController;

class PublicResultController extends BaseController
{
    /**
     * Display an individual result by token without requiring authentication.
     */
    public function view(string $token)
    {
        $reportController = new IndividualResultController();

        return $reportController->publicDetails($token);
    }
}
