<?php

namespace App\Modules\examination\Controllers;

use App\Controllers\BaseController;
use App\Modules\examination\Controllers\Reports\TranscriptController as ReportTranscriptController;

class PublicTranscriptController extends BaseController
{
    /**
     * Display a transcript by token without requiring authentication.
     *
     * Transcript content is built by the report transcript renderer so the
     * public and school-owner pages cannot drift apart.
     */
    public function view(string $token)
    {
        $reportController = new ReportTranscriptController();
        $data = $reportController->getPublicTranscriptData($token);

        if ($data === null) {
            return view('App\Modules\examination\Views\public\transcript_not_found');
        }

        return view('App\Modules\examination\Views\public\transcript_view', $data);
    }
}
