<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$base   = BASE_URL;
$unread = 0;

if (isset($_SESSION['user_id'])) {
    $uid    = $_SESSION['user_id'];
    $unread = getUnreadCount($conn, $uid, 'user');
} elseif (isset($_SESSION['agent_id'])) {
    $aid    = $_SESSION['agent_id'];
    $unread = getUnreadCount($conn, $aid, 'agent');
}

// Load current user data fresh every page load (so pic/name changes reflect immediately)
$current_name = '';
$current_pic  = '';
$role_label   = '';
$role_color   = 'var(--clr-accent)';

if (isset($_SESSION['user_id'])) {
    $row = $conn->query("SELECT name, profile_pic FROM users WHERE id=$uid")->fetch_assoc();
    $current_name = $row['name'] ?? '';
    $current_pic  = $row['profile_pic'] ?? '';
    $role_label   = 'Aspiring Entrepreneur';
    $role_color   = '#22c55e';
} elseif (isset($_SESSION['agent_id'])) {
    $row = $conn->query("SELECT name, profile_pic FROM agents WHERE id=$aid")->fetch_assoc();
    $current_name = $row['name'] ?? '';
    $current_pic  = $row['profile_pic'] ?? '';
    $role_label   = 'Entrepreneur / Agent';
    $role_color   = '#4f8ef7';
} elseif (isset($_SESSION['admin_id'])) {
    $row = $conn->query("SELECT name, profile_pic FROM admins WHERE id={$_SESSION['admin_id']}")->fetch_assoc();
    $current_name = $row['name'] ?? 'Admin';
    $current_pic  = $row['profile_pic'] ?? '';
    $role_label   = 'Administrator';
    $role_color   = '#f59e0b';
}

// Check if real pic exists on disk
$pic_path = dirname(__DIR__) . '/uploads/profiles/' . $current_pic;
$has_pic  = !empty($current_pic) && $current_pic !== 'default.png' && file_exists($pic_path);
$pic_url  = $has_pic ? $base . '/uploads/profiles/' . htmlspecialchars($current_pic) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? 'Entrepreneur Portal') ?> — Entrepreneur Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
<script>window.BASE_URL = '<?= $base ?>';</script>
</head>
<body>
<div class="app-shell">

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-text">
      <span>🚀</span>
      <div>
        <div style="font-size:.7rem;color:var(--txt-muted);font-weight:400;text-transform:uppercase;letter-spacing:.08em;">Portal</div>
        Entrepreneur
      </div>
    </div>
  </div>

  <!-- Sidebar user block -->
  <div class="sidebar-user">
    <?php if ($has_pic): ?>
      <img src="<?= $pic_url ?>" alt="<?= htmlspecialchars($current_name) ?>"
           style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid <?= $role_color ?>;flex-shrink:0">
    <?php else: ?>
      <?= initialsAvatar($current_name, 44) ?>
    <?php endif; ?>
    <div class="user-info" style="min-width:0">
      <div class="name" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars($current_name) ?></div>
      <div class="role" style="color:<?= $role_color ?>"><?= $role_label ?></div>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php if (isset($_SESSION['user_id'])): ?>
      <div class="nav-label">Main</div>
      <a href="<?= $base ?>/user/dashboard.php"   class="nav-item <?= $active_nav==='dashboard'  ?'active':'' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="<?= $base ?>/user/categories.php"  class="nav-item <?= $active_nav==='categories' ?'active':'' ?>"><span class="nav-icon">📂</span> Categories</a>
      <a href="<?= $base ?>/user/agents.php"     class="nav-item <?= $active_nav==='agents'    ?'active':'' ?>"><span class="nav-icon">👤</span> Find Agents</a>
      <a href="<?= $base ?>/user/resources.php"   class="nav-item <?= $active_nav==='resources'  ?'active':'' ?>"><span class="nav-icon">📚</span> Resources</a>
      <div class="nav-label">Community</div>
      <a href="<?= $base ?>/user/forum.php"       class="nav-item <?= $active_nav==='forum'      ?'active':'' ?>"><span class="nav-icon">💬</span> Forum</a>
      <a href="<?= $base ?>/user/messages.php"    class="nav-item <?= $active_nav==='messages'   ?'active':'' ?>">
        <span class="nav-icon">✉️</span> Messages
        <?php if ($unread > 0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?>
      </a>
      <div class="nav-label">Account</div>
      <a href="<?= $base ?>/user/profile.php"     class="nav-item <?= $active_nav==='profile'    ?'active':'' ?>"><span class="nav-icon">⚙️</span> My Profile</a>

    <?php elseif (isset($_SESSION['agent_id'])): ?>
      <div class="nav-label">Main</div>
      <a href="<?= $base ?>/agent/dashboard.php"      class="nav-item <?= $active_nav==='dashboard' ?'active':'' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="<?= $base ?>/agent/upload_content.php" class="nav-item <?= $active_nav==='upload'    ?'active':'' ?>"><span class="nav-icon">➕</span> Upload Content</a>
      <a href="<?= $base ?>/agent/my_content.php"     class="nav-item <?= $active_nav==='content'   ?'active':'' ?>"><span class="nav-icon">📁</span> My Content</a>
      <div class="nav-label">Community</div>
      <a href="<?= $base ?>/agent/forum.php"          class="nav-item <?= $active_nav==='forum'     ?'active':'' ?>"><span class="nav-icon">💬</span> Forum</a>
      <a href="<?= $base ?>/agent/messages.php"       class="nav-item <?= $active_nav==='messages'  ?'active':'' ?>">
        <span class="nav-icon">✉️</span> Messages
        <?php if ($unread > 0): ?><span class="nav-badge"><?= $unread ?></span><?php endif; ?>
      </a>
      <a href="<?= $base ?>/agent/view_feedback.php"  class="nav-item <?= $active_nav==='feedback'  ?'active':'' ?>"><span class="nav-icon">⭐</span> My Feedback</a>
      <div class="nav-label">Account</div>
      <a href="<?= $base ?>/agent/profile.php"        class="nav-item <?= $active_nav==='profile'   ?'active':'' ?>"><span class="nav-icon">⚙️</span> My Profile</a>

    <?php elseif (isset($_SESSION['admin_id'])): ?>
      <div class="nav-label">Management</div>
      <a href="<?= $base ?>/admin/dashboard.php"      class="nav-item <?= $active_nav==='dashboard' ?'active':'' ?>"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="<?= $base ?>/admin/agent_requests.php" class="nav-item <?= $active_nav==='requests'  ?'active':'' ?>"><span class="nav-icon">📋</span> Agent Requests</a>
      <a href="<?= $base ?>/admin/manage_users.php"   class="nav-item <?= $active_nav==='users'     ?'active':'' ?>"><span class="nav-icon">👥</span> Users & Agents</a>
      <a href="<?= $base ?>/admin/manage_content.php" class="nav-item <?= $active_nav==='content'   ?'active':'' ?>"><span class="nav-icon">📝</span> Content</a>
      <a href="<?= $base ?>/admin/give_feedback.php"  class="nav-item <?= $active_nav==='feedback'  ?'active':'' ?>"><span class="nav-icon">⭐</span> Give Feedback</a>
      <div class="nav-label">Account</div>
      <a href="<?= $base ?>/admin/profile.php"        class="nav-item <?= $active_nav==='profile'   ?'active':'' ?>"><span class="nav-icon">⚙️</span> My Profile</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <a href="<?= $base ?>/auth/logout.php" class="btn btn-secondary btn-block btn-sm">
      <span>🚪</span> Logout
    </a>
  </div>
</aside>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="main-content">
  <header class="topbar">
    <div class="flex-center gap-12">
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <h2 class="topbar-title"><?= htmlspecialchars($page_title ?? '') ?></h2>
    </div>
    <div class="topbar-actions">
      <?php if (isset($_SESSION['user_id']) || isset($_SESSION['agent_id'])): ?>
        <a href="<?= $base ?>/<?= isset($_SESSION['user_id']) ? 'user' : 'agent' ?>/messages.php"
           class="btn btn-icon btn-secondary" title="Messages">
          ✉️<?php if ($unread > 0): ?> <span class="nav-badge" style="position:static"><?= $unread ?></span><?php endif; ?>
        </a>
      <?php endif; ?>
    </div>
  </header>

  <div class="page-body">
    <?php showFlash(); ?>
