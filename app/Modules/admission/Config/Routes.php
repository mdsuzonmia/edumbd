<?php

namespace App\Modules\admission\Config;

$routes->group('admission', ['namespace' => 'App\Modules\admission\Controllers'], static function ($routes) {
    // Public portal. Slugs and random verification tokens are used instead of database IDs.
    $routes->get('status', 'PublicAdmissionController::statusForm');
    $routes->post('status', 'PublicAdmissionController::status');
    $routes->get('application/(:segment)', 'PublicAdmissionController::application/$1');
    $routes->get('application/(:segment)/pdf', 'PublicAdmissionController::pdf/$1');
    $routes->get('application/(:segment)/payment/(:segment)', 'PublicAdmissionController::payment/$1/$2');
    $routes->post('application/(:segment)/payment/(:segment)', 'PublicAdmissionController::submitPayment/$1/$2');
    $routes->get('payment/(:segment)/stripe/success', 'PublicAdmissionController::stripeSuccess/$1');
    $routes->get('payment/(:segment)/stripe/cancel', 'PublicAdmissionController::stripeCancel/$1');
    $routes->get('payment/(:segment)/paypal/success', 'PublicAdmissionController::paypalSuccess/$1');
    $routes->get('payment/(:segment)/paypal/cancel', 'PublicAdmissionController::paypalCancel/$1');
    $routes->get('admit-card/(:segment)', 'PublicAdmissionController::admitCard/$1');
    $routes->get('admit-card/(:segment)/pdf', 'PublicAdmissionController::admitCardPdf/$1');
    $routes->get('admit-card/verify/(:segment)', 'PublicAdmissionController::verifyAdmitCard/$1');
    $routes->get('verify/(:segment)', 'PublicAdmissionController::verify/$1');
    $routes->get('(:segment)', 'PublicAdmissionController::index/$1');
    $routes->get('(:segment)/apply/(:segment)', 'PublicAdmissionController::create/$1/$2');
    $routes->post('(:segment)/apply/(:segment)', 'PublicAdmissionController::store/$1/$2');
    $routes->post('(:segment)/lottery-result', 'PublicAdmissionController::lotteryResult/$1');
});

$routes->group('school/admission', [
    'namespace' => 'App\Modules\admission\Controllers',
    'filter'    => 'role:school-owner',
], static function ($routes) {
    $routes->get('/', 'AdmissionController::dashboard');
    $routes->get('academic-data/(:num)', 'AdmissionController::academicDataJson/$1');
    $routes->get('sessions', 'AdmissionController::sessions');
    $routes->get('sessions/create', 'AdmissionController::sessionForm');
    $routes->get('sessions/edit/(:segment)', 'AdmissionController::sessionForm/$1');
    $routes->post('sessions/save', 'AdmissionController::saveSession');
    $routes->get('circulars', 'AdmissionController::circulars');
    $routes->get('circulars/create', 'AdmissionController::circularForm');
    $routes->get('circulars/edit/(:segment)', 'AdmissionController::circularForm/$1');
    $routes->post('circulars/save', 'AdmissionController::saveCircular');
    $routes->get('applications', 'AdmissionController::applications');
    $routes->get('applications/(:segment)', 'AdmissionController::showApplication/$1');
    $routes->post('applications/(:segment)/status', 'AdmissionController::updateApplicationStatus/$1');
    $routes->post('applications/(:segment)/admit', 'AdmissionController::admit/$1');
    $routes->get('reports/applications.csv', 'AdmissionController::applicationsCsv');
    $routes->get('lotteries', 'AdmissionController::lotteries');
    $routes->get('lotteries/create', 'AdmissionController::lotteryForm');
    $routes->post('lotteries/save', 'AdmissionController::saveLottery');
    $routes->get('lotteries/(:segment)', 'AdmissionController::showLottery/$1');
    $routes->post('lotteries/(:segment)/lock', 'AdmissionController::lockLottery/$1');
    $routes->post('lotteries/(:segment)/run', 'AdmissionController::runLottery/$1');
    $routes->post('lotteries/(:segment)/finalize', 'AdmissionController::finalizeLottery/$1');
    $routes->post('lotteries/(:segment)/cancel', 'AdmissionController::cancelLottery/$1');

    $routes->get('form-builder', 'AdmissionOperationsController::formBuilder');
    $routes->post('form-builder/save', 'AdmissionOperationsController::saveField');
    $routes->post('form-builder/delete/(:num)', 'AdmissionOperationsController::deleteField/$1');
    $routes->get('payments', 'AdmissionOperationsController::payments');
    $routes->get('payment-settings', 'AdmissionOperationsController::paymentSettings');
    $routes->post('payment-settings/save', 'AdmissionOperationsController::savePaymentSettings');
    $routes->post('payments/(:segment)/review', 'AdmissionOperationsController::reviewPayment/$1');
    $routes->get('payments/(:segment)/proof', 'AdmissionOperationsController::paymentProof/$1');
    $routes->get('documents', 'AdmissionOperationsController::documents');
    $routes->post('documents/(:num)/review', 'AdmissionOperationsController::reviewDocument/$1');
    $routes->get('documents/(:num)/file', 'AdmissionOperationsController::documentFile/$1');
    $routes->get('tests', 'AdmissionOperationsController::tests');
    $routes->get('tests/create', 'AdmissionOperationsController::testForm');
    $routes->get('tests/edit/(:segment)', 'AdmissionOperationsController::testForm/$1');
    $routes->post('tests/save', 'AdmissionOperationsController::saveTest');
    $routes->get('test-rooms', 'AdmissionOperationsController::rooms');
    $routes->get('test-rooms/create', 'AdmissionOperationsController::roomForm');
    $routes->get('test-rooms/edit/(:segment)', 'AdmissionOperationsController::roomForm/$1');
    $routes->post('test-rooms/save', 'AdmissionOperationsController::saveRoom');
    $routes->get('seat-plans', 'AdmissionOperationsController::seatPlans');
    $routes->get('seat-plans/create', 'AdmissionOperationsController::seatPlanForm');
    $routes->post('seat-plans/generate', 'AdmissionOperationsController::generateSeatPlan');
    $routes->get('seat-plans/(:segment)', 'AdmissionOperationsController::showSeatPlan/$1');
    $routes->post('seat-plans/(:segment)/lock', 'AdmissionOperationsController::lockSeatPlan/$1');
    $routes->get('seat-plans/(:segment)/pdf', 'AdmissionOperationsController::seatPlanPdf/$1');
    $routes->get('admit-cards', 'AdmissionOperationsController::admitCards');
    $routes->post('admit-cards/generate/(:segment)', 'AdmissionOperationsController::generateAdmitCards/$1');
});
