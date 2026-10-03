<?php
namespace App\Modules\teachers\Config;

use CodeIgniter\Router\RouteCollection;

$routes->group('api/v1', ['namespace' => 'App\Modules\teachers\Controllers'], function ($routes) {
    // ============================
    // Teacher Routes
    // ============================
    $routes->get('teachers', 'TeachersController::index');
    $routes->get('teachers/(:num)', 'TeachersController::show/$1');
    $routes->post('teachers', 'TeachersController::store');
    $routes->post('teachers/(:num)', 'TeachersController::update/$1');
    $routes->delete('teachers/(:num)', 'TeachersController::delete/$1');
    $routes->post('teachers/status/(:num)', 'TeachersController::changeStatus/$1');
    $routes->get('teachers/school/(:any)', 'TeachersController::bySchool/$1');

    // ============================
    // Teacher Qualification Routes
    // ============================
    $routes->get('teacher-qualifications', 'TeacherQualificationsController::index');
    $routes->get('teacher-qualifications/(:num)', 'TeacherQualificationsController::show/$1');
    $routes->post('teacher-qualifications', 'TeacherQualificationsController::store');
    $routes->post('teacher-qualifications/(:num)', 'TeacherQualificationsController::update/$1');
    $routes->delete('teacher-qualifications/(:num)', 'TeacherQualificationsController::delete/$1');
    $routes->get('teacher-qualifications/teacher/(:num)', 'TeacherQualificationsController::byTeacher/$1');

    // ============================
    // Teacher Subject Routes
    // ============================
    $routes->get('teacher-subjects', 'TeacherSubjectsController::index');
    $routes->get('teacher-subjects/(:num)', 'TeacherSubjectsController::show/$1');
    $routes->post('teacher-subjects', 'TeacherSubjectsController::store');
    $routes->post('teacher-subjects/(:num)', 'TeacherSubjectsController::update/$1');
    $routes->delete('teacher-subjects/(:num)', 'TeacherSubjectsController::delete/$1');
    $routes->get('teacher-subjects/teacher/(:num)', 'TeacherSubjectsController::byTeacher/$1');
    $routes->get('teacher-subjects/subject/(:num)', 'TeacherSubjectsController::bySubject/$1');

    // ============================
    // Teacher Class Routes
    // ============================
    $routes->get('teacher-classes', 'TeacherClassesController::index');
    $routes->get('teacher-classes/(:num)', 'TeacherClassesController::show/$1');
    $routes->post('teacher-classes', 'TeacherClassesController::store');
    $routes->post('teacher-classes/(:num)', 'TeacherClassesController::update/$1');
    $routes->delete('teacher-classes/(:num)', 'TeacherClassesController::delete/$1');
    $routes->get('teacher-classes/teacher/(:num)', 'TeacherClassesController::byTeacher/$1');
    $routes->get('teacher-classes/class/(:num)', 'TeacherClassesController::byClass/$1');
});