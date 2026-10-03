<?php
$menuRows = getSidebarMenus();
$childrenByParent = $menuById = [];
foreach ($menuRows as $row) {
    $menuById[(int) $row['id']] = $row;
    $childrenByParent[(int) ($row['parent_id'] ?? 0)][] = $row;
}

$groupTitles = [
    'school-academics'=>['title'=>lang('OwnerDashboard.group_academics'),'icon'=>'bi bi-mortarboard'],
    'school-students'=>['title'=>lang('OwnerDashboard.group_students'),'icon'=>'bi bi-people'],
    'school-exams'=>['title'=>lang('OwnerDashboard.group_exams'),'icon'=>'bi bi-clipboard-data'],
    'school-admission'=>['title'=>lang('OwnerDashboard.group_admission'),'icon'=>'bi bi-person-plus'],
    'other'=>['title'=>lang('OwnerDashboard.group_other'),'icon'=>'bi bi-grid'],
];

$quickGroups = [];
foreach ($menuRows as $row) {
    $id = (int) $row['id']; $slug = (string) $row['slug'];
    $route = trim((string) ($row['route'] ?? ''), '/');
    if ($route === '' || $route === 'school-owner/dashboard' || $slug === 'school-subscriptions') continue;
    if (!empty($childrenByParent[$id]) && $slug !== 'examination') continue;

    $root = $row;
    while (!empty($root['parent_id']) && isset($menuById[(int) $root['parent_id']])) $root = $menuById[(int) $root['parent_id']];
    $rootSlug = (string) ($root['slug'] ?? 'other');
    $groupKey = isset($groupTitles[$rootSlug]) ? $rootSlug : 'other';
    $quickGroups[$groupKey][] = [
        'title'=>localized_menu_title($row), 'route'=>$route,
        'icon'=>!empty($row['icon']) ? (string) $row['icon'] : 'bi bi-arrow-up-right-circle',
    ];
}
$quickGroups['other'][] = ['title'=>lang('OwnerDashboard.school_settings'),'route'=>'school-owner/settings','icon'=>'bi bi-gear'];
$quickGroups['other'][] = ['title'=>lang('OwnerDashboard.billing'),'route'=>'school-owner/billing','icon'=>'bi bi-receipt'];
$quickGroups['other'][] = ['title'=>lang('OwnerDashboard.my_profile'),'route'=>'school-owner/profile','icon'=>'bi bi-person-circle'];
?>
<?= get_system_message(); ?>
<style>
.owner-home{font-family:"Noto Sans Bengali","Hind Siliguri",sans-serif;width:100%;max-width:1600px;margin:auto}.owner-hero{background:linear-gradient(135deg,#194b92,#3978d1);color:#fff;border-radius:22px;padding:clamp(22px,4vw,38px)}.owner-hero h1{font-size:clamp(25px,3vw,38px)}
.primary-tasks{margin-top:24px}.task-card{display:block;height:100%;padding:25px;border-radius:20px;background:#fff;color:#24324a;text-decoration:none;box-shadow:0 9px 28px rgba(31,50,81,.09);border:2px solid transparent;transition:.18s}.task-card:hover{transform:translateY(-3px);color:#174b91;border-color:#b8cff1}.task-card.primary{background:#eef5ff;border-color:#3978d1}.task-icon{width:58px;height:58px;border-radius:16px;display:grid;place-items:center;background:#eaf0f8;color:#2457a6;font-size:26px;margin-bottom:18px}.task-card.primary .task-icon{background:#2457a6;color:#fff}.task-card h3{font-size:21px;font-weight:800}.task-arrow{font-weight:700;color:#2457a6}
.quick-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;margin:28px 0 18px}.quick-toolbar h2{color:#21314b;font-size:clamp(21px,2.3vw,29px);font-weight:800;margin:0}.quick-search{position:relative;width:min(100%,360px)}.quick-search i{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#71809a}.quick-search input{min-height:46px;padding-left:42px;border:1px solid #d6e0ed;border-radius:13px;box-shadow:0 5px 18px rgba(35,58,91,.06)}
.quick-group{background:#fff;border:1px solid #e3e9f1;border-radius:18px;padding:20px;box-shadow:0 8px 26px rgba(30,52,82,.06)}.quick-group-title{display:flex;align-items:center;gap:10px;color:#253650;font-size:19px;font-weight:800;margin-bottom:16px}.quick-group-title i{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;color:#2457a6;background:#eaf2ff}
.quick-action{display:flex;align-items:center;gap:12px;height:100%;min-height:68px;padding:12px 14px;color:#293b57;background:#f8faff;border:1px solid #e1e8f2;border-radius:13px;text-decoration:none;font-size:15px;font-weight:700;transition:.18s}.quick-action>i{flex:0 0 38px;width:38px;height:38px;display:grid;place-items:center;border-radius:10px;color:#2160b5;background:#e8f1ff;font-size:18px}.quick-action:hover{color:#174b91;border-color:#9fbce7;box-shadow:0 7px 18px rgba(38,91,166,.12);transform:translateY(-2px)}.quick-action span{line-height:1.35}.quick-empty{display:none;padding:34px;color:#71809a;background:#fff;border-radius:16px;text-align:center}
@media(max-width:767px){.quick-toolbar{align-items:stretch;flex-direction:column}.quick-search{width:100%}.quick-group{padding:15px}.quick-action{min-height:62px;padding:10px}.owner-hero{border-radius:17px}.task-card{padding:21px}}
</style>
<main class="owner-home">
  <section class="owner-hero"><div class="small opacity-75 mb-2"><?= lang('OwnerDashboard.welcome') ?>, <?= esc((string) session('user_name')) ?></div><h1 class="fw-bold mb-2"><?= lang('OwnerDashboard.hero_title') ?></h1><p class="mb-0 opacity-75"><?= lang('OwnerDashboard.hero_description') ?></p></section>
  <section class="primary-tasks" aria-label="<?= esc(lang('OwnerDashboard.primary_tasks')) ?>">
    <div class="row g-3">
      <div class="col-12 col-md-6 col-xl-4"><a class="task-card primary" href="<?= site_url('examination/result-wizard') ?>"><div class="task-icon"><i class="fa fa-bar-chart"></i></div><h3><?= lang('OwnerDashboard.create_results') ?></h3><p class="text-muted"><?= lang('OwnerDashboard.create_results_help') ?></p><span class="task-arrow"><?= lang('OwnerDashboard.get_started') ?> →</span></a></div>
      <div class="col-12 col-md-6 col-xl-4"><a class="task-card" href="<?= site_url('examination/seat-plans/create') ?>"><div class="task-icon"><i class="fa fa-th-large"></i></div><h3><?= lang('OwnerDashboard.create_seat_plan') ?></h3><p class="text-muted"><?= lang('OwnerDashboard.create_seat_plan_help') ?></p><span class="task-arrow"><?= lang('OwnerDashboard.get_started') ?> →</span></a></div>
      <div class="col-12 col-md-6 col-xl-4"><a class="task-card" href="<?= site_url('examination/admit-cards/create') ?>"><div class="task-icon"><i class="fa fa-id-card"></i></div><h3><?= lang('OwnerDashboard.create_admit_cards') ?></h3><p class="text-muted"><?= lang('OwnerDashboard.create_admit_cards_help') ?></p><span class="task-arrow"><?= lang('OwnerDashboard.get_started') ?> →</span></a></div>
    </div>
  </section>
  <div class="quick-toolbar"><div><h2><?= lang('OwnerDashboard.quick_actions') ?></h2><div class="text-muted mt-1"><?= lang('OwnerDashboard.quick_actions_help') ?></div></div><label class="quick-search" aria-label="<?= esc(lang('OwnerDashboard.search_actions')) ?>"><i class="bi bi-search"></i><input type="search" id="quickActionSearch" class="form-control" placeholder="<?= esc(lang('OwnerDashboard.search_placeholder')) ?>" autocomplete="off"></label></div>
  <div id="quickActionGroups">
  <?php foreach ($groupTitles as $groupKey=>$groupMeta): if (empty($quickGroups[$groupKey])) continue; ?>
    <section class="quick-group mb-3" data-quick-group><h3 class="quick-group-title"><i class="<?= esc($groupMeta['icon']) ?>"></i><?= esc($groupMeta['title']) ?></h3><div class="row g-2">
    <?php foreach ($quickGroups[$groupKey] as $action): ?>
      <div class="col-12 col-sm-6 col-lg-4 col-xl-3" data-quick-action data-search-text="<?= esc(mb_strtolower($action['title'])) ?>"><a class="quick-action" href="<?= site_url($action['route']) ?>"><i class="<?= esc($action['icon']) ?>"></i><span><?= esc($action['title']) ?></span></a></div>
    <?php endforeach; ?>
    </div></section>
  <?php endforeach; ?>
  </div>
  <div class="quick-empty" id="quickActionEmpty"><i class="bi bi-search fs-3 d-block mb-2"></i><?= lang('OwnerDashboard.no_actions') ?></div>
</main>
<script>
document.addEventListener('DOMContentLoaded',function(){var search=document.getElementById('quickActionSearch'),empty=document.getElementById('quickActionEmpty');if(!search)return;search.addEventListener('input',function(){var query=this.value.toLocaleLowerCase('<?= esc(service('language')->getLocale(), 'js') ?>').trim(),visible=0;document.querySelectorAll('[data-quick-group]').forEach(function(group){var count=0;group.querySelectorAll('[data-quick-action]').forEach(function(action){var matches=!query||action.dataset.searchText.indexOf(query)!==-1;action.style.display=matches?'':'none';if(matches)count++});group.style.display=count?'':'none';visible+=count});empty.style.display=visible?'none':'block'})});
</script>
