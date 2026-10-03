<?php
$app_name = esc(setting('application', 'app_name', 'Edum'));
$app_logo = esc(setting('application', 'logo', 'default_logo.png'));
$active   = isset($active) ? $active : '';
?>
<style>
:root { --primary-color: #416499; --secondary-color: #416499; --dark-color: #111827; }
body { background: #ffffff; font-family: 'Inter', sans-serif; color: #1f2937; }
.top-nav { position: fixed; top:0; left:0; right:0; z-index:1000; background: rgba(255,255,255,0.95);
  box-shadow:0 2px 20px rgba(0,0,0,0.05); }
.nav-container { max-width:1200px; margin:0 auto; padding:12px 24px; display:flex; align-items:center;
  justify-content:space-between; }
.nav-brand { display:flex; align-items:center; text-decoration:none; }
.nav-brand img { max-height:42px; width:auto; }
.nav-brand-text { font-size:1.2rem; font-weight:700; color:var(--dark-color); margin-left:10px; }
.nav-menu { display:flex; align-items:center; gap:28px; list-style:none; margin:0; padding:0; }
.nav-menu a { color:#4b5563; text-decoration:none; font-weight:500; font-size:15px; transition:color .3s; }
.nav-menu a:hover { color:var(--primary-color); }
.nav-menu a.active { color:var(--primary-color); font-weight:600; }
.nav-btn { padding:8px 20px; border-radius:10px; font-weight:600; font-size:14px; text-decoration:none; }
.nav-btn-secondary { background:transparent; color:var(--primary-color); border:2px solid var(--primary-color); }
.nav-btn-secondary:hover { background:var(--primary-color); color:white; }
.nav-btn-primary { background:var(--primary-color); color:white; border:none; margin-left:8px; }
.nav-btn-primary:hover { background:#365a8a; }
.docs-page { max-width:1200px; margin:0 auto; padding:110px 24px 60px; }

@media (max-width:768px){ .nav-menu{ display:none; } }

.docs-layout { display:flex; gap:40px; align-items:flex-start; }
.docs-sidebar { width:260px; flex:0 0 260px; position:sticky; top:110px; }
.docs-sidebar .sidebar-title { font-size:13px; text-transform:uppercase; letter-spacing:.05em;
  color:#6b7280; margin-bottom:12px; font-weight:700; }
.docs-sidebar ul { list-style:none; margin:0; padding:0; }
.docs-sidebar li { margin-bottom:6px; }
.docs-sidebar a { display:block; color:#374151; text-decoration:none; font-size:14px;
  padding:7px 12px; border-radius:8px; }
.docs-sidebar a:hover { background:#eef2ff; color:var(--primary-color); }
.docs-sidebar a.active { background:var(--primary-color); color:white; font-weight:600; }
.docs-content { flex:1 1 auto; min-width:0; }
.docs-content > .doc-card { background:white; border:1px solid #e5e7eb; border-radius:12px;
  padding:36px 40px; box-shadow:0 2px 12px rgba(0,0,0,0.04); line-height:1.7; }
.docs-content .doc-title { font-size:1.75rem; font-weight:800; color:var(--dark-color);
  padding-bottom:12px; margin-bottom:20px; border-bottom:1px solid #eceef1; }

.doc-body h1,.doc-body h2,.doc-body h3,.doc-body h4,.doc-body h5,.doc-body h6 { color:var(--dark-color);
  font-weight:700; margin:26px 0 12px; }
.doc-body h1 { font-size:1.5rem; }
.doc-body h2 { font-size:1.3rem; border-bottom:1px solid #eceef1; padding-bottom:6px; }
.doc-body h3 { font-size:1.1rem; }
.doc-body p { margin:12px 0; }
.doc-body a { color:var(--primary-color); }
.doc-body code { background:#f3f4f6; color:#be185d; padding:2px 6px; border-radius:5px; font-size:.9em; }
.doc-body pre { background:#111827; color:#e5e7eb; padding:16px 18px; border-radius:10px;
  overflow-x:auto; margin:16px 0; }
.doc-body pre code { background:transparent; color:inherit; padding:0; }
.doc-body blockquote { border-left:4px solid var(--primary-color); background:#f5f8fc;
  margin:16px 0; padding:12px 18px; border-radius:0 8px 8px 0; color:#374151; }
.doc-body blockquote p { margin:6px 0; }
.doc-body ul,.doc-body ol { margin:12px 0 12px 24px; padding:0; }
.doc-body li { margin:5px 0; }
.doc-body table { width:100%; border-collapse:collapse; margin:18px 0; font-size:.93rem;
  display:block; overflow-x:auto; }
.doc-body th,.doc-body td { border:1px solid #e5e7eb; padding:9px 12px; text-align:left; vertical-align:top; }
.doc-body th { background:#f7f8fa; font-weight:700; color:#111827; }
.doc-body tr:nth-child(even) td { background:#fafbfc; }
.doc-body hr { border:0; border-top:1px solid #e5e7eb; margin:28px 0; }
.doc-body strong { color:#111827; }
</style>

<nav class="top-nav">
  <div class="nav-container">
    <a href="<?= base_url('/'); ?>" class="nav-brand">
      <img src="<?= base_url('public/uploads/settings/' . $app_logo); ?>" alt="<?= $app_name; ?>"
           onerror="this.style.display='none'">
      <span class="nav-brand-text"><?= $app_name; ?></span>
    </a>
    <ul class="nav-menu">
      <li><a href="<?= base_url('/'); ?>">Home</a></li>
      <li><a href="<?= base_url('pricing'); ?>">Pricing</a></li>
      <li><a href="<?= base_url('docs'); ?>" class="active">Docs</a></li>
    </ul>
    <div>
      <a href="<?= base_url('login'); ?>" class="nav-btn nav-btn-secondary">Sign In</a>
      <a href="<?= base_url('registration'); ?>" class="nav-btn nav-btn-primary">Get Started</a>
    </div>
  </div>
</nav>

<div class="docs-page">
  <div class="docs-layout">
    <aside class="docs-sidebar">
      <div class="sidebar-title">User Guides</div>
      <ul>
        <li><a href="<?= base_url('docs'); ?>">📖 Index</a></li>
        <?php foreach ($docs as $doc): ?>
          <li>
            <a href="<?= base_url('docs/' . esc($doc['slug'])); ?>"
               class="<?= ($active === $doc['slug']) ? 'active' : ''; ?>">
              <?= esc($doc['title']); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </aside>

    <div class="docs-content">
      <div class="doc-card">
        <div class="doc-title"><?= esc($doc_title); ?></div>
        <div class="doc-body" id="docBody">
          <?= $content; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<footer style="text-align:center; padding:28px; color:#6b7280; font-size:14px;">
  &copy; <?= date('Y'); ?> <?= $app_name; ?> Documentation
</footer>