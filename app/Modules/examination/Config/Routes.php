<?php

namespace App\Modules\examination\Config;

use CodeIgniter\Router\RouteCollection;

$routes->group('examination', ['namespace' => 'App\Modules\examination\Controllers'], function ($routes) {

    // The wizard tour is public. All endpoints that create or change school data
    // remain protected for authenticated school owners.
    $routes->get('result-wizard', 'ResultWizardController::index');
    $routes->get('result-wizard/', 'ResultWizardController::index');
    $routes->get('result-wizard/step/(:num)', 'ResultWizardController::index/$1');
    $routes->post('result-wizard/guest-save/(:num)', 'ResultWizardController::saveGuestDraft/$1');

    $routes->group('result-wizard', ['filter' => 'role:school-owner'], function ($routes) {
        $routes->post('save/(:num)', 'ResultWizardController::save/$1');
        $routes->get('student-template', 'ResultWizardController::studentTemplate');
        $routes->post('student-preview', 'ResultWizardController::studentPreview');
        $routes->post('student-import', 'ResultWizardController::studentImport');
        $routes->get('marks-template', 'ResultWizardController::marksTemplate');
        $routes->post('marks-import', 'ResultWizardController::marksImport');
        $routes->get('student-results', 'ResultWizardController::studentResults');
    });

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */
    $routes->group('dashboard', ['filter' => 'role:school-owner'], function ($routes) {
        $routes->get('/', 'DashboardController::index');
    });
    

    /*
    |--------------------------------------------------------------------------
    | Grade System
    |--------------------------------------------------------------------------
    */
    $routes->group('grade-systems', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'GradeSystemController::grades');
        $routes->get('create', 'GradeSystemController::createGrade');
        $routes->post('store', 'GradeSystemController::storeGrade');
        $routes->get('edit/(:segment)', 'GradeSystemController::editGrade/$1');
        $routes->post('trash/(:segment)', 'GradeSystemController::trashGrade/$1');
        $routes->post('restore/(:segment)', 'GradeSystemController::restoreGrade/$1');
        $routes->post('empty-trash', 'GradeSystemController::empty_trashGrade');

        // Grade Rules
        $routes->get('rules/(:segment)', 'GradeRuleController::index/$1');
        $routes->get('rules/create/(:segment)', 'GradeRuleController::create/$1');
        $routes->post('rules/store', 'GradeRuleController::store');
        $routes->get('rules/edit/(:segment)', 'GradeRuleController::edit/$1');
        $routes->post('rules/trash/(:segment)', 'GradeRuleController::trash/$1');
        $routes->post('rules/restore/(:segment)', 'GradeRuleController::restore/$1');
        $routes->post('rules/empty-trash', 'GradeRuleController::empty_trash');
        $routes->post('rules/get-grade-systems-by-school', 'GradeRuleController::getGradeSystemsBySchool');

    });

    /*
    |--------------------------------------------------------------------------
    | Exam Setup
    |--------------------------------------------------------------------------
    */
    $routes->group('exam-setup', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'ExamSetupController::index');
        $routes->get('create', 'ExamSetupController::create');
        $routes->post('store', 'ExamSetupController::store');
        $routes->get('edit/(:segment)', 'ExamSetupController::edit/$1');
        $routes->post('update/(:segment)', 'ExamSetupController::update/$1');
        $routes->post('trash/(:segment)', 'ExamSetupController::trash/$1');
        $routes->post('restore/(:segment)', 'ExamSetupController::restore/$1');
        $routes->post('empty-trash', 'ExamSetupController::emptyTrash');
        // getYearsBySchool
        $routes->post('get-years-by-school', 'ExamSetupController::getYearsBySchool');
        $routes->post('get-exams-by-school-and-year', 'ExamSetupController::getExamsBySchoolAndYear');

    });

    /*
    |--------------------------------------------------------------------------
    | Exam Rooms
    |--------------------------------------------------------------------------
    */
    $routes->group('exam-rooms', ['filter' => 'role:school-owner'], function ($routes) {
        $routes->get('/', 'ExamRoomController::index');
        $routes->get('create', 'ExamRoomController::create');
        $routes->post('store', 'ExamRoomController::store');
        $routes->get('edit/(:segment)', 'ExamRoomController::edit/$1');
        $routes->post('update/(:segment)', 'ExamRoomController::update/$1');
        $routes->post('status/(:segment)', 'ExamRoomController::changeStatus/$1');
        $routes->post('trash/(:segment)', 'ExamRoomController::trash/$1');
        $routes->post('restore/(:segment)', 'ExamRoomController::restore/$1');
        $routes->post('delete/(:segment)', 'ExamRoomController::delete/$1');
    });

    /*
    |--------------------------------------------------------------------------
    | Exam Seat Plans
    |--------------------------------------------------------------------------
    */
    $routes->group('seat-plans', ['filter' => 'role:school-owner'], function ($routes) {
        $routes->get('/', 'SeatPlanController::index');
        $routes->get('create', 'SeatPlanController::create');
        $routes->post('academic-data', 'SeatPlanController::academicData');
        $routes->post('students', 'SeatPlanController::students');
        $routes->post('preview', 'SeatPlanController::preview');
        $routes->post('generate', 'SeatPlanController::generate');
        $routes->get('view/(:segment)', 'SeatPlanController::view/$1');
        $routes->get('seat-slips/(:segment)', 'SeatPlanController::seatSlips/$1');
        $routes->get('print/(:segment)/(:segment)', 'SeatPlanController::printReport/$1/$2');
        $routes->get('download-pdf/(:segment)/(:segment)', 'SeatPlanController::downloadPdf/$1/$2');
        $routes->post('move/(:segment)', 'SeatPlanController::moveAllocation/$1');
        $routes->post('swap/(:segment)', 'SeatPlanController::swapAllocations/$1');
        $routes->post('remove/(:segment)', 'SeatPlanController::removeAllocation/$1');
        $routes->post('add/(:segment)', 'SeatPlanController::addAllocation/$1');
        $routes->post('regenerate/(:segment)', 'SeatPlanController::regenerate/$1');
        $routes->post('lock/(:segment)', 'SeatPlanController::lock/$1');
        $routes->post('unlock/(:segment)', 'SeatPlanController::unlock/$1');
    });

    $routes->group('admit-cards', ['filter' => 'role:school-owner'], function ($routes) {
        $routes->get('/', 'AdmitCardController::index');
        $routes->get('create', 'AdmitCardController::create');
        $routes->post('academic-data', 'AdmitCardController::academicData');
        $routes->post('students', 'AdmitCardController::students');
        $routes->post('generate', 'AdmitCardController::generate');
        $routes->get('settings', 'AdmitCardController::settings');
        $routes->get('settings/(:segment)', 'AdmitCardController::settings/$1');
        $routes->post('settings/(:segment)', 'AdmitCardController::updateSettings/$1');
        $routes->get('view/(:segment)', 'AdmitCardController::view/$1');
        $routes->get('print/(:segment)', 'AdmitCardController::print/$1');
        $routes->get('download-pdf/(:segment)', 'AdmitCardController::downloadPdf/$1');
    });

    /*
    |--------------------------------------------------------------------------
    | Mark Distribution
    |--------------------------------------------------------------------------
    */
    $routes->group('mark-distributions' , ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'MarkDistributionController::index');
        $routes->get('create', 'MarkDistributionController::create');
        $routes->post('store', 'MarkDistributionController::store');
        $routes->get('edit/(:segment)', 'MarkDistributionController::edit/$1');
        $routes->post('update/(:segment)', 'MarkDistributionController::update/$1');
        $routes->post('trash/(:segment)', 'MarkDistributionController::trash/$1');
        $routes->post('restore/(:segment)', 'MarkDistributionController::restore/$1');

    });

    /*
    |--------------------------------------------------------------------------
    | Subject Setup
    |--------------------------------------------------------------------------
    */
    $routes->group('subjects', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'SubjectController::index');
        $routes->get('create', 'SubjectController::create');
        $routes->post('store', 'SubjectController::store');
        $routes->get('edit/(:segment)', 'SubjectController::edit/$1');
        $routes->post('update/(:segment)', 'SubjectController::update/$1');
        $routes->post('trash/(:segment)', 'SubjectController::trash/$1');
        $routes->post('restore/(:segment)', 'SubjectController::restore/$1');
        // getDropdownsBySchool
        $routes->post('getDropdownsBySchool', 'SubjectController::getDropdownsBySchool');

        // Assign students to subjects
        $routes->get('assign-students', 'SubjectController::assignStudents');
        $routes->post('assign-students', 'SubjectController::assignStudents');

        // AJAX endpoints for assign students
        $routes->post('getAcademicDataBySchool', 'SubjectController::getAcademicDataBySchool');
        $routes->post('getStudentsByFilter', 'SubjectController::getStudentsByFilter');

        // Subject Distribution CRUD endpoints
        $routes->post('saveDistribution', 'SubjectController::saveDistribution');
        $routes->post('getDistribution', 'SubjectController::getDistribution');
        $routes->post('deleteDistribution', 'SubjectController::deleteDistribution');

    });

    /*
    |--------------------------------------------------------------------------
    | Subject Distribution
    |--------------------------------------------------------------------------
    */
    $routes->group('subject-distributions', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'SubjectDistributionController::index');
        $routes->get('create', 'SubjectDistributionController::create');
        $routes->post('store', 'SubjectDistributionController::store');
        $routes->get('edit/(:segment)', 'SubjectDistributionController::edit/$1');
        $routes->post('update/(:segment)', 'SubjectDistributionController::update/$1');
        $routes->post('trash/(:segment)', 'SubjectDistributionController::trash/$1');
        $routes->post('restore/(:segment)', 'SubjectDistributionController::restore/$1');

        /// getSubjectsBySchool
        $routes->post('getSubjectsBySchool', 'SubjectDistributionController::getSubjectsBySchool');

        // getMarkDistributionsBySchool
        $routes->post('getMarkDistributionsBySchool', 'SubjectDistributionController::getMarkDistributionsBySchool');

    });

    /*
    |--------------------------------------------------------------------------
    | Marks Management
    |--------------------------------------------------------------------------
    */
    $routes->group('marks', ['filter' => 'role:school-owner'], function ($routes) {

        // Enter Marks
        $routes->get('list', 'MarksController::index');
        $routes->get('input', 'MarksController::create');
        $routes->post('store', 'MarksController::store');
        $routes->get('edit/(:segment)', 'MarksController::edit/$1');
        $routes->post('update/(:segment)', 'MarksController::update/$1');
        // View Marks
        $routes->get('view/(:segment)', 'MarksController::view/$1');

        // trash, restore, empty trash
        $routes->post('trash/(:segment)', 'MarksController::trash/$1');
        $routes->post('restore/(:segment)', 'MarksController::restore/$1');
        $routes->post('empty-trash', 'MarksController::emptyTrash');

        // getAcademicDataBySchool
        $routes->post('getAcademicDataBySchool', 'MarksController::getAcademicDataBySchool');

        // getStudentsByFilter
        $routes->post('getStudentsByFilter', 'MarksController::getStudentsByFilter');

        // Bulk Import
        $routes->group('bulk-import', ['filter' => 'role:school-owner'], function ($routes) {

            $routes->get('/', 'BulkImportController::index');
            $routes->post('preview', 'BulkImportController::preview');
            $routes->post('import', 'BulkImportController::import');
            $routes->get('download-real-data-json', 'BulkImportController::download_real_data_json');
            $routes->get('download-real-data-csv', 'BulkImportController::download_real_data_csv');

            $routes->get('download-sample-data-json', 'BulkImportController::download_sample_data_json');
            $routes->get('download-sample-data-csv', 'BulkImportController::download_sample_data_csv');

            // AJAX endpoints for bulk import
            $routes->post('getStudentsByClass', 'BulkImportController::getStudentsByClass');
            $routes->post('getDistributionsBySubject', 'BulkImportController::getDistributionsBySubject');
            $routes->post('getAcademicDataBySchool', 'BulkImportController::getAcademicDataBySchool');

        });

        // Lock / Unlock
        $routes->post('lock', 'MarksController::lock');
        $routes->post('unlock', 'MarksController::unlock');
        $routes->post('check-lock-status', 'MarksController::checkLockStatus');
        $routes->get('locked-marks', 'MarksController::lockedMarksList');
       

    });

    /*
    |--------------------------------------------------------------------------
    | Result Generator
    |--------------------------------------------------------------------------
    */
    $routes->group('results' , ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('generate', 'ResultGeneratorController::index');
        $routes->post('generate', 'ResultGeneratorController::generate');
        $routes->post('recalculate', 'ResultGeneratorController::recalculate');
        $routes->post('getAcademicDataBySchool', 'ResultGeneratorController::getAcademicDataBySchool');

    });

    /*
    |--------------------------------------------------------------------------
    | Result Publish
    |--------------------------------------------------------------------------
    */
    $routes->group('result-publish', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'ResultPublishController::index');
        $routes->post('publish', 'ResultPublishController::publish');
        $routes->post('unpublish', 'ResultPublishController::unpublish');
        $routes->post('checkStatus', 'ResultPublishController::checkStatus');
        $routes->post('getAcademicDataBySchool', 'ResultPublishController::getAcademicDataBySchool');

    });

    /*
    |--------------------------------------------------------------------------
    | Aggregate Results
    |--------------------------------------------------------------------------
    */
    $routes->group('aggregate', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'AggregateController::index');
        $routes->post('generate', 'AggregateController::generate');
        $routes->post('getAcademicDataBySchool', 'AggregateController::getAcademicDataBySchool');

        // remarks page
        $routes->get('remarks', 'AggregateController::remarks');
        $routes->post('ajax-get-remarks-results', 'AggregateController::ajaxGetRemarksResults');

    });

    // Remarks
    $routes->group('remarks', ['filter' => 'role:school-owner'], function ($routes) {
        // aggregate remark
        $routes->get('aggregate', 'AggregateController::remarks');
        $routes->post('aggregate/results', 'AggregateController::ajaxGetRemarksResults');
        $routes->post('aggregate/save', 'AggregateController::saveRemarks');

        // Exam Remark
        $routes->get('exam', 'ExamRemarkController::index');
        $routes->post('exam/results', 'ExamRemarkController::ajaxGetResults');
        $routes->post('exam/save', 'ExamRemarkController::store');

    });

    /*
    |--------------------------------------------------------------------------
    | Reports
    |--------------------------------------------------------------------------
    */
    $routes->group('reports', ['filter' => 'role:school-owner'], function ($routes) {

        // Individual Result
        $routes->get('individual-result', 'Reports\IndividualResultController::index');
        $routes->get('individual-result/details/(:segment)', 'Reports\IndividualResultController::details/$1');
        $routes->get('individual-result/download-pdf/(:segment)', 'Reports\IndividualResultController::downloadPdf/$1');
        
        // AJAX endpoints for individual result
        $routes->post('individual-result/ajax-get-years', 'Reports\IndividualResultController::ajaxGetYears');
        $routes->post('individual-result/ajax-get-classes', 'Reports\IndividualResultController::ajaxGetClasses');
        $routes->post('individual-result/ajax-get-exams', 'Reports\IndividualResultController::ajaxGetExams');
        $routes->post('individual-result/ajax-get-students', 'Reports\IndividualResultController::ajaxGetStudents');
        $routes->post('individual-result/send-email', 'Reports\IndividualResultController::sendEmail');

        // Aggregate Result
        $routes->get('aggregate-result', 'Reports\AggregateResultController::index');

        // Transcript
        $routes->get('transcript', 'Reports\TranscriptController::index');
        $routes->get('transcript/details/(:segment)', 'Reports\TranscriptController::details/$1');
        $routes->get('transcript/download-pdf/(:segment)', 'Reports\TranscriptController::downloadPdf/$1');
        
        // AJAX endpoints for transcript
        $routes->post('transcript/ajax-get-years', 'Reports\TranscriptController::ajaxGetYears');
        $routes->post('transcript/ajax-get-classes', 'Reports\TranscriptController::ajaxGetClasses');
        $routes->post('transcript/ajax-get-students', 'Reports\TranscriptController::ajaxGetStudents');


        // Tabulation Sheet
        $routes->get('tabulation-sheet', 'Reports\TabulationSheetController::index');

        // AJAX endpoints for tabulation sheet
        $routes->post('tabulation-sheet/ajax-get-years', 'Reports\TabulationSheetController::ajaxGetYears');
        $routes->post('tabulation-sheet/ajax-get-exams', 'Reports\TabulationSheetController::ajaxGetExams');
        $routes->post('tabulation-sheet/ajax-get-classes', 'Reports\TabulationSheetController::ajaxGetClasses');
        $routes->post('tabulation-sheet/ajax-generate-tabulation', 'Reports\TabulationSheetController::ajaxGenerateTabulation');
        $routes->get('tabulation-sheet/download-pdf', 'Reports\TabulationSheetController::downloadPdf');

        // Merit List
        $routes->get('merit-list', 'Reports\MeritListController::index');
        
        // AJAX endpoints for merit list
        $routes->post('merit-list/ajax-get-years', 'Reports\MeritListController::ajaxGetYears');
        $routes->post('merit-list/ajax-get-exams', 'Reports\MeritListController::ajaxGetExams');
        $routes->post('merit-list/ajax-get-classes', 'Reports\MeritListController::ajaxGetClasses');
        $routes->post('merit-list/ajax-get-sections', 'Reports\MeritListController::ajaxGetSections');
        $routes->post('merit-list/ajax-generate-merit', 'Reports\MeritListController::ajaxGenerateMerit');
        $routes->get('merit-list/download-pdf', 'Reports\MeritListController::downloadPdf');

        // Subject Analysis
        $routes->get('subject-analysis', 'Reports\SubjectAnalysisController::index');
        
        // AJAX endpoints for subject analysis
        $routes->post('subject-analysis/ajax-get-years', 'Reports\SubjectAnalysisController::ajaxGetYears');
        $routes->post('subject-analysis/ajax-get-exams', 'Reports\SubjectAnalysisController::ajaxGetExams');
        $routes->post('subject-analysis/ajax-get-classes', 'Reports\SubjectAnalysisController::ajaxGetClasses');
        $routes->post('subject-analysis/ajax-get-sections', 'Reports\SubjectAnalysisController::ajaxGetSections');
        $routes->post('subject-analysis/ajax-get-subjects', 'Reports\SubjectAnalysisController::ajaxGetSubjects');
        $routes->post('subject-analysis/ajax-generate-analysis', 'Reports\SubjectAnalysisController::ajaxGenerateAnalysis');
        $routes->get('subject-analysis/download-pdf', 'Reports\SubjectAnalysisController::downloadPdf');

        // Class Statistics
        $routes->get('class-statistics', 'Reports\ClassStatisticsController::index');
        
        // AJAX endpoints for class statistics
        $routes->post('class-statistics/ajax-get-years', 'Reports\ClassStatisticsController::ajaxGetYears');
        $routes->post('class-statistics/ajax-get-exams', 'Reports\ClassStatisticsController::ajaxGetExams');
        $routes->post('class-statistics/ajax-get-classes', 'Reports\ClassStatisticsController::ajaxGetClasses');
        $routes->post('class-statistics/ajax-get-sections', 'Reports\ClassStatisticsController::ajaxGetSections');
        $routes->post('class-statistics/ajax-generate-statistics', 'Reports\ClassStatisticsController::ajaxGenerateStatistics');
        $routes->get('class-statistics/download-pdf', 'Reports\ClassStatisticsController::downloadPdf');

        // GPA Analysis
        $routes->get('gpa-analysis', 'Reports\GpaAnalysisController::index');
        
        // AJAX endpoints for GPA analysis
        $routes->post('gpa-analysis/ajax-get-years', 'Reports\GpaAnalysisController::ajaxGetYears');
        $routes->post('gpa-analysis/ajax-get-exams', 'Reports\GpaAnalysisController::ajaxGetExams');
        $routes->post('gpa-analysis/ajax-get-classes', 'Reports\GpaAnalysisController::ajaxGetClasses');
        $routes->post('gpa-analysis/ajax-get-sections', 'Reports\GpaAnalysisController::ajaxGetSections');
        $routes->post('gpa-analysis/ajax-generate-analysis', 'Reports\GpaAnalysisController::ajaxGenerateAnalysis');
        $routes->get('gpa-analysis/download-pdf', 'Reports\GpaAnalysisController::downloadPdf');

        // Pass/Fail Report
        $routes->get('pass-fail-report', 'Reports\PassFailReportController::index');
        
        // AJAX endpoints for pass/fail report
        $routes->post('pass-fail-report/ajax-get-years', 'Reports\PassFailReportController::ajaxGetYears');
        $routes->post('pass-fail-report/ajax-get-exams', 'Reports\PassFailReportController::ajaxGetExams');
        $routes->post('pass-fail-report/ajax-get-classes', 'Reports\PassFailReportController::ajaxGetClasses');
        $routes->post('pass-fail-report/ajax-get-sections', 'Reports\PassFailReportController::ajaxGetSections');
        $routes->post('pass-fail-report/ajax-generate-report', 'Reports\PassFailReportController::ajaxGenerateReport');
        $routes->get('pass-fail-report/download-pdf', 'Reports\PassFailReportController::downloadPdf');

        

    });

    /*
    |--------------------------------------------------------------------------
    | Promotion
    |--------------------------------------------------------------------------
    */
    $routes->group('promotion', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'PromotionController::index');
        $routes->post('preview', 'PromotionController::preview');
        $routes->post('process', 'PromotionController::process');
        
        // Promotion history
        $routes->get('history', 'PromotionController::history');
        $routes->get('history/(:num)', 'PromotionController::details/$1');
        $routes->post('rollback/(:num)', 'PromotionController::rollback/$1');
        
        // AJAX endpoints for promotion
        $routes->post('ajax-get-years', 'PromotionController::ajaxGetYears');
        $routes->post('ajax-get-classes', 'PromotionController::ajaxGetClasses');
        $routes->post('ajax-get-sections', 'PromotionController::ajaxGetSections');
        $routes->post('ajax-get-exams', 'PromotionController::ajaxGetExams');

    });

    /*
    |--------------------------------------------------------------------------
    | Result Settings
    |--------------------------------------------------------------------------
    */
    $routes->group('settings', ['filter' => 'role:school-owner'], function ($routes) {

        $routes->get('/', 'ResultSettingsController::index');
        $routes->post('save', 'ResultSettingsController::save');

    });

    /*
    |--------------------------------------------------------------------------
    | Result Templates
    |--------------------------------------------------------------------------
    */
    $routes->group('templates', ['filter' => 'role:school-owner,super-admin'], function ($routes) {

        $routes->get('/', 'ResultTemplateController::index');
        $routes->get('builder', 'ResultTemplateController::builder');
        $routes->post('save', 'ResultTemplateController::save');
        $routes->post('delete/(:segment)', 'ResultTemplateController::delete/$1');

    });

    // Public result access routes (no authentication required) by token
    $routes->get('result/(:segment)', 'PublicResultController::view/$1');
    $routes->get('transcript/(:segment)', 'PublicTranscriptController::view/$1');


    // Individual Result and Transcript  for Students (authenticated access)
    $routes->group('student', ['filter' => 'role:student'], function ($routes) {
        $routes->get('result', 'StudentResultController::index');
        $routes->get('result/view/(:segment)', 'StudentResultController::viewResult/$1');
        $routes->get('transcript', 'StudentTranscriptController::index');
        $routes->get('transcript/view/(:segment)', 'StudentTranscriptController::viewTranscript/$1');
        $routes->get('transcript/download-pdf/(:segment)', 'StudentTranscriptController::downloadPdf/$1');
        $routes->get('admit-cards', 'AdmitCardController::studentIndex');
        $routes->get('admit-cards/view/(:segment)', 'AdmitCardController::studentView/$1');
        $routes->get('admit-cards/download-pdf/(:segment)', 'AdmitCardController::studentDownloadPdf/$1');
    });
    
});

$routes->group('admit-card', ['namespace' => 'App\Modules\examination\Controllers'], function ($routes) {
    $routes->get('verify/(:segment)', 'AdmitCardController::verify/$1');
});
