<?php
$page_title = 'Admin Dashboard';
$active_nav = 'dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();

$users     = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$agents    = $conn->query("SELECT COUNT(*) AS c FROM agents WHERE approval_status='approved'")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) AS c FROM agents WHERE approval_status='pending'")->fetch_assoc()['c'];
$content   = $conn->query("SELECT COUNT(*) AS c FROM business_content")->fetch_assoc()['c'];
$forum     = $conn->query("SELECT COUNT(*) AS c FROM forum_topics")->fetch_assoc()['c'];
$messages  = $conn->query("SELECT COUNT(*) AS c FROM messages")->fetch_assoc()['c'];

// Recent pending agent requests
$requests = $conn->query("SELECT * FROM agents WHERE approval_status='pending' ORDER BY submitted_at DESC LIMIT 5");

// Recent user registrations
$new_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
?>

<div class="stats-grid">
  <a href="<?= $base ?>/admin/manage_users.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">👥</div><div class="stat-info"><div class="value"><?= $users ?></div><div class="label">Total Users</div></div>
  </a>
  <a href="<?= $base ?>/admin/manage_users.php?tab=agents" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">🎓</div><div class="stat-info"><div class="value"><?= $agents ?></div><div class="label">Active Agents</div></div>
  </a>
  <a href="<?= $base ?>/admin/agent_requests.php?status=pending" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">📋</div><div class="stat-info"><div class="value" style="color:<?= $pending > 0 ? 'var(--clr-warning)' : 'inherit' ?>"><?= $pending ?></div><div class="label">Pending Requests</div></div>
  </a>
  <a href="<?= $base ?>/admin/manage_content.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">📚</div><div class="stat-info"><div class="value"><?= $content ?></div><div class="label">Resources</div></div>
  </a>
  <a href="<?= $base ?>/user/forum.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">💬</div><div class="stat-info"><div class="value"><?= $forum ?></div><div class="label">Forum Topics</div></div>
  </a>
  <a href="<?= $base ?>/admin/manage_users.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">✉️</div><div class="stat-info"><div class="value"><?= $messages ?></div><div class="label">Messages Sent</div></div>
  </a>
</div>

<?php if ($pending > 0): ?>
<div class="notice" style="display:flex;align-items:center;justify-content:space-between;gap:12px">
  <span>⚠️ You have <strong><?= $pending ?></strong> pending agent registration<?= $pending > 1 ? 's' : '' ?> awaiting approval.</span>
  <a href="<?= $base ?>/admin/agent_requests.php" class="btn btn-warning btn-sm">Review Now</a>
</div>
<?php endif; ?>

<div class="grid-2">
  <!-- Pending requests -->
  <div class="card">
    <div class="card-header">
      <h3>Pending Agent Requests</h3>
      <a href="<?= $base ?>/admin/agent_requests.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($requests->num_rows === 0): ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">✅</div>
        <p>No pending requests.</p>
      </div>
    <?php else: ?>
      <?php while ($r = $requests->fetch_assoc()): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--clr-border);display:flex;justify-content:space-between;align-items:center;gap:10px">
          <div>
            <div style="font-weight:600"><?= htmlspecialchars($r['name']) ?></div>
            <div style="font-size:.82rem;color:var(--txt-secondary)"><?= htmlspecialchars($r['expertise']) ?> · <?= timeAgo($r['submitted_at']) ?></div>
          </div>
          <a href="<?= $base ?>/admin/agent_requests.php?view=<?= $r['id'] ?>" class="btn btn-warning btn-sm">Review</a>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <!-- New users -->
  <div class="card">
    <div class="card-header">
      <h3>Recent Registrations</h3>
      <a href="<?= $base ?>/admin/manage_users.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php while ($u = $new_users->fetch_assoc()): ?>
      <div style="padding:12px 0;border-bottom:1px solid var(--clr-border);display:flex;justify-content:space-between;align-items:center">
        <div>
          <div style="font-weight:600"><?= htmlspecialchars($u['name']) ?></div>
          <div style="font-size:.82rem;color:var(--txt-secondary)"><?= htmlspecialchars($u['email']) ?></div>
        </div>
        <div>
          <span class="status-pill <?= $u['status']==='active'?'pill-active':'pill-suspended' ?>"><?= ucfirst($u['status']) ?></span>
          <div style="font-size:.75rem;color:var(--txt-muted);text-align:right;margin-top:4px"><?= timeAgo($u['created_at']) ?></div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
