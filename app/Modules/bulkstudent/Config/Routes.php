<?php
namespace App\Modules\bulkstudent\Config;

use CodeIgniter\Router\RouteCollection;

$routes->group('bulkstudent', ['namespace' => 'App\Modules\bulkstudent\Controllers'], function ($routes) {
    $routes->get('/', 'BulkstudentController::index');
    $routes->get('download-csv', 'BulkstudentController::download_csv');
    $routes->post('save-students', 'BulkstudentController::save_students');
});
