<?php

use CodeIgniter\Router\RouteCollection;
use App\Models\ModuleModel;

/**
 * @var RouteCollection $routes
 */


$routes->get('/', 'Registration::registration_form');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::doLogin');
$routes->get('logout', 'Auth::logout');
$routes->get('language/(:alpha)', 'Language::switch/$1');

// Static Pages
$routes->get('terms', 'Pages::terms');
$routes->get('privacy-policy', 'Pages::privacy');


// Documentation
$routes->get('docs', 'Docs::index');
$routes->get('docs/(:segment)', 'Docs::view/$1');


// Payment Page
$routes->get('payment', 'Registration::payment');


// Stripe
$routes->get('payment/stripe/(:segment)', 'Registration::stripeCheckout/$1');
$routes->get('payment/stripe/success/(:segment)', 'Registration::stripeSuccess/$1');
$routes->get('payment/stripe/cancel', 'Registration::stripeCancel');

// PayPal
$routes->get('payment/paypal/(:segment)', 'Registration::paypalCheckout/$1');
$routes->get('payment/paypal/success/(:segment)', 'Registration::paypalSuccess/$1');
$routes->get('payment/paypal/cancel', 'Registration::paypalCancel');
$routes->post('paypal/ipn', 'PayPal::paypalIpn');

// Get school registration form
$routes->get('registration', 'Registration::registration_form');
$routes->post('registration', 'Registration::do_registration');

// registration-success
$routes->get('registration-success/(:segment)', 'Registration::registration_success/$1');

// register/manual-success/$tempToken
$routes->get('register/manual-success/(:segment)', 'Registration::manualSuccess/$1');   

// Get verify email form
$routes->get('verify-email/(:segment)', 'Verification::verify_email/$1');
//email/verification/send
$routes->get('email/verification/send', 'Verification::resendVerificationEmail');

// Get Verify phone number form
$routes->get('verify-phone', 'Auth::verify_phone');
$routes->post('verify-phone', 'Auth::doVerifyPhone');

// Get reset password form
$routes->get('reset-password/(:segment)', 'Auth::reset_password/$1');
$routes->post('reset-password', 'Auth::doResetPassword');

// Get forgot password form
$routes->get('forgot-password', 'Auth::forgot_password');
$routes->post('forgot-password', 'Auth::sendResetLink');

$routes->group('auth', ['filter' => 'auth'], function($routes) {
    $routes->get('my-profile', 'Auth::my_profile');
    $routes->get('edit-profile', 'Auth::edit_profile');
    $routes->post('update-profile', 'Auth::update_profile');
    $routes->get('change-password', 'Auth::change_password');
    $routes->post('update-password', 'Auth::update_password');
});


// ===============================
// SaaS SUPER ADMIN ROUTES
// ===============================
$routes->group('saas-admin', ['filter' => 'role:super-admin'], static function ($routes) {

    // Dashboard
    $routes->get('dashboard', 'SaasAdmin\Dashboard::index',['as' => 'saas_admin_dashboard']);

    // -------------------------------
    // School Management (Tenants)
    // -------------------------------
    $routes->get('schools', 'SaasAdmin\Schools::index',['as' => 'saas_admin_schools']);
    $routes->get('schools/create', 'SaasAdmin\Schools::create');
    $routes->post('schools/store', 'SaasAdmin\Schools::store');
    $routes->get('schools/edit/(:num)', 'SaasAdmin\Schools::edit/$1');
    $routes->post('schools/update/(:num)', 'SaasAdmin\Schools::update/$1');
    $routes->post('schools/delete/(:num)', 'SaasAdmin\Schools::delete/$1');
    $routes->get('schools/view/(:num)', 'SaasAdmin\Schools::view/$1');
    $routes->post('schools/status/(:num)', 'SaasAdmin\Schools::changeStatus/$1');

    // -------------------------------
    // Subscription Plans
    // -------------------------------
    $routes->get('plans', 'SaasAdmin\Plans::index',['as' => 'saas_admin_plans']);
    $routes->get('plans/create', 'SaasAdmin\Plans::create');
    $routes->post('plans/store', 'SaasAdmin\Plans::store');
    $routes->get('plans/edit/(:num)', 'SaasAdmin\Plans::edit/$1');
    $routes->post('plans/trash', 'SaasAdmin\Plans::trash');
    $routes->post('plans/empty-trash', 'SaasAdmin\Plans::empty_trash');
    $routes->post('plans/restore', 'SaasAdmin\Plans::restore');

    // -------------------------------
    // Subscriptions
    // -------------------------------
    $routes->get('subscriptions', 'SaasAdmin\Subscriptions::index',['as' => 'saas_admin_subscriptions']);
    $routes->get('subscriptions/create', 'SaasAdmin\Subscriptions::create');
    $routes->post('subscriptions/store', 'SaasAdmin\Subscriptions::store');
    $routes->post('subscriptions/trash', 'SaasAdmin\Subscriptions::trash');
    $routes->post('subscriptions/empty-trash', 'SaasAdmin\Subscriptions::empty_trash');
    $routes->post('subscriptions/restore', 'SaasAdmin\Subscriptions::restore');
    $routes->get('subscriptions/view/(:num)', 'SaasAdmin\Subscriptions::view/$1');
    $routes->post('subscriptions/renew/(:num)', 'SaasAdmin\Subscriptions::renew/$1');
    $routes->post('subscriptions/cancel/(:num)', 'SaasAdmin\Subscriptions::cancel/$1');
    $routes->post('subscriptions/status/(:num)', 'SaasAdmin\Subscriptions::changeStatus/$1');

    // -------------------------------
    // Payments & Billing
    // -------------------------------
    $routes->get('payments', 'SaasAdmin\Payments::index',['as' => 'saas_admin_payments']);
    $routes->get('payments/view/(:num)', 'SaasAdmin\Payments::view/$1');
    $routes->post('payments/status/(:num)', 'SaasAdmin\Payments::changeStatus/$1');
    $routes->get('invoices', 'SaasAdmin\Invoices::index',['as' => 'saas_admin_invoices']);
    $routes->get('invoices/view/(:num)', 'SaasAdmin\Invoices::view/$1');
    $routes->get('service-billing', 'SaasAdmin\ServiceBilling::index');
    $routes->get('service-billing/approvals', 'SaasAdmin\ServiceBilling::approvals');
    $routes->post('service-billing/settings', 'SaasAdmin\ServiceBilling::saveSettings');
    $routes->post('service-billing/rule', 'SaasAdmin\ServiceBilling::saveRule');
    $routes->post('service-billing/approve/(:num)', 'SaasAdmin\ServiceBilling::approve/$1');
    $routes->post('service-billing/cancel/(:num)', 'SaasAdmin\ServiceBilling::cancel/$1');
    $routes->post('service-billing/adjust/(:num)', 'SaasAdmin\ServiceBilling::adjust/$1');

    // -------------------------------
    // SaaS Users (Platform Staff)
    // -------------------------------
    $routes->get('users', 'SaasAdmin\Users::index',['as' => 'saas_admin_users']);
    $routes->get('users/create', 'SaasAdmin\Users::form');
    $routes->post('users/store', 'SaasAdmin\Users::store');
    $routes->get('users/edit/(:num)', 'SaasAdmin\Users::edit/$1');
    $routes->post('users/trash', 'SaasAdmin\Users::trash');
    $routes->post('users/empty-trash', 'SaasAdmin\Users::empty_trash');
    $routes->post('users/restore', 'SaasAdmin\Users::restore');

    // -------------------------------
    // Reports & Analytics
    // -------------------------------
    $routes->get('reports', 'SaasAdmin\Reports::index',['as' => 'saas_admin_reports']);
    $routes->get('reports/schools', 'SaasAdmin\Reports::schools');
    $routes->get('reports/revenue', 'SaasAdmin\Reports::revenue');
    $routes->get('reports/subscriptions', 'SaasAdmin\Reports::subscriptions');

    // -------------------------------
    // System Settings
    // -------------------------------
    $routes->get('settings', 'SaasAdmin\Settings::index',['as' => 'saas_admin_settings']);
    $routes->post('settings/save', 'SaasAdmin\Settings::save');
    $routes->post('settings/test-email', 'SaasAdmin\Settings::testEmail');


    // -------------------------------
    // Email / Notification
    // -------------------------------
    $routes->get('notifications', 'SaasAdmin\Notifications::index',['as' => 'saas_admin_notifications']);
    $routes->post('notifications/send', 'SaasAdmin\Notifications::send');

    // -------------------------------
    // Audit Logs
    // -------------------------------
    $routes->get('logs', 'SaasAdmin\Logs::index',['as' => 'saas_admin_logs']);

    // -------------------------------
    // Custom Fields - Field Entities
    // -------------------------------
    $routes->get('custom-fields/entities', 'SaasAdmin\CustomFields::entities',['as' => 'saas_admin_cf_entities']);
    $routes->get('custom-fields/entities/create', 'SaasAdmin\CustomFields::createEntity');
    $routes->post('custom-fields/entities/store', 'SaasAdmin\CustomFields::storeEntity');
    $routes->get('custom-fields/entities/edit/(:num)', 'SaasAdmin\CustomFields::editEntity/$1');
    $routes->post('custom-fields/entities/update/(:num)', 'SaasAdmin\CustomFields::updateEntity/$1');
    $routes->post('custom-fields/entities/trash', 'SaasAdmin\CustomFields::trashEntity');
    $routes->post('custom-fields/entities/restore', 'SaasAdmin\CustomFields::restoreEntity');
    $routes->post('custom-fields/entities/empty-trash', 'SaasAdmin\CustomFields::emptyTrashEntity');

    // -------------------------------
    // Custom Fields - Custom Fields
    // -------------------------------
    $routes->get('custom-fields', 'SaasAdmin\CustomFields::fields', ['as' => 'saas_admin_custom_fields']);
    $routes->post('custom-fields/trash', 'SaasAdmin\CustomFields::trashField');
    $routes->post('custom-fields/restore', 'SaasAdmin\CustomFields::restoreField');
    $routes->post('custom-fields/empty-trash', 'SaasAdmin\CustomFields::emptyTrashField');

    // -------------------------------
    // Custom Fields - Field Types
    // -------------------------------
    $routes->get('custom-fields/types', 'SaasAdmin\CustomFields::types',['as' => 'saas_admin_cf_types']);
    $routes->get('custom-fields/types/create', 'SaasAdmin\CustomFields::createType');
    $routes->post('custom-fields/types/store', 'SaasAdmin\CustomFields::storeType');
    $routes->get('custom-fields/types/edit/(:num)', 'SaasAdmin\CustomFields::editType/$1');
    $routes->post('custom-fields/types/update/(:num)', 'SaasAdmin\CustomFields::updateType/$1');
    $routes->post('custom-fields/types/trash', 'SaasAdmin\CustomFields::trashType');
    $routes->post('custom-fields/types/restore', 'SaasAdmin\CustomFields::restoreType');
    $routes->post('custom-fields/types/empty-trash', 'SaasAdmin\CustomFields::emptyTrashType');

    // -------------------------------
    // Custom Fields - Field Groups
    // -------------------------------
    $routes->get('custom-fields/groups', 'SaasAdmin\CustomFields::groups', ['as' => 'saas_admin_cf_groups']);
    $routes->get('custom-fields/groups/create', 'SaasAdmin\CustomFields::createGroup');
    $routes->post('custom-fields/groups/store', 'SaasAdmin\CustomFields::storeGroup');
    $routes->get('custom-fields/groups/edit/(:num)', 'SaasAdmin\CustomFields::editGroup/$1');
    $routes->post('custom-fields/groups/update/(:num)', 'SaasAdmin\CustomFields::updateGroup/$1');
    $routes->post('custom-fields/groups/trash', 'SaasAdmin\CustomFields::trashGroup');
    $routes->post('custom-fields/groups/restore', 'SaasAdmin\CustomFields::restoreGroup');
    $routes->post('custom-fields/groups/empty-trash', 'SaasAdmin\CustomFields::emptyTrashGroup');

    // Module Management
    $routes->get('modules', 'SaasAdmin\Modules::index',['as' => 'saas_admin_modules']);
    $routes->post('modules/install', 'SaasAdmin\Modules::install');
    $routes->post('modules/status', 'SaasAdmin\Modules::changeStatus');
    $routes->post('modules/uninstall', 'SaasAdmin\Modules::uninstall');

    // -------------------------------
    // Menu Management
    // -------------------------------
    $routes->get('menus', 'SaasAdmin\Menus::index', ['as' => 'saas_admin_menus']);
    $routes->get('menus/create', 'SaasAdmin\Menus::create');
    $routes->post('menus/store', 'SaasAdmin\Menus::store');
    $routes->get('menus/edit/(:num)', 'SaasAdmin\Menus::edit/$1');
    $routes->post('menus/update/(:num)', 'SaasAdmin\Menus::update/$1');
    $routes->post('menus/status', 'SaasAdmin\Menus::changeStatus');
    $routes->post('menus/update-order', 'SaasAdmin\Menus::updateOrder');
    $routes->post('menus/trash', 'SaasAdmin\Menus::trash');
    $routes->post('menus/empty-trash', 'SaasAdmin\Menus::empty_trash');
    $routes->post('menus/restore', 'SaasAdmin\Menus::restore');
    $routes->get('menus/roles/(:num)', 'SaasAdmin\Menus::roles/$1');
    $routes->post('menus/sync-roles/(:num)', 'SaasAdmin\Menus::syncRoles/$1');

});

// ===============================
// SaaS SCHOOL ADMIN Or Scchool OWNER ROUTES
// ===============================
$routes->group('school-owner', ['filter' => 'role:school-owner'], static function ($routes) {
    // Dashboard
    $routes->get('dashboard', 'SchoolOwner\Dashboard::index',['as' => 'school_owner_dashboard']);

    // Profile
    $routes->get('profile', 'School\Profile::index',['as' => 'school_profile']);
    $routes->post('profile/update', 'School\Profile::update');

    // Change Subscription Plan
    $routes->get('plans/history', 'SchoolOwner\ServiceBilling::legacy');
    $routes->get('plans/upgrade', 'SchoolOwner\ServiceBilling::legacy');
    $routes->post('plans/upgrade', 'SchoolOwner\ServiceBilling::legacy');
    $routes->get('plans/payment/(:segment)', 'SchoolOwner\Plans::continue_payment/$1');
    $routes->get('plans/payment/manual-success/(:segment)', 'SchoolOwner\Plans::manual_success/$1');
    $routes->get('plans/payment/success/(:segment)', 'SchoolOwner\Plans::payment_success/$1');
    $routes->get('plans/payment/stripe/(:segment)', 'SchoolOwner\Plans::stripe_checkout/$1');
    $routes->get('plans/payment/stripe/success/(:segment)', 'SchoolOwner\Plans::stripe_success/$1');
    $routes->get('plans/payment/stripe/cancel/(:segment)', 'SchoolOwner\Plans::stripe_cancel/$1');
    $routes->get('plans/payment/paypal/(:segment)', 'SchoolOwner\Plans::paypal_checkout/$1');
    $routes->get('plans/payment/paypal/success/(:segment)', 'SchoolOwner\Plans::paypal_success/$1');
    $routes->get('plans/payment/paypal/cancel/(:segment)', 'SchoolOwner\Plans::paypal_cancel/$1');

    
    // Subscriptions
    $routes->get('subscriptions', 'SchoolOwner\ServiceBilling::legacy',['as' => 'school_subscriptions']);
    $routes->get('subscriptions/view/(:segment)', 'SchoolOwner\ServiceBilling::legacy');
    $routes->post('subscription/renew', 'SchoolOwner\ServiceBilling::legacy');
    $routes->get('subscriptions/renew/stripe/(:segment)', 'SchoolOwner\Subscriptions::stripe_checkout/$1');
    $routes->get('subscriptions/renew/stripe/success/(:segment)', 'SchoolOwner\Subscriptions::stripe_success/$1');
    $routes->get('subscriptions/renew/stripe/cancel/(:segment)', 'SchoolOwner\Subscriptions::stripe_cancel/$1');
    $routes->get('subscriptions/renew/paypal/(:segment)', 'SchoolOwner\Subscriptions::paypal_checkout/$1');
    $routes->get('subscriptions/renew/paypal/success/(:segment)', 'SchoolOwner\Subscriptions::paypal_success/$1');
    $routes->get('subscriptions/renew/paypal/cancel/(:segment)', 'SchoolOwner\Subscriptions::paypal_cancel/$1');
    $routes->get('subscriptions/renew/manual/(:segment)', 'SchoolOwner\Subscriptions::manual_success/$1');

    // Payments
    $routes->get('payments', 'SchoolOwner\ServiceBilling::legacy');
    $routes->get('payments/(:segment)', 'SchoolOwner\ServiceBilling::legacy');
    $routes->get('billing', 'SchoolOwner\ServiceBilling::index');
    $routes->get('billing/(:segment)', 'SchoolOwner\ServiceBilling::show/$1');
    $routes->post('billing/manual/(:segment)', 'SchoolOwner\ServiceBilling::manual/$1');

    // Failed Payment Retry
    $routes->get('payments/paypal/retry/(:segment)', 'SchoolOwner\Payments::retry_paypal_payment/$1');
    $routes->get('payments/paypal/success/(:segment)', 'SchoolOwner\Payments::retry_paypal_payment_success/$1');
    $routes->get('payments/paypal/cancel/(:segment)', 'SchoolOwner\Payments::retry_paypal_payment_cancel/$1');

    $routes->get('payments/stripe/retry/(:segment)', 'SchoolOwner\Payments::retry_stripe_payment/$1');
    $routes->get('payments/stripe/success/(:segment)', 'SchoolOwner\Payments::stripe_success/$1');
    $routes->get('payments/stripe/cancel/(:segment)', 'SchoolOwner\Payments::stripe_cancel/$1');

    // Schools for multi school management by super admin
    $routes->get('schools', 'SchoolOwner\Schools::index',['as' => 'school_schools']);
    $routes->get('schools/create', 'SchoolOwner\Schools::create');
    $routes->post('schools/store', 'SchoolOwner\Schools::store');
    $routes->get('schools/edit/(:segment)', 'SchoolOwner\Schools::edit/$1');
    $routes->post('schools/update/(:segment)', 'SchoolOwner\Schools::update/$1');
    $routes->post('schools/delete/(:segment)', 'SchoolOwner\Schools::delete/$1');
    $routes->get('schools/view/(:segment)', 'SchoolOwner\Schools::view/$1');
    $routes->post('schools/status/(:segment)', 'SchoolOwner\Schools::changeStatus/$1');

    // School Settings
    $routes->get('settings', 'SchoolOwner\Settings::index',['as' => 'school_settings']);
    $routes->post('settings/update', 'SchoolOwner\Settings::update');
    $routes->post('settings/modules/save', 'SchoolOwner\\Settings::saveModules');

    // User Management
    // $routes->get('users', 'SchoolOwner\UsersController::index',['as' => 'school_users']);
    // $routes->get('users/create', 'SchoolOwner\UsersController::create');
    // $routes->post('users/store', 'SchoolOwner\UsersController::store');
    // $routes->get('users/edit/(:segment)', 'SchoolOwner\UsersController::edit/$1');
    // $routes->post('users/trash/(:segment)', 'SchoolOwner\UsersController::trash/$1');
    // $routes->post('users/empty-trash/(:segment)', 'SchoolOwner\UsersController::empty_trash/$1');
    // $routes->post('users/restore/(:segment)', 'SchoolOwner\UsersController::restore/$1');
    // $routes->get('users/view/(:segment)', 'SchoolOwner\UsersController::view/$1');
    // $routes->post('users/status/(:segment)', 'SchoolOwner\UsersController::changeStatus/$1');

    // Academic Management
    $routes->get('academics', 'SchoolOwner\Academics::index',['as' => 'school_academics']);

    // Academic Year
    $routes->get('academics/years', 'SchoolOwner\AcademicsYears::years');
    $routes->get('academics/years/create', 'SchoolOwner\AcademicsYears::createYear');
    $routes->post('academics/years/store', 'SchoolOwner\AcademicsYears::storeYear');
    $routes->get('academics/years/edit/(:segment)', 'SchoolOwner\AcademicsYears::editYear/$1');
    $routes->post('academics/years/trash/(:segment)', 'SchoolOwner\AcademicsYears::trashYear/$1');
    $routes->post('academics/years/empty-trash/(:segment)', 'SchoolOwner\AcademicsYears::empty_trashYear/$1');
    $routes->post('academics/years/restore/(:segment)', 'SchoolOwner\AcademicsYears::restoreYear/$1');

    // Academic Classes
    $routes->get('academics/classes', 'SchoolOwner\AcademicsClasses::classes');
    $routes->get('academics/classes/create', 'SchoolOwner\AcademicsClasses::createClass');
    $routes->post('academics/classes/store', 'SchoolOwner\AcademicsClasses::storeClass');
    $routes->get('academics/classes/edit/(:segment)', 'SchoolOwner\AcademicsClasses::editClass/$1');
    $routes->post('academics/classes/trash/(:segment)', 'SchoolOwner\AcademicsClasses::trashClass/$1');
    $routes->post('academics/classes/empty-trash/(:segment)', 'SchoolOwner\AcademicsClasses::empty_trashClass/$1');
    $routes->post('academics/classes/restore/(:segment)', 'SchoolOwner\AcademicsClasses::restoreClass/$1');

    // Academic Sections
    $routes->get('academics/sections', 'SchoolOwner\AcademicsSections::sections');
    $routes->get('academics/sections/create', 'SchoolOwner\AcademicsSections::createSection');
    $routes->post('academics/sections/store', 'SchoolOwner\AcademicsSections::storeSection');
    $routes->get('academics/sections/edit/(:segment)', 'SchoolOwner\AcademicsSections::editSection/$1');
    $routes->post('academics/sections/trash/(:segment)', 'SchoolOwner\AcademicsSections::trashSection/$1');
    $routes->post('academics/sections/empty-trash/(:segment)', 'SchoolOwner\AcademicsSections::empty_trashSection/$1');
    $routes->post('academics/sections/restore/(:segment)', 'SchoolOwner\AcademicsSections::restoreSection/$1');

    // Academic Departments
    $routes->get('academics/departments', 'SchoolOwner\AcademicsDepartments::departments');
    $routes->get('academics/departments/create', 'SchoolOwner\AcademicsDepartments::createDepartment');
    $routes->post('academics/departments/store', 'SchoolOwner\AcademicsDepartments::storeDepartment');
    $routes->get('academics/departments/edit/(:segment)', 'SchoolOwner\AcademicsDepartments::editDepartment/$1');
    $routes->post('academics/departments/trash/(:segment)', 'SchoolOwner\AcademicsDepartments::trashDepartment/$1');
    $routes->post('academics/departments/empty-trash/(:segment)', 'SchoolOwner\AcademicsDepartments::empty_trashDepartment/$1');
    $routes->post('academics/departments/restore/(:segment)', 'SchoolOwner\AcademicsDepartments::restoreDepartment/$1');

    // Academic Categories
    $routes->get('academics/categories', 'SchoolOwner\AcademicsCategories::categories');
    $routes->get('academics/categories/create', 'SchoolOwner\AcademicsCategories::createCategory');
    $routes->post('academics/categories/store', 'SchoolOwner\AcademicsCategories::storeCategory');
    $routes->get('academics/categories/edit/(:segment)', 'SchoolOwner\AcademicsCategories::editCategory/$1');
    $routes->post('academics/categories/trash/(:segment)', 'SchoolOwner\AcademicsCategories::trashCategory/$1');
    $routes->post('academics/categories/empty-trash/(:segment)', 'SchoolOwner\AcademicsCategories::empty_trashCategory/$1');
    $routes->post('academics/categories/restore/(:segment)', 'SchoolOwner\AcademicsCategories::restoreCategory/$1');

    // Academic Shifts
    $routes->get('academics/shifts', 'SchoolOwner\AcademicsShifts::shifts');
    $routes->get('academics/shifts/create', 'SchoolOwner\AcademicsShifts::createShift');
    $routes->post('academics/shifts/store', 'SchoolOwner\AcademicsShifts::storeShift');
    $routes->get('academics/shifts/edit/(:segment)', 'SchoolOwner\AcademicsShifts::editShift/$1');
    $routes->post('academics/shifts/trash/(:segment)', 'SchoolOwner\AcademicsShifts::trashShift/$1');
    $routes->post('academics/shifts/empty-trash/(:segment)', 'SchoolOwner\AcademicsShifts::empty_trashShift/$1');
    $routes->post('academics/shifts/restore/(:segment)', 'SchoolOwner\AcademicsShifts::restoreShift/$1');

    
    
    // Student Management
    $routes->get('students', 'SchoolOwner\Students::index');
    $routes->get('students/all', 'SchoolOwner\Students::index');
    $routes->get('students/create', 'SchoolOwner\Students::create');
    $routes->post('students/store', 'SchoolOwner\Students::store');
    $routes->get('students/edit/(:segment)', 'SchoolOwner\Students::edit/$1');
    $routes->post('students/update/(:segment)', 'SchoolOwner\Students::update/$1');
    $routes->post('students/trash/(:segment)', 'SchoolOwner\Students::trash/$1');
    $routes->post('students/empty-trash', 'SchoolOwner\Students::empty_trash');
    $routes->post('students/restore/(:segment)', 'SchoolOwner\Students::restore/$1');
    $routes->get('students/view/(:segment)', 'SchoolOwner\Students::view/$1');

    

    // Student Profile Print and PDF
    $routes->get('students/office-copy-print', 'SchoolOwner\Students::office_copy_print');
    $routes->get('students/office-copy-pdf', 'SchoolOwner\Students::office_copy_pdf');
    $routes->get('students/profile-print/(:segment)', 'SchoolOwner\Students::profile_print/$1');
    $routes->get('students/profile-pdf/(:segment)', 'SchoolOwner\Students::profile_pdf/$1');


    // AJAX: Get academic data by school (years, classes, sections, departments, categories, subjects)
    $routes->post('students/getAcademicData', 'SchoolOwner\Students::getAcademicData');
    $routes->post('students/getAutoGenCodes', 'SchoolOwner\Students::getAutoGenCodes');

    // Bulk student upload from 
        $routes->get('students/bulk/import', 'SchoolOwner\StudentsBulk::bulk_import');

        // Download sample files CSV and JSON
        $routes->get('students/bulk/sample-csv', 'SchoolOwner\StudentsBulk::bulk_sample_csv');
        $routes->get('students/bulk/sample-json', 'SchoolOwner\StudentsBulk::bulk_sample_json');

        // Upload file and preview data
        $routes->post('students/bulk/upload', 'SchoolOwner\StudentsBulk::bulk_upload');
        $routes->post('students/bulk/preview', 'SchoolOwner\StudentsBulk::bulk_preview');

        // Validate uploaded data
        $routes->post('students/bulk/validate', 'SchoolOwner\StudentsBulk::bulk_validate');

        // Final import
        $routes->post('students/bulk/import/process', 'SchoolOwner\StudentsBulk::bulk_import_process');

        // Import history
        $routes->get('students/bulk/import/history', 'SchoolOwner\StudentsBulk::bulk_import_history');

        // Import details (AJAX)
        $routes->post('students/bulk/import/details', 'SchoolOwner\StudentsBulk::bulk_import_details');
    

    // Student promotion by principal or head teacher
        // Promotion page
        $routes->get('students/promotion', 'SchoolOwner\StudentsPromotion::index');

        // Get eligible students
        $routes->post('students/promotion/preview', 'SchoolOwner\StudentsPromotion::preview');

        // Promote students
        $routes->post('students/promotion/process', 'SchoolOwner\StudentsPromotion::process');

        // Promotion history
        $routes->get('students/promotion/history', 'SchoolOwner\StudentsPromotion::history');

        // Promotion details
        $routes->get('students/promotion/history/(:num)', 'SchoolOwner\StudentsPromotion::details/$1');

        // Rollback (optional)
        $routes->post('students/promotion/rollback/(:num)', 'SchoolOwner\StudentsPromotion::rollback/$1');


    // Teacher Management
    $routes->get('teachers', 'SchoolOwner\Teachers::index');
    $routes->get('teachers/create', 'SchoolOwner\Teachers::create');
    $routes->post('teachers/store', 'SchoolOwner\Teachers::store');
    $routes->get('teachers/edit/(:segment)', 'SchoolOwner\Teachers::edit/$1');
    $routes->post('teachers/trash/(:segment)', 'SchoolOwner\Teachers::trash/$1');
    $routes->post('teachers/empty-trash/(:segment)', 'SchoolOwner\Teachers::empty_trash/$1');
    $routes->post('teachers/restore/(:segment)', 'SchoolOwner\Teachers::restore/$1');
    $routes->get('teachers/view/(:segment)', 'SchoolOwner\Teachers::view/$1');
    $routes->post('teachers/status/(:segment)', 'SchoolOwner\Teachers::changeStatus/$1');

    // Academic Syllabus
    $routes->get('academics/syllabus', 'SchoolOwner\Academics::syllabus');
    $routes->get('academics/syllabus/create', 'SchoolOwner\Academics::createSyllabus');
    $routes->post('academics/syllabus/store', 'SchoolOwner\Academics::storeSyllabus');
    $routes->get('academics/syllabus/edit/(:num)', 'SchoolOwner\Academics::editSyllabus/$1');
    $routes->post('academics/syllabus/trash/(:num)', 'SchoolOwner\Academics::trashSyllabus/$1');
    $routes->post('academics/syllabus/empty-trash', 'SchoolOwner\Academics::empty_trashSyllabus');
    $routes->post('academics/syllabus/restore/(:num)', 'SchoolOwner\Academics::restoreSyllabus/$1');

    // Academic Routines
    $routes->get('academics/routines', 'SchoolOwner\Academics::routines');
    $routes->get('academics/routines/create', 'SchoolOwner\Academics::createRoutine');
    $routes->post('academics/routines/store', 'SchoolOwner\Academics::storeRoutine');
    $routes->get('academics/routines/edit/(:num)', 'SchoolOwner\Academics::editRoutine/$1');
    $routes->post('academics/routines/trash/(:num)', 'SchoolOwner\Academics::trashRoutine/$1');
    $routes->post('academics/routines/empty-trash', 'SchoolOwner\Academics::empty_trashRoutine');
    $routes->post('academics/routines/restore/(:num)', 'SchoolOwner\Academics::restoreRoutine/$1');


    // -------------------------------
    // Custom Fields
    // -------------------------------
    $routes->get('custom-fields', 'SchoolOwner\CustomFields::index',['as' => 'school_custom_fields']);
    $routes->get('custom-fields/create', 'SchoolOwner\CustomFields::create');
    $routes->post('custom-fields/store', 'SchoolOwner\CustomFields::store');
    $routes->get('custom-fields/edit/(:segment)', 'SchoolOwner\CustomFields::edit/$1');
    $routes->post('custom-fields/update/(:segment)', 'SchoolOwner\CustomFields::update/$1');
    $routes->post('custom-fields/delete/(:segment)', 'SchoolOwner\CustomFields::delete/$1');
    $routes->post('custom-fields/status/(:segment)', 'SchoolOwner\CustomFields::changeStatus/$1');

    // -------------------------------
    // Custom Fields - AJAX
    // -------------------------------
    $routes->post('custom-fields/getFieldsByEntity', 'SchoolOwner\CustomFields::getFieldsByEntity');
    $routes->post('custom-fields/getGroupsByEntity', 'SchoolOwner\CustomFields::getGroupsByEntity');

    // -------------------------------
    // Custom Fields - Options
    // -------------------------------
    $routes->post('custom-fields/options/store', 'SchoolOwner\CustomFields::storeOption');
    $routes->post('custom-fields/options/delete/(:num)', 'SchoolOwner\CustomFields::deleteOption/$1');

    // -------------------------------
    // Custom Fields - Values (AJAX)
    // -------------------------------
    $routes->post('custom-fields/values/save', 'SchoolOwner\CustomFields::saveFieldValue');
    $routes->post('custom-fields/values/get', 'SchoolOwner\CustomFields::getRecordValues');

    
});

// ===============================
// SaaS ADMIN  ROUTES
// ===============================
$routes->group('admin', ['filter' => 'role:school-admin'], static function ($routes) {   
    // Dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index',['as' => 'admin_dashboard']);
    // Profile
    $routes->get('profile', 'Admin\Profile::index',['as' => 'admin_profile']);
    $routes->post('profile/update', 'Admin\Profile::update');

    // Academic Year
    $routes->get('academics/years', 'Admin\Academics::years');
    $routes->get('academics/years/create', 'Admin\Academics::createYear');
    $routes->post('academics/years/store', 'Admin\Academics::storeYear');
    $routes->get('academics/years/edit/(:num)', 'Admin\Academics::editYear/$1');
    $routes->post('academics/years/trash/(:num)', 'Admin\Academics::trashYear/$1');
    $routes->post('academics/years/empty-trash', 'Admin\Academics::empty_trashYear');
    $routes->post('academics/years/restore/(:num)', 'Admin\Academics::restoreYear/$1');

    // Academic Classes
    $routes->get('academics/classes', 'Admin\Academics::classes');
    $routes->get('academics/classes/create', 'Admin\Academics::createClass');
    $routes->post('academics/classes/store', 'Admin\Academics::storeClass');
    $routes->get('academics/classes/edit/(:num)', 'Admin\Academics::editClass/$1');
    $routes->post('academics/classes/trash/(:num)', 'Admin\Academics::trashClass/$1');
    $routes->post('academics/classes/empty-trash', 'Admin\Academics::empty_trashClass');
    $routes->post('academics/classes/restore/(:num)', 'Admin\Academics::restoreClass/$1');

    // Academic Sections
    $routes->get('academics/sections', 'Admin\Academics::sections');
    $routes->get('academics/sections/create', 'Admin\Academics::createSection');
    $routes->post('academics/sections/store', 'Admin\Academics::storeSection');
    $routes->get('academics/sections/edit/(:num)', 'Admin\Academics::editSection/$1');
    $routes->post('academics/sections/trash/(:num)', 'Admin\Academics::trashSection/$1');
    $routes->post('academics/sections/empty-trash', 'Admin\Academics::empty_trashSection');
    $routes->post('academics/sections/restore/(:num)', 'Admin\Academics::restoreSection/$1');

    // Academic Subjects
    $routes->get('academics/subjects', 'Admin\Academics::subjects');
    $routes->get('academics/subjects/create', 'Admin\Academics::createSubject');
    $routes->post('academics/subjects/store', 'Admin\Academics::storeSubject');
    $routes->get('academics/subjects/edit/(:num)', 'Admin\Academics::editSubject/$1');
    $routes->post('academics/subjects/trash/(:num)', 'Admin\Academics::trashSubject/$1');
    $routes->post('academics/subjects/empty-trash', 'Admin\Academics::empty_trashSubject');
    $routes->post('academics/subjects/restore/(:num)', 'Admin\Academics::restoreSubject/$1');

    // Student Management
    $routes->get('students', 'Admin\Students::index');
    $routes->get('students/create', 'Admin\Students::create');
    $routes->post('students/store', 'Admin\Students::store');
    $routes->get('students/edit/(:num)', 'Admin\Students::edit/$1');
    $routes->post('students/trash/(:num)', 'Admin\Students::trash/$1');
    $routes->post('students/empty-trash', 'Admin\Students::empty_trash');
    $routes->post('students/restore/(:num)', 'Admin\Students::restore/$1');

    // Bulk student upload from csv file
    $routes->get('students/csv_upload', 'Admin\Students::csv_upload');
    $routes->post('students/csv_store', 'Admin\Students::csv_store');

    // Academic Syllabus
    $routes->get('academics/syllabus', 'Admin\Academics::syllabus');
    $routes->get('academics/syllabus/create', 'Admin\Academics::createSyllabus');
    $routes->post('academics/syllabus/store', 'Admin\Academics::storeSyllabus');
    $routes->get('academics/syllabus/edit/(:num)', 'Admin\Academics::editSyllabus/$1');
    $routes->post('academics/syllabus/trash/(:num)', 'Admin\Academics::trashSyllabus/$1');
    $routes->post('academics/syllabus/empty-trash', 'Admin\Academics::empty_trashSyllabus');
    $routes->post('academics/syllabus/restore/(:num)', 'Admin\Academics::restoreSyllabus/$1');

    // Academic Routines
    $routes->get('academics/routines', 'Admin\Academics::routines');
    $routes->get('academics/routines/create', 'Admin\Academics::createRoutine');
    $routes->post('academics/routines/store', 'Admin\Academics::storeRoutine');
    $routes->get('academics/routines/edit/(:num)', 'Admin\Academics::editRoutine/$1');
    $routes->post('academics/routines/trash/(:num)', 'Admin\Academics::trashRoutine/$1');
    $routes->post('academics/routines/empty-trash', 'Admin\Academics::empty_trashRoutine');
    $routes->post('academics/routines/restore/(:num)', 'Admin\Academics::restoreRoutine/$1');

    // Academic Exams
    $routes->get('academics/exams', 'Admin\Academics::exams');
    $routes->get('academics/exams/create', 'Admin\Academics::createExam');
    $routes->post('academics/exams/store', 'Admin\Academics::storeExam');
    $routes->get('academics/exams/edit/(:num)', 'Admin\Academics::editExam/$1');
    $routes->post('academics/exams/trash/(:num)', 'Admin\Academics::trashExam/$1');
    $routes->post('academics/exams/empty-trash', 'Admin\Academics::empty_trashExam');
    $routes->post('academics/exams/restore/(:num)', 'Admin\Academics::restoreExam/$1');

    // Academic Results Reports by student, class, section, subject, exam etc
    $routes->get('academics/results', 'Admin\Academics::results');

    // Academic Results Reports by class (all students in a class   with their results) 
    $routes->get('academics/results/class', 'Admin\Academics::resultsByClass');

    // Print all results of a class
    $routes->get('academics/results/class/print/(:num)', 'Admin\Academics::printResultsByClass/$1');

    // Tabulation sheet for a class
    $routes->get('academics/results/class/tabulation/(:num)', 'Admin\Academics::tabulationSheetByClass/$1');

    // Result remark by principal or head teacher
    $routes->post('academics/results/remark', 'Admin\Academics::addRemark');

    // Student promotion by principal or head teacher
    $routes->post('academics/students/promote', 'Admin\Academics::promoteStudents');

    // -------------------------------
    // Custom Fields
    // -------------------------------
    $routes->get('custom-fields', 'Admin\CustomFields::index',['as' => 'admin_custom_fields']);
    $routes->get('custom-fields/create', 'Admin\CustomFields::create');
    $routes->post('custom-fields/store', 'Admin\CustomFields::store');
    $routes->get('custom-fields/edit/(:num)', 'Admin\CustomFields::edit/$1');
    $routes->post('custom-fields/update/(:num)', 'Admin\CustomFields::update/$1');
    $routes->post('custom-fields/delete/(:num)', 'Admin\CustomFields::delete/$1');
    $routes->post('custom-fields/status/(:num)', 'Admin\CustomFields::changeStatus/$1');

    // -------------------------------
    // Custom Fields - Groups
    // -------------------------------
    $routes->get('custom-fields/groups', 'Admin\CustomFields::groups',['as' => 'admin_cf_groups']);
    $routes->get('custom-fields/groups/create', 'Admin\CustomFields::createGroup');
    $routes->post('custom-fields/groups/store', 'Admin\CustomFields::storeGroup');
    $routes->get('custom-fields/groups/edit/(:num)', 'Admin\CustomFields::editGroup/$1');
    $routes->post('custom-fields/groups/update/(:num)', 'Admin\CustomFields::updateGroup/$1');
    $routes->post('custom-fields/groups/delete/(:num)', 'Admin\CustomFields::deleteGroup/$1');

    // -------------------------------
    // Custom Fields - Options
    // -------------------------------
    $routes->post('custom-fields/options/store', 'Admin\CustomFields::storeOption');
    $routes->post('custom-fields/options/delete/(:num)', 'Admin\CustomFields::deleteOption/$1');

    // -------------------------------
    // Custom Fields - Values (AJAX)
    // -------------------------------
    $routes->post('custom-fields/values/save', 'Admin\CustomFields::saveFieldValue');
    $routes->post('custom-fields/values/get', 'Admin\CustomFields::getRecordValues');

});


// ===============================
// SaaS TEACHER ROUTES
// ===============================
$routes->group('teacher', ['filter' => 'role:teacher'], static function ($routes) {   
    // Dashboard
    $routes->get('dashboard', 'Teacher\Dashboard::index',['as' => 'teacher_dashboard']);
    // Profile
    $routes->get('profile', 'Teacher\Profile::index',['as' => 'teacher_profile']);
    $routes->post('profile/update', 'Teacher\Profile::update');

    // Teacher input marks
    $routes->get('marks/input', 'Teacher\Marks::input');
    $routes->post('marks/store', 'Teacher\Marks::store');

    // Input exam mark from csv file
    $routes->get('marks/csv_input', 'Teacher\Marks::csv_input');
    $routes->post('marks/csv_store', 'Teacher\Marks::csv_store');

    // Result remark by class teacher
    $routes->post('results/remark', 'Teacher\Results::addRemark');

    // Teacher view results
    $routes->get('results', 'Teacher\Results::index');

    // Teacher view routines
    $routes->get('routines', 'Teacher\Routines::index');

    // Teacher view syllabus
    $routes->get('syllabus', 'Teacher\Syllabus::index');

    // Teacher view students
    $routes->get('students', 'Teacher\Students::index');

    // Teacher view subjects
    $routes->get('subjects', 'Teacher\Subjects::index');

    // Teacher input attendance
    $routes->get('attendance/input', 'Teacher\Attendance::input');
    $routes->post('attendance/store', 'Teacher\Attendance::store');

    // Teacher view attendance
    $routes->get('attendance', 'Teacher\Attendance::index');

    // Student performance analytics
    $routes->get('analytics/performance', 'Teacher\Analytics::performance');

    // Student attendance analytics
    $routes->get('analytics/attendance', 'Teacher\Analytics::attendance');

    // Student promotion 
    $routes->post('students/promote', 'Teacher\Students::promote');

   

});

// ===============================
// SaaS STUDENT ROUTES
// ===============================
$routes->group('student', ['filter' => 'role:student'], static function ($routes) {   
    // Dashboard
    $routes->get('dashboard', 'Student\Dashboard::index',['as' => 'student_dashboard']);
    // Profile
    $routes->get('profile', 'Student\Profile::index',['as' => 'student_profile']);
    $routes->post('profile/update', 'Student\Profile::update');

    // Student view results
    $routes->get('results', 'Student\Results::index');

    // Student view routines
    $routes->get('routines', 'Student\Routines::index');

    // Student view syllabus
    $routes->get('syllabus', 'Student\Syllabus::index');

    // Student view attendance
    $routes->get('attendance', 'Student\Attendance::index');


});

// ===============================
// SaaS PARENT ROUTES
// ===============================
$routes->group('parent', ['filter' => 'role:parent'], static function ($routes) {   
    // Dashboard
    $routes->get('dashboard', 'Parent\Dashboard::index',['as' => 'parent_dashboard']);
    // Profile
    $routes->get('profile', 'Parent\Profile::index',['as' => 'parent_profile']);
    $routes->post('profile/update', 'Parent\Profile::update');

    // Parent view results
    $routes->get('results', 'Parent\Results::index');

    // Parent view routines
    $routes->get('routines', 'Parent\Routines::index');

    // Parent view syllabus
    $routes->get('syllabus', 'Parent\Syllabus::index');

    // Parent view attendance
    $routes->get('attendance', 'Parent\Attendance::index');


});


// ===============================
// TOKEN BASED API ROUTES
// ===============================
$routes->group('api/v1', ['filter' => 'token'], static function ($routes) {

    // Subjects API
    $routes->get('subjects', 'Api\V1\SubjectsController::index');
    $routes->get('subjects/(:num)', 'Api\V1\SubjectsController::show/$1');
    $routes->post('subjects', 'Api\V1\SubjectsController::store');
    $routes->post('subjects/(:num)', 'Api\V1\SubjectsController::update/$1');

    // Mark Distributions API
    $routes->get('mark-distributions', 'Api\V1\MarkDistributionsController::index');
    $routes->get('mark-distributions/(:num)', 'Api\V1\MarkDistributionsController::show/$1');
    $routes->post('mark-distributions', 'Api\V1\MarkDistributionsController::store');
    $routes->post('mark-distributions/(:num)', 'Api\V1\MarkDistributionsController::update/$1');

    // Subject Distributions API
    $routes->get('subject-distributions', 'Api\V1\SubjectDistributionsController::index');
    $routes->get('subject-distributions/(:num)', 'Api\V1\SubjectDistributionsController::show/$1');
    $routes->post('subject-distributions', 'Api\V1\SubjectDistributionsController::store');
    $routes->post('subject-distributions/(:num)', 'Api\V1\SubjectDistributionsController::update/$1');

    // Marks API
    $routes->get('marks', 'Api\V1\MarksController::index');
    $routes->get('marks/(:num)', 'Api\V1\MarksController::show/$1');
    $routes->post('marks', 'Api\V1\MarksController::store');
    $routes->post('marks/(:num)', 'Api\V1\MarksController::update/$1');
});

// Student QR Code Verification
$routes->get('students/verify/(:segment)', 'VerifyStudents::verify/$1');
$routes->get('students/verify/qr-code/(:segment)', 'VerifyStudents::verify_qr_code/$1');

// Student Public Profile by token
$routes->get('students/profile/(:segment)', 'PublicStudentsProfile::profile/$1');
    

// file upload 
$routes->get('uploads/(:any)', 'Uploads::serve/$1');

// no access
$routes->get('/no-access', 'NoAccess::noAccess');

// unauthorized
$routes->get('/unauthorized', 'Unauthorized::unauthorized');


$modulesPath = ROOTPATH . 'app/Modules/';
$modules = scandir($modulesPath);

foreach ($modules as $module) {
    // Admission is temporarily disabled system-wide, including its public portal.
    if (strcasecmp($module, 'admission') === 0) {
        continue;
    }

    if ($module !== '.' && $module !== '..' && is_dir($modulesPath . $module)) {
        $routesPath = $modulesPath . $module . '/Config/Routes.php';
        if (file_exists($routesPath)) {
            require $routesPath;
        }
    }
}
