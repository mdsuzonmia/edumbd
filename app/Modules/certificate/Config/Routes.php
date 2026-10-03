<?php
namespace App\Modules\sms_whatapp\Config;

use CodeIgniter\Router\RouteCollection;

$routes->group('sms_whatapp', ['namespace' => 'App\Modules\sms_whatapp\Controllers'], function ($routes) {
    $routes->get('/', 'SmsController::index');
    $routes->post('send-sms', 'SmsController::send_sms');
    $routes->get('download-photo', 'BackupController::download_photo');
    $routes->post('save-json', 'BackupController::save_json');
    $routes->post('upload-photo', 'BackupController::upload_photo');
});
