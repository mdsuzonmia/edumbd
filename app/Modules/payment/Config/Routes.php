<?php

$routes->group('payment', ['namespace' => 'Modules\payment\Controllers'], function ($routes) {
    $routes->get('/', 'PaymentController::superAdminDashboard');
});
