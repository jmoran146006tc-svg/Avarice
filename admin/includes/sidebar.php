<?php
// =============================================================================
//  AVARITIA CULTURAL HOLDINGS, LTD.
//  admin/includes/sidebar.php — Admin Navigation Sidebar
// =============================================================================
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir  = basename(dirname($_SERVER['PHP_SELF']));

function adminNavLink(string $href, string $label, string $current): string {
    $file    = basename($href);
    $active  = ($file === $current) ? 'active' : '';
    $siteUrl = SITE_URL;
    return "<a href=\"{$siteUrl}/admin/{$href}\" class=\"{$active}\">{$label}</a>";
}
?>
<aside class="admin-sidebar">

  <div class="admin-sidebar__section">
    <div class="admin-sidebar__heading">Overview</div>
    <nav class="admin-nav">
      <?= adminNavLink('index.php', 'Dashboard',    $currentPage) ?>
    </nav>
  </div>

  <div class="admin-sidebar__section">
    <div class="admin-sidebar__heading">Catalogue</div>
    <nav class="admin-nav">
      <?= adminNavLink('artifacts.php', 'Artifacts',   $currentPage) ?>
      <?= adminNavLink('auctions.php',  'Auctions',    $currentPage) ?>
      <?= adminNavLink('flagged.php',      'Flagged Items', $currentPage) ?>
    <?= adminNavLink('add-artifact.php', '+ Add Artifact', $currentPage) ?>

    </nav>
  </div>


  <div class="admin-sidebar__section">
    <div class="admin-sidebar__heading">People</div>
    <nav class="admin-nav">
      <?= adminNavLink('users.php',    'Users',   $currentPage) ?>
    </nav>
  </div>


  <div class="admin-sidebar__section">
    <div class="admin-sidebar__heading">System</div>
    <nav class="admin-nav">
      <?= adminNavLink('audit.php',   'Audit Log',  $currentPage) ?>
    </nav>
  </div>

  <div style="padding: 1.5rem 1.75rem; margin-top: auto;">
    <a href="<?= SITE_URL ?>/index.php" style="font-size:0.82rem;color:var(--text-dim);">
      ← Back to Site
    </a>
  </div>

</aside>
