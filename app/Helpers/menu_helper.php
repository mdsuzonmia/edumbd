<?php

use Config\Database;
use CodeIgniter\Router\Exceptions\RouterException;

if (!function_exists('getSidebarMenus')) {

    /**
     * Get Sidebar Menus for Logged-in User
     */
    function getSidebarMenus(): array
    {
        $db = Database::connect();

        $userId   = session('user_id');
        $schoolId = session('school_id');
        $role     = session('role');

        // Get role id from role slug
        $roleId = $db->table('roles')->where('slug', $role)->get()->getRow()->id ?? null;

        if (!$userId || !$role || !$roleId) {
            return [];
        }

        

        // School user menus (role + subscription + module based)
        return $db->table('menus m')
            ->select('m.*')
            ->join('menu_roles mr', 'mr.menu_id = m.id')
            //->join('user_roles ur', 'ur.role_id = mr.role_id')
           // ->join('plan_modules pm', 'pm.module_id = m.module_id')
            //->join('subscriptions s', 's.plan_id = pm.plan_id')
            //->where('ur.user_id', $userId)
            //->where('s.school_id', $schoolId)
            ->where('mr.role_id', $roleId)
            //->whereIn('s.status', [1, 2]) // trial or active
            ->where('m.status', 1)
            // Admission is temporarily disabled system-wide.
            ->notLike('m.route', 'school/admission', 'after')
            ->orderBy('m.parent_id ASC, m.menu_order ASC')
            ->get()
            ->getResultArray();
    }
}



if (!function_exists('buildMenuTree')) {

    function buildMenuTree(array $menus, int $parentId = 0, int $maxDepth = 3): array
    {
        $branch = [];
        $currentDepth = 0;

        // Calculate current depth based on parentId
        if ($parentId > 0) {
            foreach ($menus as $parentMenu) {
                if ((int)$parentMenu['id'] === $parentId) {
                    $currentDepth = 1;
                    // Check if parent has a parent (level 2)
                    if (!empty($parentMenu['parent_id']) && (int)$parentMenu['parent_id'] > 0) {
                        $currentDepth = 2;
                    }
                    break;
                }
            }
        }

        // Don't recurse beyond max depth
        if ($currentDepth >= $maxDepth) {
            return $branch;
        }

        foreach ($menus as $menu) {

            if ((int)$menu['parent_id'] === (int)$parentId) {

                $children = buildMenuTree($menus, (int)$menu['id'], $maxDepth);

                if (!empty($children)) {
                    $menu['children'] = $children;
                }

                if ($parentId === 0 && empty($menu['children']) && ($menu['route'] ?? '') === 'saas-admin/reports') {
                    $menu['children'] = getChildrenMenus((int) $menu['id']);
                }

                $branch[] = $menu;
            }
        }

        return $branch;
    }
}

if (! function_exists('localized_menu_title')) {
    /**
     * Resolve a database-backed menu title for the active locale.
     * Bengali titles remain editable in the database; English labels are keyed
     * by the stable menu ID so duplicate slugs and routes are not ambiguous.
     */
    function localized_menu_title(array $menu): string
    {
        $fallback = (string) ($menu['title'] ?? '');
        $locale = service('language')->getLocale();

        if ($locale === 'bn' && ! empty($menu['id'])) {
            return $fallback;
        }

        $key = ! empty($menu['id'])
            ? 'Menu.items.item_' . (int) $menu['id']
            : 'Menu.slugs.' . strtolower((string) ($menu['slug'] ?? ''));
        $translated = lang($key);

        return $translated === $key ? $fallback : (string) $translated;
    }
}

if (! function_exists('menu_active')) {
    function menu_active(string|array $slugs, string $class = 'active'): string
    {
        $request = service('request');
        $currentPath = trim($request->getUri()->getPath(), '/');

        if (is_array($slugs)) {
            foreach ($slugs as $slug) {
                if (menu_path_is_active((string) $slug, $currentPath)) {
                    return $class;
                }
            }

            return '';
        }

        return menu_path_is_active($slugs, $currentPath) ? $class : '';
    }
}

if (!function_exists('getChildrenMenus')) {
    function getChildrenMenus(int $parentId): array
    {
        // Get Parent item only
        $db = Database::connect();
        return $db->table('menus')
            ->where('parent_id', $parentId)
            ->where('status', 1)
            ->orderBy('menu_order ASC')
            ->get()
            ->getResultArray();
    }
}

if (! function_exists('menu_path_is_active')) {
    function menu_path_is_active(string $menuPath, string $currentPath): bool
    {
        $menuPath = trim($menuPath, '/');
        $currentPath = trim($currentPath, '/');

        $basePath = trim((string) parse_url(site_url('/'), PHP_URL_PATH), '/');

        if ($basePath !== '' && ($currentPath === $basePath || str_starts_with($currentPath, $basePath . '/'))) {
            $currentPath = trim(substr($currentPath, strlen($basePath)), '/');
        }

        if ($menuPath === '' || $currentPath === '') {
            return $menuPath === $currentPath;
        }

        return $currentPath === $menuPath || str_starts_with($currentPath, $menuPath . '/');
    }
}


if (! function_exists('menu_url')) {
    function menu_url(string $slug): string
    {
        try {
            return route_to($slug);
        } catch (RouterException $e) {
            return '#'; // fallback if route missing
        }
    }
}
