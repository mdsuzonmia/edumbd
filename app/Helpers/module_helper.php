<?php
if (!function_exists('module_view')) {
    function module_view($module, $view)
    {
        include APPPATH . 'Modules/' . $module . '/Views/' . $view . '.php';
        
    }
}

if (!function_exists('module_menu')) {
    function module_menu($module)
    {
        $role = session()->get('role_id');
        $access          = $module->menu_access;
		$access_ids      = explode(",",$access);
        $output = '';
        if (in_array($role, $access_ids)) {
        $output .='<li>';
					$output .='<a href="'.base_url($module->slug).'">';
						$output .='<i class="fa '.$module->menu_icon.'"></i>';
						$output .='<span>'.$module->name.'</span>';
					$output .='</a>';
        $output .='</li>';
        }

        return $output;
    }
}


