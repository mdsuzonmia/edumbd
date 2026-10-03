<?php
namespace App\Modules\backup\Config;

use CodeIgniter\Router\RouteCollection;

$routes->group('backup', ['namespace' => 'App\Modules\backup\Controllers'], function ($routes) {
    $routes->get('/', 'BackupController::index');
    $routes->get('download-json', 'BackupController::download');
    $routes->get('download-photo', 'BackupController::download_photo');
    $routes->post('save-json', 'BackupController::save_json');
    $routes->post('upload-photo', 'BackupController::upload_photo');
});
