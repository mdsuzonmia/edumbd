<?php

namespace App\Controllers\SchoolOwner;

use App\Controllers\BaseController;
use App\Models\SchoolModel;
use App\Models\ModuleModel;
use App\Models\SchoolSettingModel;
use App\Models\SchoolModuleModel;

class Settings extends BaseController
{
    protected SchoolModel $SchoolModel;
    protected ModuleModel $ModuleModel;
    protected SchoolSettingModel $SchoolSettingModel;
    protected SchoolModuleModel $SchoolModuleModel;

    public function __construct()
    {
        $this->SchoolModel        = new SchoolModel();
        $this->ModuleModel        = new ModuleModel();
        $this->SchoolSettingModel = new SchoolSettingModel();
        $this->SchoolModuleModel  = new SchoolModuleModel();
    }

    /**
     * Resolve the school id for the current session (falls back to first owned school).
     */
    protected function currentSchoolId(): int
    {
        $schoolId = (int) session('school_id');

        if ($schoolId <= 0) {
            $owned = $this->SchoolModel->getSchoolIdsByOwner((int) session('user_id'));
            $schoolId = (int) ($owned[0] ?? 0);
        }

        return $schoolId;
    }

    /**
     * Merge enabled module slugs from the school params and the school_modules table.
     *
     * @return string[]
     */
    protected function getEnabledModuleSlugs(int $schoolId, ?object $school): array
    {
        $slugs = [];

        // From stored school params (backwards compatibility with school form)
        if ($school && !empty($school->params)) {
            $params = json_decode($school->params, true);
            if (is_array($params) && !empty($params['modules']) && is_array($params['modules'])) {
                $slugs = array_merge($slugs, $params['modules']);
            }
        }

        // From the school_modules relation table (authoritative)
        $slugs = array_merge($slugs, $this->SchoolModuleModel->getEnabledSlugs($schoolId));

        return array_values(array_unique(array_filter($slugs)));
    }

    public function index()
    {
        $check = check_subscription('school-owner/settings');
        if ($check) {
            return $check;
        }

        $schoolId = $this->currentSchoolId();
        $school   = $schoolId ? $this->SchoolModel->find($schoolId) : null;

        if (!$school) {
            return redirect()->to('school-owner/schools')->with('error', 'School not found.');
        }

        // --- Module System ---
        $publishedModules = $this->ModuleModel
            ->where('status', 1)
            ->orderBy('name', 'ASC')
            ->findAll();

        $enabledModuleSlugs = $this->getEnabledModuleSlugs($schoolId, $school);

        $data = [
            'school'               => $school,
            'school_id'            => $schoolId,
            'settings'             => $this->SchoolSettingModel->getByGroup($schoolId, 'school'),
            'modules'              => $publishedModules,
            'enabled_module_slugs' => $enabledModuleSlugs,
            'countries'            => get_country_list(),
            'timezone_list'        => timezone_identifiers_list(),
        ];

        $header_data['page_title'] = 'School Settings';
        $header_data['body_class'] = 'nav-md';
        $header_data['admin_area'] = 'yes';
        $footer_data['admin_area'] = 'yes';

        return view('header', $header_data)
            . view('school_owner/settings/index', $data)
            . view('footer', $footer_data);
    }

    public function update()
    {
        $check = check_subscription('school-owner/settings');
        if ($check) {
            return $check;
        }

        $schoolId = $this->currentSchoolId();
        if (!$schoolId) {
            return redirect()->back()->with('error', 'School not found.');
        }

        $post = $this->request->getPost();

        // Update the school profile record
        $this->SchoolModel->update($schoolId, [
            'name'          => $post['name'] ?? null,
            'email'         => $post['email'] ?? null,
            'phone_code'    => $post['phone_code'] ?? null,
            'phone'         => $post['phone'] ?? null,
            'address'       => $post['address'] ?? null,
            'custom_domain' => $post['custom_domain'] ?? null,
            'timezone'      => $post['timezone'] ?? null,
            'country'       => $post['country'] ?? null,
            'updated_at'    => date('Y-m-d H:i:s'),
            'updated_by'    => (int) session('user_id'),
        ]);

        // Save extended school settings (school_settings table)
        if (!empty($post['setting']) && is_array($post['setting'])) {
            foreach ($post['setting'] as $key => $value) {
                $this->SchoolSettingModel->saveSetting($schoolId, 'school', $key, $value);
            }
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }

    public function saveModules()
    {
        $check = check_subscription('school-owner/settings');
        if ($check) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['status' => false, 'message' => $check['message'] ?? 'Subscription expired.']);
            }
            return $check;
        }

        $schoolId = $this->currentSchoolId();
        if (!$schoolId) {
            $message = 'School not found.';
            return $this->request->isAJAX()
                ? $this->response->setJSON(['status' => false, 'message' => $message])
                : redirect()->back()->with('error', $message);
        }

        $moduleIds = (array) $this->request->getPost('modules');

        // Only persist ids belonging to published modules
        $allowedIds = $this->ModuleModel->where('status', 1)->findColumn('id');
        $allowedIds = array_map('intval', $allowedIds ?: []);
        $moduleIds  = array_values(array_intersect(array_map('intval', $moduleIds), $allowedIds));

        // Persist to the school_modules relation table
        $saved = $this->SchoolModuleModel->syncModules($schoolId, $moduleIds);

        // Keep school params in sync (slugs) for backwards compatibility
        $slugs = [];
        if (!empty($moduleIds)) {
            $slugs = $this->ModuleModel->whereIn('id', $moduleIds)->findColumn('slug');
            $slugs = $slugs ?: [];
        }

        $school = $this->SchoolModel->find($schoolId);
        $params = [];
        if ($school && !empty($school->params)) {
            $params = json_decode($school->params, true);
            if (!is_array($params)) {
                $params = [];
            }
        }
        $params['modules'] = $slugs;

        $this->SchoolModel->update($schoolId, ['params' => json_encode($params)]);

        $message = $saved ? 'Modules updated successfully.' : 'Failed to update modules.';

        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => (bool) $saved, 'message' => $message]);
        }

        return redirect()->to('school-owner/settings')->with($saved ? 'success' : 'error', $message);
    }
}
