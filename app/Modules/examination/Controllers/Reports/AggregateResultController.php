<?php

namespace App\Modules\examination\Controllers\Reports;

class AggregateResultController extends BaseReportController
{
    public function index()
    {
        if (!$this->getUserId()) {
            return redirect()->to('login');
        }
        return $this->renderWithHeaderFooter('Aggregate Result', 'App\Modules\examination\Views\reports\aggregate_result');
    }
}