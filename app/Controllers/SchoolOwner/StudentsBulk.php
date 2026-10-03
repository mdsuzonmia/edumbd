<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\StudentEnrollmentModel;
use App\Models\StudentGuardianModel;
use App\Models\StudentAddressModel;
use App\Models\SchoolModel;
use App\Models\AcademicsYearModel;
use App\Models\AcademicsClassesModel;
use App\Models\AcademicsSectionModel;
use App\Models\AcademicsDepartmentModel;
use App\Models\AcademicsCategoryModel;
use App\Models\AcademicsShiftModel;
use App\Models\ImportLogModel;
use App\Models\ImportLogItemModel;
use App\Models\UserModel;
use App\Models\UserRoleModel;
use App\Models\CustomField\CustomFieldEntityModel;
use App\Models\CustomField\CustomFieldModel;
use App\Models\CustomField\CustomFieldValueModel;

class StudentsBulk extends BaseController
{
    protected StudentModel $StudentModel;
    protected StudentEnrollmentModel $EnrollmentModel;
    protected StudentGuardianModel $GuardianModel;
    protected StudentAddressModel $AddressModel;
    protected SchoolModel $SchoolModel;
    protected AcademicsYearModel $YearModel;
    protected AcademicsClassesModel $ClassModel;
    protected AcademicsSectionModel $SectionModel;
    protected AcademicsDepartmentModel $DepartmentModel;
    protected AcademicsCategoryModel $CategoryModel;
    protected AcademicsShiftModel $ShiftModel;
    protected ImportLogModel $ImportLogModel;
    protected ImportLogItemModel $ImportLogItemModel;
    protected UserModel $UserModel;
    protected UserRoleModel $UserRoleModel;
    protected CustomFieldEntityModel $EntityModel;
    protected CustomFieldModel $FieldModel;
    protected CustomFieldValueModel $ValueModel;

    public function __construct()
    {
        helper('customfield');

        $this->StudentModel          = new StudentModel();
        $this->EnrollmentModel       = new StudentEnrollmentModel();
        $this->GuardianModel         = new StudentGuardianModel();
        $this->AddressModel          = new StudentAddressModel();
        $this->SchoolModel           = new SchoolModel();
        $this->YearModel             = new AcademicsYearModel();
        $this->ClassModel            = new AcademicsClassesModel();
        $this->SectionModel          = new AcademicsSectionModel();
        $this->DepartmentModel       = new AcademicsDepartmentModel();
        $this->CategoryModel         = new AcademicsCategoryModel();
        $this->ShiftModel            = new AcademicsShiftModel();
        $this->ImportLogModel        = new ImportLogModel();
        $this->ImportLogItemModel    = new ImportLogItemModel();
        $this->UserModel             = new UserModel();
        $this->UserRoleModel         = new UserRoleModel();
        $this->EntityModel           = new CustomFieldEntityModel();
        $this->FieldModel            = new CustomFieldModel();
        $this->ValueModel            = new CustomFieldValueModel();
    }

    protected function getUserId(): int
    {
        return (int) session('user_id');
    }

    protected function getUserSchools(): array
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return [];
        }

        return $this->SchoolModel
            ->select('schools.id, schools.name, schools.params')
            ->join('school_user_relation', 'school_user_relation.school_id = schools.id', 'left')
            ->where('school_user_relation.user_id', $user_id)
            ->where('schools.status', 1)
            ->orderBy('schools.name', 'ASC')
            ->findAll();
    }

    protected function getSchoolDropdown(): array
    {
        $schools = $this->getUserSchools();
        $list = [];
        foreach ($schools as $s) {
            $list[$s->id] = $s->name;
        }
        return $list;
    }

    protected function isSchoolSettingEnabled(int $school_id, string $key): bool
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return false;
        }
        $params = json_decode($school->params, true);
        return !empty($params[$key]);
    }

    protected function isStudentAccountEnabled(int $school_id): bool
    {
        return $this->isSchoolSettingEnabled($school_id, 'student_account_enabled');
    }

    protected function getCustomFields(int $school_id): array
    {
        $entity = $this->EntityModel->where('slug', 'student')->first();
        if (!$entity) {
            return [];
        }

        return $this->FieldModel->getImportFields($school_id, (int) $entity->id) ?: [];
    }

    protected function getStudentEntityId(): int
    {
        $entity = $this->EntityModel->where('slug', 'student')->first();
        return $entity ? (int) $entity->id : 0;
    }

    protected function getAcademicReferenceMaps(int $school_id): array
    {
        return [
            'session'    => $this->buildIdAndTitleMap($this->YearModel->where('school_id', $school_id)->findAll()),
            'class'      => $this->buildIdAndTitleMap($this->ClassModel->where('school_id', $school_id)->findAll()),
            'section'    => $this->buildIdAndTitleMap($this->SectionModel->where('school_id', $school_id)->findAll()),
            'department' => $this->buildIdAndTitleMap($this->DepartmentModel->where('school_id', $school_id)->findAll()),
            'category'   => $this->buildIdAndTitleMap($this->CategoryModel->where('school_id', $school_id)->findAll()),
            'shift'      => $this->buildIdAndTitleMap($this->ShiftModel->where('school_id', $school_id)->findAll()),
        ];
    }

    protected function buildIdAndTitleMap(array $records): array
    {
        $map = [];
        foreach ($records as $record) {
            $id = (int) ($record->id ?? 0);
            $title = trim((string) ($record->title ?? ''));
            if (!$id || $title === '') {
                continue;
            }

            $map[$id] = $id;
            $map[$title] = $id;
            $map[strtolower($title)] = $id;
        }
        return $map;
    }

    protected function resolveReferenceValue(string $value, array $map, string $label): int
    {
        $value = trim($value);
        if ($value === '') {
            return 0;
        }

        if (isset($map[$value])) {
            return (int) $map[$value];
        }

        $normalized = strtolower($value);
        if (isset($map[$normalized])) {
            return (int) $map[$normalized];
        }

        if (ctype_digit($value)) {
            $id = (int) $value;
            if (isset($map[$id])) {
                return (int) $map[$id];
            }
        }

        throw new \RuntimeException($label . ' "' . $value . '" was not found.');
    }

    protected function resolveAcademicReferences(array $data, int $school_id): array
    {
        $maps = $this->getAcademicReferenceMaps($school_id);

        $session = $this->resolveReferenceValue((string) ($data['session_id'] ?? $data['session'] ?? ''), $maps['session'], 'Academic year');
        $class   = $this->resolveReferenceValue((string) ($data['class_id'] ?? $data['class'] ?? ''), $maps['class'], 'Class');

        if (!$session) {
            throw new \RuntimeException('Academic year is required.');
        }
        if (!$class) {
            throw new \RuntimeException('Class is required.');
        }

        $section = 0;
        if (!empty($data['section_id'] ?? $data['section'] ?? '')) {
            $section = $this->resolveReferenceValue((string) ($data['section_id'] ?? $data['section'] ?? ''), $maps['section'], 'Section');
        }

        $department = 0;
        if (!empty($data['department_id'] ?? $data['department'] ?? '')) {
            $department = $this->resolveReferenceValue((string) ($data['department_id'] ?? $data['department'] ?? ''), $maps['department'], 'Department');
        }

        $category = 0;
        if (!empty($data['category_id'] ?? $data['category'] ?? '')) {
            $category = $this->resolveReferenceValue((string) ($data['category_id'] ?? $data['category'] ?? ''), $maps['category'], 'Category');
        }

        $shift = 0;
        if (!empty($data['shift_id'] ?? $data['shift'] ?? '')) {
            $shift = $this->resolveReferenceValue((string) ($data['shift_id'] ?? $data['shift'] ?? ''), $maps['shift'], 'Shift');
        }

        return [
            'session_id'    => $session,
            'class_id'      => $class,
            'section_id'    => $section ?: null,
            'department_id' => $department ?: null,
            'category_id'   => $category ?: null,
            'shift_id'      => $shift ?: null,
        ];
    }

    protected function getCustomFieldMap(int $school_id): array
    {
        $fields = $this->getCustomFields($school_id);
        $map = [];
        foreach ($fields as $field) {
            $header = $this->getCustomFieldHeader($field);
            $map[$header] = $field;
            $map[strtolower($header)] = $field;
            $map[strtolower((string) $field->label)] = $field;
            $map[(string) $field->id] = $field;
        }
        return $map;
    }

    protected function normalizeCustomFieldValue(object $field, string $rawValue): ?string
    {
        $rawValue = trim($rawValue);
        if ($rawValue === '') {
            return null;
        }

        $fieldType = strtolower((string) ($field->field_type_slug ?? $field->field_type ?? 'text'));
        if (!in_array($fieldType, ['select', 'radio', 'checkbox', 'multiselect', 'multi_select'], true)) {
            return $rawValue;
        }

        $options = get_custom_field_options((int) $field->id);
        if (empty($options)) {
            return $rawValue;
        }

        $lookup = [];
        foreach ($options as $opt) {
            $lookup[strtolower(trim((string) $opt->option_label))] = (string) $opt->option_value;
            $lookup[strtolower(trim((string) $opt->option_value))] = (string) $opt->option_value;
        }

        if (in_array($fieldType, ['checkbox', 'multiselect', 'multi_select'], true)) {
            $parts = preg_split('/[|,]/', $rawValue) ?: [];
            $values = [];
            foreach ($parts as $part) {
                $part = trim($part);
                if ($part === '') {
                    continue;
                }
                $key = strtolower($part);
                if (isset($lookup[$key])) {
                    $values[] = $lookup[$key];
                } else {
                    $values[] = $part;
                }
            }
            return implode(',', array_values(array_unique($values)));
        }

        $key = strtolower($rawValue);
        return $lookup[$key] ?? $rawValue;
    }

    protected function extractCustomFieldValues(array $data, int $school_id): array
    {
        $values = [];
        $fieldMap = $this->getCustomFieldMap($school_id);

        foreach ($data as $key => $value) {
            if (strpos($key, 'custom_') !== 0) {
                continue;
            }

            $field = $fieldMap[$key] ?? $fieldMap[strtolower($key)] ?? null;
            if (!$field) {
                continue;
            }

            $normalized = is_array($value) ? implode(',', $value) : (string) $value;
            $values[(int) $field->id] = $this->normalizeCustomFieldValue($field, $normalized);
        }

        return $values;
    }

    // ================================================================
    //  1. IMPORT PAGE
    // ================================================================
    public function bulk_import()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = 'Bulk Student Import';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $data = [
            'school_list' => $this->getSchoolDropdown(),
        ];

        return view('header', $header_data)
            . view('school_owner/students/bulk_import', $data)
            . view('footer', $footer_data);
    }

    // ================================================================
    //  2. DOWNLOAD SAMPLE CSV
    // ================================================================
    public function bulk_sample_csv()
    {
        $school_id = (int) $this->request->getGet('school_id');
        if (!$school_id) {
            return redirect()->back()->with('error', 'School ID is required.');
        }

        $headers = $this->getSampleHeaders($school_id);
        $sample  = $this->getSampleRow($school_id, $headers);

        $filename = 'student_bulk_upload_sample_' . date('Y-m-d') . '.csv';

        $this->response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');

        $fp = fopen('php://output', 'w');
        fwrite($fp, "\xEF\xBB\xBF"); // BOM for Excel
        fputcsv($fp, $headers);
        fputcsv($fp, $sample);
        fclose($fp);

        return $this->response;
    }

    // ================================================================
    //  3. DOWNLOAD SAMPLE JSON
    // ================================================================
    public function bulk_sample_json()
    {
        $school_id = (int) $this->request->getGet('school_id');
        if (!$school_id) {
            return redirect()->back()->with('error', 'School ID is required.');
        }

        $headers = $this->getSampleHeaders($school_id);
        $sample  = $this->getSampleRow($school_id, $headers);

        $data = [
            'headers' => $headers,
            'rows'    => [$sample],
        ];

        $filename = 'student_bulk_upload_sample_' . date('Y-m-d') . '.json';

        return $this->response
            ->setHeader('Content-Type', 'application/json')
            ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->setJSON($data, JSON_PRETTY_PRINT);
    }

    // ================================================================
    //  4. UPLOAD FILE (parse and return preview)
    // ================================================================
    public function bulk_upload()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Invalid request.')]);
        }

        $response = ['status' => false, 'html' => ''];
        $file = $this->request->getFile('bulk_file');
        $school_id = (int) $this->request->getPost('school_id');

        if (!$school_id) {
            $response['html'] = message_generator('error', 'School is required.');
            return $this->jsonResponse($response);
        }

        if (!$file || !$file->isValid()) {
            $response['html'] = message_generator('error', 'No file uploaded or invalid file.');
            return $this->jsonResponse($response);
        }

        $extension = strtolower($file->getClientExtension());
        if (!in_array($extension, ['csv', 'json'])) {
            $response['html'] = message_generator('error', 'Only CSV and JSON files are allowed.');
            return $this->jsonResponse($response);
        }

        try {
            $parsed = ($extension === 'csv')
                ? $this->parseCSV($file->getTempName())
                : $this->parseJSON($file->getTempName());

            if (empty($parsed['headers']) || empty($parsed['rows'])) {
                $response['html'] = message_generator('error', 'File is empty or has no valid data.');
                return $this->jsonResponse($response);
            }

            session()->set('bulk_upload_data', [
                'school_id' => $school_id,
                'headers'   => $parsed['headers'],
                'rows'      => $parsed['rows'],
                'file_type' => $extension,
                'file_name' => $file->getClientName(),
            ]);

            $response['status'] = true;
            $response['html']   = $this->renderPreviewTable($parsed['headers'], $parsed['rows']);
        } catch (\Exception $e) {
            $response['html'] = message_generator('error', 'Error parsing file: ' . $e->getMessage());
        }

        return $this->jsonResponse($response);
    }

    // ================================================================
    //  5. PREVIEW (re-render from session)
    // ================================================================
    public function bulk_preview()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Invalid request.')]);
        }

        $uploadData = session('bulk_upload_data');
        if (!$uploadData || empty($uploadData['rows'])) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'No data found. Please upload a file first.')]);
        }

        return $this->jsonResponse([
            'status' => true,
            'html'   => $this->renderPreviewTable($uploadData['headers'], $uploadData['rows']),
        ]);
    }

    // ================================================================
    //  6. VALIDATE DATA
    // ================================================================
    public function bulk_validate()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Invalid request.')]);
        }

        $uploadData = session('bulk_upload_data');
        if (!$uploadData || empty($uploadData['rows'])) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'No data found. Please upload a file first.')]);
        }

        $school_id = $uploadData['school_id'];
        $headers   = $uploadData['headers'];
        $rows      = $uploadData['rows'];

        $errors = $this->validateRows($headers, $rows, $school_id);

        if (!empty($errors)) {
            $html = '<div class="alert alert-danger">';
            $html .= '<h5><i class="fa fa-exclamation-triangle"></i> Validation Errors (' . count($errors) . ')</h5>';
            $html .= '<ul>';
            foreach ($errors as $err) {
                $html .= '<li><strong>Row ' . $err['row'] . ':</strong> ' . esc($err['message']) . '</li>';
            }
            $html .= '</ul></div>';
            $html .= $this->renderPreviewTable($headers, $rows, $errors);

            return $this->jsonResponse([
                'status'      => false,
                'html'        => $html,
                'error_count' => count($errors),
            ]);
        }

        return $this->jsonResponse([
            'status' => true,
            'html'   => '<div class="alert alert-success"><i class="fa fa-check-circle"></i> All ' . count($rows) . ' records are valid!</div>'
                      . $this->renderPreviewTable($headers, $rows),
        ]);
    }

    // ================================================================
    //  7. PROCESS IMPORT
    // ================================================================
    public function bulk_import_process()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Invalid request.')]);
        }

        // Check subscription
        $subscription_check = check_subscription('school-owner/students/bulk');
        if ($subscription_check) {
            return $this->jsonResponse($subscription_check);
        }

        $uploadData = session('bulk_upload_data');
        if (!$uploadData || empty($uploadData['rows'])) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'No data found. Please upload a file first.')]);
        }

        $user_id   = $this->getUserId();
        $school_id = $uploadData['school_id'];
        $headers   = $uploadData['headers'];
        $rows      = $uploadData['rows'];

        // Validate before import
        $errors = $this->validateRows($headers, $rows, $school_id);
        if (!empty($errors)) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Please fix validation errors before importing.')]);
        }

        $now       = date('Y-m-d H:i:s');
        $total     = count($rows);
        $success   = 0;
        $failed    = 0;
        $errorLog  = [];

        // Create import log
        $logData = [
            'school_id'       => $school_id,
            'user_id'         => $user_id,
            'module'          => 'student_bulk',
            'file_type'       => $uploadData['file_type'] ?? 'csv',
            'file_name'       => $uploadData['file_name'] ?? '',
            'total_records'   => $total,
            'success_records' => 0,
            'failed_records'  => 0,
            'status'          => 'processing',
            'created_at'      => $now,
            'updated_at'      => $now,
        ];

        if (!$this->ImportLogModel->insert($logData)) {
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Failed to create import log.')]);
        }
        $importLogId = (int) $this->ImportLogModel->insertID();

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($rows as $rowIndex => $row) {
                $rowNumber = $rowIndex + 2;
                $rowData   = $this->combineRow($headers, $row);

                try {
                    $this->importSingleStudent($rowData, $school_id, $user_id, $now);
                    $success++;

                    $this->ImportLogItemModel->insert([
                        'import_log_id' => $importLogId,
                        'row_number'    => $rowNumber,
                        'row_data'      => json_encode($rowData),
                        'status'        => 'success',
                        'error_message' => null,
                    ]);
                } catch (\Throwable $e) {
                    $failed++;
                    $errorLog[] = 'Row ' . $rowNumber . ': ' . $e->getMessage();

                    $this->ImportLogItemModel->insert([
                        'import_log_id' => $importLogId,
                        'row_number'    => $rowNumber,
                        'row_data'      => json_encode($rowData),
                        'status'        => 'failed',
                        'error_message' => $e->getMessage(),
                    ]);
                }
            }

            $this->ImportLogModel->update($importLogId, [
                'success_records' => $success,
                'failed_records'  => $failed,
                'status'          => ($failed === 0) ? 'completed' : 'completed_with_errors',
                'error_log'       => !empty($errorLog) ? json_encode($errorLog) : null,
                'updated_at'      => $now,
            ]);

            $db->transComplete();
            session()->remove('bulk_upload_data');

            $message = 'Import completed. Success: ' . $success . ', Failed: ' . $failed . '.';

            return $this->jsonResponse([
                'status' => true,
                'html'   => message_generator('success', $message)
                          . '<div class="mt-2"><a href="' . site_url('school-owner/students') . '" class="btn btn-primary"><i class="fa fa-arrow-left"></i> Back to Students</a></div>',
            ]);
        } catch (\Throwable $e) {
            $db->transRollback();
            return $this->jsonResponse(['status' => false, 'html' => message_generator('error', 'Import failed: ' . $e->getMessage())]);
        }
    }

    // ================================================================
    //  8. IMPORT HISTORY
    // ================================================================
    public function bulk_import_history()
    {
        $user_id = $this->getUserId();
        if (!$user_id) {
            return redirect()->to('login');
        }

        $header_data['page_title'] = 'Import History';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        $userSchools = $this->getUserSchools();
        $schoolIds   = array_map(function ($s) { return $s->id; }, $userSchools);
        $school_id   = (int) $this->request->getGet('school_id');

        $this->ImportLogModel
            ->where('module', 'student_bulk')
            ->whereIn('school_id', $schoolIds);

        if ($school_id && in_array($school_id, $schoolIds)) {
            $this->ImportLogModel->where('school_id', $school_id);
        }

        $this->ImportLogModel->orderBy('id', 'DESC');

        $perPage = (int) setting('application', 'per_item_in_list') ?: 10;
        $items   = $this->ImportLogModel->paginate($perPage);

        foreach ($items as &$item) {
            $school = $this->SchoolModel->find($item->school_id);
            $item->school_name = $school ? $school->name : 'N/A';
        }

        $data = [
            'items'           => $items,
            'pager'           => $this->ImportLogModel->pager,
            'pagerTemplate'   => 'custom_pagination',
            'school_list'     => $this->getSchoolDropdown(),
            'selected_school' => $school_id,
        ];

        return view('header', $header_data)
            . view('school_owner/students/bulk_history', $data)
            . view('footer', $footer_data);
    }

    // ================================================================
    //  9. IMPORT DETAILS (AJAX - view import log items)
    // ================================================================
    public function bulk_import_details()
    {
        if (!$this->request->isAJAX()) {
            return $this->jsonResponse(['status' => false, 'message' => 'Invalid request.']);
        }

        $import_log_id = (int) $this->request->getPost('import_log_id');
        if (!$import_log_id) {
            return $this->jsonResponse(['status' => false, 'message' => 'Import log ID is required.']);
        }

        $log = $this->ImportLogModel->find($import_log_id);
        if (!$log) {
            return $this->jsonResponse(['status' => false, 'message' => 'Import log not found.']);
        }

        $items = $this->ImportLogItemModel
            ->where('import_log_id', $import_log_id)
            ->orderBy('row_number', 'ASC')
            ->findAll();

        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-bordered table-sm table-striped">';
        $html .= '<thead class="thead-light"><tr>
                    <th>Row</th>
                    <th>Status</th>
                    <th>Error</th>
                    <th>Row Data</th>
                </tr></thead><tbody>';

        foreach ($items as $item) {
            $badge = $item->status === 'success'
                ? '<span class="badge badge-success">Success</span>'
                : '<span class="badge badge-danger">Failed</span>';

            $rowData = json_decode($item->row_data, true);
            $dataStr = $rowData
                ? '<pre class="mb-0" style="max-height:80px;overflow-y:auto;font-size:11px;">' . esc(json_encode($rowData, JSON_PRETTY_PRINT)) . '</pre>'
                : esc($item->row_data);

            $html .= '<tr>';
            $html .= '<td>' . $item->row_number . '</td>';
            $html .= '<td>' . $badge . '</td>';
            $html .= '<td>' . esc($item->error_message ?? 'N/A') . '</td>';
            $html .= '<td>' . $dataStr . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        return $this->jsonResponse(['status' => true, 'html' => $html]);
    }

    // ================================================================
    //  PRIVATE HELPERS
    // ================================================================

    private function getSampleHeaders(int $school_id): array
    {
        // Predefined headers for students personal information
        $headers = [
            'first_name',
            'middle_name',
            'last_name',
            'gender',
            'date_of_birth',
            'phone',
            'email',
            'religion',
            'nationality',
            'blood_group',
            'student_code',
            'admission_date',
        ];

        foreach ($this->getCustomFields($school_id) as $field) {
            $headers[] = $this->getCustomFieldHeader($field);
        }

        // Set Student Academic Information
        $headers[] = 'session';
        $headers[] = 'class';
        
        $settings = [
            'roll'       => 'academic_class_roll_enabled',
            'section'    => 'academic_section_enabled',
            'department' => 'academic_department_enabled',
            'category'   => 'academic_category_enabled',
            'shift'      => 'academic_shift_enabled'
        ];

        foreach ($settings as $header => $setting) {
            if ($this->isSchoolSettingEnabled($school_id, $setting)) {
                $headers[] = $header;
            }
        }

        $headers[] = 'guardian_name';
        $headers[] = 'guardian_relation';
        $headers[] = 'guardian_phone';
        $headers[] = 'guardian_email';
        $headers[] = 'guardian_occupation';

        return $headers;
    }

    private function getSampleRow(int $school_id, array $headers): array
    {
        $defaults = [
            // Personal fields
            'first_name'      => 'Mr.',
            'middle_name'     => 'John',
            'last_name'       => 'Doe',
            'gender'          => 'Male',
            'date_of_birth'   => '2010-01-15',
            'phone'           => '01712345678',
            'email'           => 'john.doe@example.com',
            'religion'        => 'Islam',
            'nationality'     => 'Bangladeshi',
            'blood_group'     => 'A+',
            'student_code'    => 'STU-20240001',
            'admission_date'  => date('Y-m-d'),

            // Academic fields
            'roll'         => '1001',
            'session'      => '2026',
            'class'        => 'One',
            'section'      => 'A',
            'department'   => 'Science',
            'category'     => 'General',

            // Guardian fields
            'guardian_name'        => 'Jane Doe',
            'guardian_relation'    => 'Mother',
            'guardian_phone'       => '01798765432',
            'guardian_email'       => 'jane.doe@example.com',
            'guardian_occupation'  => 'Teacher',
        ];

        $customDefaults = [];
        foreach ($this->getCustomFields($school_id) as $field) {
            $header = $this->getCustomFieldHeader($field);
            $customDefaults[$header] = $this->getSampleCustomFieldValue($field);
        }

        $row = [];
        foreach ($headers as $h) {
            if (isset($defaults[$h])) {
                $row[] = $defaults[$h];
            } elseif (isset($customDefaults[$h])) {
                $row[] = $customDefaults[$h];
            } else {
                $row[] = '';
            }
        }
        return $row;
    }

    private function getSampleCustomFieldValue(object $field): string
    {
        $fieldType = strtolower((string) ($field->field_type_slug ?? $field->field_type ?? 'text'));
        $defaultValue = trim((string) ($field->default_value ?? ''));
        if ($defaultValue !== '') {
            return $defaultValue;
        }

        if (in_array($fieldType, ['select', 'radio', 'checkbox', 'multiselect', 'multi_select'], true)) {
            $options = get_custom_field_options((int) $field->id);
            if (!empty($options)) {
                if (in_array($fieldType, ['checkbox', 'multiselect', 'multi_select'], true) && count($options) > 1) {
                    return (string) $options[0]->option_value . '|' . (string) $options[1]->option_value;
                }
                return (string) $options[0]->option_value;
            }
        }

        return 'Sample Value';
    }

    private function getCustomFieldHeader(object $field): string
    {
        return 'custom_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string) $field->field_key);
    }

    private function combineRow(array $headers, array $row): array
    {
        $row = array_pad($row, count($headers), '');
        if (count($row) > count($headers)) {
            $row = array_slice($row, 0, count($headers));
        }

        return array_combine($headers, $row) ?: [];
    }

    private function parseCSV(string $filePath): array
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \RuntimeException('Could not open CSV file.');
        }

        $bom = fread($handle, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($handle);
        }

        $headers = fgetcsv($handle);
        if (!$headers || empty($headers)) {
            fclose($handle);
            throw new \RuntimeException('CSV file has no header row.');
        }

        $headers = array_map('trim', $headers);

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_map('trim', $row);
            if (count($row) === 1 && $row[0] === '') {
                continue;
            }
            $rows[] = $row;
        }

        fclose($handle);
        return ['headers' => $headers, 'rows' => $rows];
    }

    private function parseJSON(string $filePath): array
    {
        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        if ($data === null) {
            throw new \RuntimeException('Invalid JSON file.');
        }

        // Format: { headers: [...], rows: [[...]] }
        if (isset($data['headers']) && isset($data['rows'])) {
            $headers = array_map('trim', $data['headers']);
            $rows = [];
            foreach ($data['rows'] as &$r) {
                if (is_array($r)) {
                    $rows[] = array_map('trim', $r);
                }
            }
            return ['headers' => $headers, 'rows' => $rows];
        }

        // Format: [{ col1: val1, ... }]
        if (is_array($data) && isset($data[0]) && is_array($data[0])) {
            $headers = array_keys($data[0]);
            $rows = [];
            foreach ($data as $item) {
                $row = [];
                foreach ($headers as $h) {
                    $row[] = isset($item[$h]) ? trim((string) $item[$h]) : '';
                }
                $rows[] = $row;
            }
            return ['headers' => $headers, 'rows' => $rows];
        }

        throw new \RuntimeException('Unsupported JSON format.');
    }

    private function validateRows(array $headers, array $rows, int $school_id): array
    {
        $errors = [];
        $customFields = $this->getCustomFields($school_id);
        $seenStudentCodes = [];
        $seenCustomValues = [];

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $data = $this->combineRow($headers, $row);

            if (empty($data['first_name'])) {
                $errors[] = ['row' => $rowNumber, 'message' => 'First name is required.'];
            }

            if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Invalid email format.'];
            }

            if (!empty($data['date_of_birth']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['date_of_birth'])) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Date of birth must be in YYYY-MM-DD format.'];
            }

            if (!empty($data['admission_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['admission_date'])) {
                $errors[] = ['row' => $rowNumber, 'message' => 'Admission date must be in YYYY-MM-DD format.'];
            }

            if (!empty($data['student_code'])) {
                $studentCodeKey = strtolower(trim((string) $data['student_code']));
                if (isset($seenStudentCodes[$studentCodeKey])) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Student code "' . $data['student_code'] . '" is duplicated in the uploaded file.'];
                }
                $seenStudentCodes[$studentCodeKey] = true;

                $existing = $this->StudentModel
                    ->where('school_id', $school_id)
                    ->where('student_code', $data['student_code'])
                    ->first();
                if ($existing) {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Student code "' . $data['student_code'] . '" already exists.'];
                }
            }

            try {
                $this->resolveAcademicReferences($data, $school_id);
            } catch (\Exception $e) {
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            }

            $customValues = $this->extractCustomFieldValues($data, $school_id);
            foreach ($customFields as $field) {
                $header = $this->getCustomFieldHeader($field);
                $value = trim((string) ($customValues[(int) $field->id] ?? ''));

                if ((int) ($field->is_required ?? 0) === 1 && $value === '') {
                    $errors[] = ['row' => $rowNumber, 'message' => 'Custom field "' . $field->label . '" is required.'];
                }

                if ($value !== '' && (int) ($field->is_unique ?? 0) === 1) {
                    $entityId = $this->getStudentEntityId();
                    $uniqueKey = (int) $field->id . '|' . strtolower(trim($value));
                    if (isset($seenCustomValues[$uniqueKey])) {
                        $errors[] = ['row' => $rowNumber, 'message' => 'Custom field "' . $field->label . '" value is duplicated in the uploaded file.'];
                    }
                    $seenCustomValues[$uniqueKey] = true;

                    $exists = $entityId ? $this->ValueModel
                        ->where('school_id', $school_id)
                        ->where('entity_id', $entityId)
                        ->where('field_id', (int) $field->id)
                        ->where('value', $value)
                        ->first() : null;

                    if ($exists) {
                        $errors[] = ['row' => $rowNumber, 'message' => 'Custom field "' . $field->label . '" value "' . $value . '" already exists.'];
                    }
                }
            }
        }

        return $errors;
    }

    private function importSingleStudent(array $data, int $school_id, int $user_id, string $now): void
    {
        $academic = $this->resolveAcademicReferences($data, $school_id);
        $customValues = $this->extractCustomFieldValues($data, $school_id);
        $alias = $this->generateAlias($data['first_name'] ?? '', $data['middle_name'] ?? '', $data['last_name'] ?? '');
        $token = $this->generateToken();

        $studentData = [
            'school_id'       => $school_id,
            'school_owner_uid' => $user_id,
            'student_code'    => $data['student_code'] ?? $this->generateStudentCode($school_id),
            'first_name'      => $data['first_name'] ?? '',
            'middle_name'     => $data['middle_name'] ?? null,
            'last_name'       => $data['last_name'] ?? null,
            'alias'           => $alias,
            'token'           => $token,
            'gender'          => $data['gender'] ?? null,
            'date_of_birth'   => $data['date_of_birth'] ?? null,
            'phone'           => $data['phone'] ?? null,
            'email'           => $data['email'] ?? null,
            'registration_no' => !empty($data['registration_no']) ? $data['registration_no'] : $this->generateRegistrationNo($school_id),
            'admission_date'  => $data['admission_date'] ?? date('Y-m-d'),
            'student_status'  => 'Active',
            'admission_source' => 'Bulk Import',
            'student_qr_code' => $this->generateQRCode($school_id),
            'status'          => 1,
            'created_at'      => $now,
            'created_by'      => $user_id,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        $this->StudentModel->insert($studentData);
        $student_id = (int) $this->StudentModel->insertID();

        // Create user account if enabled
        $student_account_enabled = $this->isStudentAccountEnabled($school_id);
        if ($student_account_enabled && !empty($data['email'])) {
            $login_name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            $userData = [
                'name'       => $login_name ?: $data['email'],
                'email'      => $data['email'],
                'phone'      => $data['phone'] ?? null,
                'password'   => password_hash('student123', PASSWORD_DEFAULT),
                'status'     => 1,
                'created_at' => $now,
                'created_by' => $user_id,
                'updated_at' => $now,
                'updated_by' => $user_id,
            ];

            $this->UserModel->insert($userData);
            $user_account_id = (int) $this->UserModel->insertID();

            $this->UserRoleModel->insert([
                'user_id'   => $user_account_id,
                'role_id'   => 4,
                'school_id' => $school_id,
            ]);

            $this->StudentModel->update($student_id, ['user_id' => $user_account_id]);
        }

        // Save enrollment
        $enrollmentData = [
            'school_id'       => $school_id,
            'school_owner_uid' => $user_id,
            'student_id'      => $student_id,
            'session_id'      => (int) $academic['session_id'],
            'class_id'        => (int) $academic['class_id'],
            'section_id'      => $academic['section_id'],
            'department_id'   => $academic['department_id'],
            'category_id'     => $academic['category_id'],
            'shift_id'        => $academic['shift_id'],
            'roll_no'         => $data['roll_no'] ?? null,
            'status'          => 1,
            'created_at'      => $now,
            'created_by'      => $user_id,
            'updated_at'      => $now,
            'updated_by'      => $user_id,
        ];

        $this->EnrollmentModel->insert($enrollmentData);
        $enrollment_id = (int) $this->EnrollmentModel->insertID();

        // Save guardian
        if (!empty($data['guardian_name'])) {
            $this->GuardianModel->insert([
                'school_id'     => $school_id,
                'student_id'    => $student_id,
                'relation_type' => $data['guardian_relation'] ?? 'Guardian',
                'name'          => $data['guardian_name'],
                'phone'         => $data['guardian_phone'] ?? null,
                'email'         => $data['guardian_email'] ?? null,
                'created_at'    => $now,
            ]);
        }

        $entityId = $this->getStudentEntityId();
        if ($entityId && !empty($customValues)) {
            foreach ($customValues as $fieldId => $value) {
                $this->ValueModel->saveFieldValue(
                    $school_id,
                    (int) $fieldId,
                    $entityId,
                    $student_id,
                    $value
                );
            }
        }
    }

    private function renderPreviewTable(array $headers, array $rows, array $errors = []): string
    {
        $errorMap = [];
        foreach ($errors as $err) {
            $errorMap[$err['row']][] = $err['message'];
        }

        $html = '<div class="table-responsive">';
        $html .= '<table class="table table-bordered table-striped table-sm" id="preview_table">';
        $html .= '<thead class="thead-dark"><tr><th>#</th>';
        foreach ($headers as $h) {
            $html .= '<th>' . esc($h) . '</th>';
        }
        $html .= '<th>Status</th></tr></thead><tbody>';

        foreach ($rows as $rowIndex => $row) {
            $rowNumber = $rowIndex + 2;
            $hasError  = isset($errorMap[$rowNumber]);
            $html .= '<tr' . ($hasError ? ' class="table-danger"' : '') . '>';
            $html .= '<td>' . $rowNumber . '</td>';

            foreach ($headers as $colIndex => $h) {
                $value = $row[$colIndex] ?? '';
                $html .= '<td>' . esc($value) . '</td>';
            }

            $html .= '<td>';
            if ($hasError) {
                $html .= '<span class="badge badge-danger" title="' . esc(implode('; ', $errorMap[$rowNumber])) . '">Error</span>';
            } else {
                $html .= '<span class="badge badge-success">Valid</span>';
            }
            $html .= '</td></tr>';
        }

        $html .= '</tbody></table></div>';
        return $html;
    }

    protected function jsonResponse(array $response)
    {
        return $this->response
            ->setHeader('X-CSRF-TOKEN', csrf_hash())
            ->setJSON($response);
    }

    protected function generateStudentCode(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_id_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'id_prefix', 'STU-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'id_digit', 8);
        $digit     = max(1, $digit);

        $last = $this->StudentModel
            ->select('student_code')
            ->where('school_id', $school_id)
            ->orderBy('id', 'DESC')
            ->first();

        $pattern = '/\d{' . $digit . '}$/';
        if ($last && preg_match($pattern, $last->student_code, $matches)) {
            $num = (int) $matches[0] + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, $digit, '0', STR_PAD_LEFT);
    }

    protected function generateRegistrationNo(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_reg_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'reg_prefix', 'REG-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'reg_digit', 8);
        $digit     = max(1, $digit);

        $last = $this->StudentModel
            ->select('registration_no')
            ->where('school_id', $school_id)
            ->orderBy('id', 'DESC')
            ->first();

        $pattern = '/\d{' . $digit . '}$/';
        if ($last && preg_match($pattern, $last->registration_no, $matches)) {
            $num = (int) $matches[0] + 1;
        } else {
            $num = 1;
        }

        return $prefix . str_pad($num, $digit, '0', STR_PAD_LEFT);
    }

    protected function generateQRCode(int $school_id): string
    {
        $usePrefix = $this->getSchoolParam($school_id, 'use_qr_prefix', 0);
        $prefix    = $usePrefix ? $this->getSchoolParam($school_id, 'qr_prefix', 'QR-') : '';
        $digit     = (int) $this->getSchoolParam($school_id, 'qr_digit', 8);
        $digit     = max(1, $digit);

        $random = strtoupper(bin2hex(random_bytes(4)));
        // Pad or truncate random part to match digit count
        if (strlen($random) > $digit) {
            $random = substr($random, 0, $digit);
        } else {
            $random = str_pad($random, $digit, '0', STR_PAD_LEFT);
        }

        return $prefix . $random;
    }

    protected function getSchoolParam(int $school_id, string $key, $default = '')
    {
        $school = $this->SchoolModel->find($school_id);
        if (!$school || empty($school->params)) {
            return $default;
        }
        $params = json_decode($school->params, true);
        return $params[$key] ?? $default;
    }

    protected function generateToken(): string
    {
        return bin2hex(random_bytes(16));
    }

    protected function generateAlias(string $firstName, string $middleName, string $lastName): string
    {
        $fullName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
        $alias = strtolower($fullName);
        $alias = preg_replace('/\s+/', '-', $alias);
        $alias = preg_replace('/[^a-z0-9-]/', '', $alias);
        $alias = preg_replace('/-+/', '-', $alias);
        $alias = trim($alias, '-');

        if (empty($alias)) {
            $alias = 'student-' . time();
        }

        return $alias;
    }
}