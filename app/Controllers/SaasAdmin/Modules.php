<?php

namespace App\Controllers\SaasAdmin;

use App\Controllers\BaseController;
use App\Models\ModuleModel;
use ZipArchive;

class Modules extends BaseController
{
    protected ModuleModel $moduleModel;

    public function __construct()
    {
        $this->moduleModel = new ModuleModel();
    }

    public function index()
    {
        $header_data = [
            'page_title' => lang('System.page_title_modules'),
            'body_class' => 'nav-md',
            'admin_area' => 'yes',
        ];

        $footer_data['admin_area'] = 'yes';

        $data = [
            'items' => $this->getModules(),
        ];

        return view('header', $header_data)
            . view('saas_admin/modules/list', $data)
            . view('footer', $footer_data);
    }

    public function install()
    {
        $slug = trim((string) $this->request->getPost('slug'));

        if ($slug !== '') {
            return $this->installFromExistingDirectory($slug);
        }

        $file = $this->request->getFile('module_file');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', lang('System.no_file_selected'));
        }

        if (strtolower($file->getClientExtension()) !== 'zip') {
            return redirect()->back()->with('error', lang('System.invalid_file_selected'));
        }

        $uploadPath = WRITEPATH . 'uploads/modules';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0775, true);
        }

        $zipName = $file->getRandomName();
        $file->move($uploadPath, $zipName);
        $zipPath = $uploadPath . DIRECTORY_SEPARATOR . $zipName;

        try {
            $moduleData = $this->extractModuleZip($zipPath);
            $this->saveModule($moduleData);
        } catch (\Throwable $e) {
            @unlink($zipPath);
            return redirect()->back()->with('error', $e->getMessage());
        }

        @unlink($zipPath);

        return redirect()->to('saas-admin/modules')->with('success', lang('System.module_installed'));
    }

    public function uninstall()
    {
        $id = (int) $this->request->getPost('module_id');

        if (!$id) {
            return redirect()->back()->with('error', lang('System.module_id_missing'));
        }

        if ($this->moduleModel->update($id, $this->filterModuleColumns(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]))) {
            return redirect()->to('saas-admin/modules')->with('success', lang('System.module_uninstalled'));
        }

        return redirect()->back()->with('error', lang('System.error_uninstall_module'));
    }

    public function changeStatus()
    {
        $id = (int) $this->request->getPost('module_id');
        $status = (int) $this->request->getPost('status');

        if (!$id) {
            return redirect()->back()->with('error', lang('System.module_id_missing'));
        }

        if (!in_array($status, [0, 1], true)) {
            return redirect()->back()->with('error', 'Invalid module status.');
        }

        $moduleData = $this->filterModuleColumns([
            'status' => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        if ($this->moduleModel->update($id, $moduleData)) {
            $message = $status === 1 ? 'Module activated successfully.' : 'Module deactivated successfully.';
            return redirect()->to('saas-admin/modules')->with('success', $message);
        }

        return redirect()->back()->with('error', 'Module status could not be updated.');
    }

    protected function installFromExistingDirectory(string $slug)
    {
        $slug = basename($slug);
        $moduleJson = APPPATH . 'Modules/' . $slug . '/module.json';

        if (!is_file($moduleJson)) {
            return redirect()->back()->with('error', 'Module manifest not found.');
        }

        $metadata = $this->readModuleJson($moduleJson);
        $this->saveModule($metadata);

        return redirect()->to('saas-admin/modules')->with('success', lang('System.module_installed'));
    }

    protected function getModules(): array
    {
        $dbModules = [];
        foreach ($this->moduleModel->orderBy('id', 'DESC')->findAll() as $module) {
            $dbModules[$module->slug] = $module;
        }

        foreach ($this->scanModuleDirectories() as $slug => $metadata) {
            if (isset($dbModules[$slug])) {
                $dbModules[$slug]->is_available = true;
                continue;
            }

            $dbModules[$slug] = (object) [
                'id' => null,
                'name' => $metadata['name'] ?? $slug,
                'slug' => $slug,
                'description' => $metadata['description'] ?? '',
                'version' => $metadata['version'] ?? '',
                'author' => $metadata['author'] ?? '',
                'status' => 0,
                'is_available' => true,
            ];
        }

        return array_values($dbModules);
    }

    protected function scanModuleDirectories(): array
    {
        $modules = [];
        $modulePath = APPPATH . 'Modules';

        if (!is_dir($modulePath)) {
            return $modules;
        }

        foreach (scandir($modulePath) ?: [] as $directory) {
            if ($directory === '.' || $directory === '..') {
                continue;
            }

            $manifest = $modulePath . DIRECTORY_SEPARATOR . $directory . DIRECTORY_SEPARATOR . 'module.json';
            if (is_dir(dirname($manifest)) && is_file($manifest)) {
                $metadata = $this->readModuleJson($manifest);
                $modules[$metadata['slug'] ?? $directory] = $metadata;
            }
        }

        return $modules;
    }

    protected function extractModuleZip(string $zipPath): array
    {
        if (!class_exists(ZipArchive::class)) {
            throw new \RuntimeException('PHP Zip extension is not enabled.');
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            throw new \RuntimeException(lang('System.invalid_file_selected'));
        }

        $manifestIndex = $this->findManifestIndex($zip);
        if ($manifestIndex === false) {
            $zip->close();
            throw new \RuntimeException('module.json not found in uploaded zip.');
        }

        $manifestPath = str_replace('\\', '/', $zip->getNameIndex($manifestIndex));
        $sourceRoot = dirname($manifestPath);
        $sourceRoot = $sourceRoot === '.' ? '' : trim($sourceRoot, '/') . '/';
        $metadata = json_decode($zip->getFromIndex($manifestIndex), true);

        if (!is_array($metadata)) {
            $zip->close();
            throw new \RuntimeException('Invalid module.json file.');
        }

        $slug = $this->cleanSlug($metadata['slug'] ?? pathinfo($manifestPath, PATHINFO_DIRNAME));
        if ($slug === '') {
            $zip->close();
            throw new \RuntimeException('Module slug is missing.');
        }

        $targetPath = APPPATH . 'Modules' . DIRECTORY_SEPARATOR . $slug;
        if (!is_dir($targetPath)) {
            mkdir($targetPath, 0775, true);
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', $zip->getNameIndex($i));
            if ($sourceRoot !== '' && !str_starts_with($entryName, $sourceRoot)) {
                continue;
            }

            $relativePath = $sourceRoot === '' ? $entryName : substr($entryName, strlen($sourceRoot));
            $relativePath = ltrim($relativePath, '/');

            if ($relativePath === '' || str_contains($relativePath, '../') || str_starts_with($relativePath, '/')) {
                continue;
            }

            $destination = $targetPath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

            if (str_ends_with($entryName, '/')) {
                if (!is_dir($destination)) {
                    mkdir($destination, 0775, true);
                }
                continue;
            }

            $destinationDirectory = dirname($destination);
            if (!is_dir($destinationDirectory)) {
                mkdir($destinationDirectory, 0775, true);
            }

            copy('zip://' . $zipPath . '#' . $entryName, $destination);
        }

        $zip->close();

        $metadata['slug'] = $slug;
        return $metadata;
    }

    protected function findManifestIndex(ZipArchive $zip): int|false
    {
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = str_replace('\\', '/', $zip->getNameIndex($i));
            if (basename($entryName) === 'module.json') {
                return $i;
            }
        }

        return false;
    }

    protected function readModuleJson(string $path): array
    {
        $metadata = json_decode((string) file_get_contents($path), true);
        if (!is_array($metadata)) {
            return [];
        }

        $metadata['slug'] = $this->cleanSlug($metadata['slug'] ?? basename(dirname($path)));
        return $metadata;
    }

    protected function saveModule(array $metadata): void
    {
        $slug = $this->cleanSlug($metadata['slug'] ?? '');
        if ($slug === '') {
            throw new \RuntimeException('Module slug is missing.');
        }

        $moduleData = [
            'name' => $metadata['name'] ?? ucfirst($slug),
            'slug' => $slug,
            'description' => $metadata['description'] ?? null,
            'author' => $metadata['author'] ?? null,
            'version' => $metadata['version'] ?? '1.0',
            'status' => (int) ($metadata['status'] ?? 1),
            'sidebar_menu' => (int) ($metadata['sidebar_menu'] ?? 0),
            'menu_access' => $metadata['menu_access'] ?? null,
            'menu_icon' => $metadata['menu_icon'] ?? null,
            'super_admin_dashboard' => (int) ($metadata['super_admin_dashboard'] ?? 0),
            'admin_dashboard' => (int) ($metadata['admin_dashboard'] ?? 0),
            'teacher_dashboard' => (int) ($metadata['teacher_dashboard'] ?? 0),
            'student_dashboard' => (int) ($metadata['student_dashboard'] ?? 0),
            'parent_dashboard' => (int) ($metadata['parent_dashboard'] ?? 0),
            'params' => isset($metadata['params']) ? json_encode($metadata['params']) : null,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $existingModule = $this->moduleModel->where('slug', $slug)->first();
        if ($existingModule) {
            $this->moduleModel->update($existingModule->id, $this->filterModuleColumns($moduleData));
            return;
        }

        $moduleData['created_at'] = date('Y-m-d H:i:s');
        $this->moduleModel->insert($this->filterModuleColumns($moduleData));
    }

    protected function filterModuleColumns(array $data): array
    {
        $fields = db_connect()->getFieldNames('modules');
        return array_intersect_key($data, array_flip($fields));
    }

    protected function cleanSlug(string $slug): string
    {
        $slug = trim($slug);
        return preg_match('/^[A-Za-z0-9_-]+$/', $slug) ? $slug : '';
    }
}
