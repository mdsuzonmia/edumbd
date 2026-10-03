<?php
use App\Models\ModuleModel;
use Config\MyConstants;



$menus = buildMenuTree(getSidebarMenus());
$roleSlug = (string) session('role');
if ($roleSlug === 'school-owner') {
    $hideLegacyBilling = static function (array $items) use (&$hideLegacyBilling): array {
        $filtered = [];
        foreach ($items as $item) {
            $route = trim((string) ($item['route'] ?? ''), '/');
            if (preg_match('#^school-owner/(plans|subscriptions|payments)(/|$)#', $route)) continue;
            if (!empty($item['children'])) $item['children'] = $hideLegacyBilling($item['children']);
            $filtered[] = $item;
        }
        return $filtered;
    };
    $menus = $hideLegacyBilling($menus);
    $menus[] = ['title'=>'বিল ও পেমেন্ট','slug'=>'service-billing','route'=>'school-owner/billing','icon'=>'bi bi-receipt','children'=>[]];
}
if ($roleSlug === 'super-admin') {
    $menus[] = ['title'=>'Service Approvals','slug'=>'service-approvals','route'=>'saas-admin/service-billing/approvals','icon'=>'bi bi-check2-square','children'=>[]];
}
$app_logo = setting('application', 'logo', 'default_logo.png');
    

?>
<div class="sidebar" id="appSidebar">
    <div class="mb-4 text-center">
        <img src="<?= base_url('public/uploads/settings/' .$app_logo); ?>"  class="app-logo" alt="Logo" />
    </div>

<style>
    .sidebar-menu .submenu {
        max-height: 0;
        overflow: hidden;
        padding-left: 0;
        list-style: none;
        transition: max-height 0.3s ease-in-out, opacity 0.3s ease-in-out;
        opacity: 0;
    }
    
    .sidebar-menu .has-submenu.active > .submenu {
        max-height: 1000px;
        opacity: 1;
    }
    
    .sidebar-menu .has-submenu.active > a .chevron {
        transform: rotate(180deg);
        transition: transform 0.3s ease-in-out;
    }
    
    .sidebar-menu .nav-item .nav-link {
        cursor: pointer;
    }
    
    /* Indentation for nested levels */
    .sidebar-menu .submenu .nav-item .nav-link span {
        padding-left: 10px;
    }
    
    .sidebar-menu .submenu .submenu .nav-item .nav-link span {
        padding-left: 20px;
    }
    
    .sidebar-menu .submenu .submenu .submenu .nav-item .nav-link span {
        padding-left: 30px;
    }
</style>

<ul class="sidebar-menu nav nav-pills flex-column mb-4">
    <?php 
    function render_sidebar_menu($menus, $level = 0) {
        foreach ($menus as $menu): 
            // Get menu URL and active class
            $menu_slug = !empty($menu['route']) ? $menu['route'] : ltrim(menu_url($menu['slug']), '/');
            $active_class = menu_active($menu_slug);
            $has_children = !empty($menu['children']);
            $indent = $level > 0 ? str_repeat('&nbsp;&nbsp;', $level) : '';
            
            // Check if any child menu is active
            $has_active_child = false;
            if ($has_children) {
                $has_active_child = check_if_child_active($menu['children']);
            }
            
            // Build LI class
            $li_classes = ['nav-item'];
            if ($has_children) {
                $li_classes[] = 'has-submenu';
            }
            if ($active_class || $has_active_child) {
                $li_classes[] = 'active';
            }
            ?>
            
            <li class="<?= implode(' ', $li_classes) ?>">
                <a class="nav-link <?= $active_class ?> d-flex justify-content-between align-items-center" href="<?= $has_children ? 'javascript:void(0);' : site_url($menu['route']) ?>" <?= $has_children ? 'data-toggle="submenu"' : '' ?>>
                    <span><?= $indent ?><i class="<?= esc($menu['icon']) ?>"></i> <?= esc(localized_menu_title($menu)) ?></span>
                    <?php if ($has_children): ?>
                        <i class="bi bi-chevron-down small chevron"></i>
                    <?php endif; ?>
                </a>

                <?php if ($has_children): ?>
                    <ul class="submenu">
                        <?php render_sidebar_menu($menu['children'], $level + 1); ?>
                    </ul>
                <?php endif; ?>
            </li>
        <?php endforeach;
    }
    
    // Helper function to check if any child menu is active
    function check_if_child_active($children) {
        foreach ($children as $child) {
            $child_slug = !empty($child['route']) ? $child['route'] : ltrim(menu_url($child['slug']), '/');
            if (menu_active($child_slug)) {
                return true;
            }
            if (!empty($child['children']) && check_if_child_active($child['children'])) {
                return true;
            }
        }
        return false;
    }
    
    render_sidebar_menu($menus);
    ?>
</ul>

<?php 
          // Get role_id and show role name
          $role_slug = session()->get('role');
          $role_name = get_item('name', 'roles', 'slug', $role_slug);
          ?>

          <div class="mt-auto">
            <hr class="border-light">
            <div class="small text-white-50"><?= lang('Common.logged_in_as') ?> <strong><?= esc($role_name) ?></strong></div>
            <div class="mt-2 d-flex gap-2">
              <button class="btn btn-sm btn-outline-light w-100 logout-btn" onclick="doLogout()"><?= lang('Auth.btn_logout') ?></button>
            </div>

            <hr class="border-light mt-4">
            <footer>
              <div class="pull-right">
              <?= MyConstants::SITE_NAME ?>
              </div>
              <div class="clearfix"></div>
            </footer>
          </div>
        </div>

<script type="text/javascript">
    jQuery(document).ready(function($) {
        
        // Sidebar submenu toggle - supports nested submenus using CSS classes
        $('.sidebar-menu').on('click', function(e) {
            // Check if clicked element is or is inside a submenu toggle link
            var toggleLink = $(e.target).closest('a[data-toggle="submenu"]');
            
            if (toggleLink.length) {
                e.preventDefault();
                
                var parentLi = toggleLink.parent();
                var submenu = parentLi.children('.submenu');
                
                if (submenu.length) {
                    var isActive = parentLi.hasClass('active');
                    
                    if (isActive) {
                        // Collapse current
                        parentLi.removeClass('active');
                    } else {
                        // Expand current - remove active from siblings first
                        var siblings = parentLi.siblings('.has-submenu');
                        siblings.removeClass('active');
                        
                        // Expand current
                        parentLi.addClass('active');
                    }
                }
                
                return false;
            }
        });
        
        // Auto-expand submenu and activate parent links when a child menu is active
        $('.sidebar-menu .nav-link.active').each(function() {
            var $parent = $(this).parent();
            while ($parent.length && $parent.hasClass('has-submenu')) {
                $parent.addClass('active');
                
                // Also add active class to the parent link
                var parentLink = $parent.children('a.nav-link');
                if (parentLink.length && !parentLink.hasClass('active')) {
                    parentLink.addClass('active');
                }
                
                $parent = $parent.parent().parent();
            }
        });
        
    });

    // Logout function
    function doLogout(){
      if(confirm('<?= esc(lang('Common.logout_confirmation'), 'js') ?>')){
        window.location.href = "<?= site_url('logout') ?>";
      }
    }

    
</script>
