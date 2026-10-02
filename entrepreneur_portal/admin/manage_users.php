<?php
$page_title = 'Users & Agents';
$active_nav = 'users';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();

// ── Toggle user status ────────────────────────────────────────
if (isset($_GET['toggle_user'])) {
    $id  = (int)$_GET['toggle_user'];
    $row = $conn->query("SELECT status FROM users WHERE id=$id")->fetch_assoc();
    if ($row) {
        $new = $row['status'] === 'active' ? 'suspended' : 'active';
        $conn->query("UPDATE users SET status='$new' WHERE id=$id");
        flashSet('success', "User " . ($new === 'active' ? 'activated' : 'suspended') . ".");
    }
    redirect(BASE_URL . '/admin/manage_users.php');
}

// ── Toggle agent status ───────────────────────────────────────
if (isset($_GET['toggle_agent'])) {
    $id  = (int)$_GET['toggle_agent'];
    $row = $conn->query("SELECT account_status FROM agents WHERE id=$id")->fetch_assoc();
    if ($row) {
        $new = $row['account_status'] === 'active' ? 'suspended' : 'active';
        $conn->query("UPDATE agents SET account_status='$new' WHERE id=$id");
        flashSet('success', "Agent " . ($new === 'active' ? 'activated' : 'suspended') . ".");
    }
    redirect(BASE_URL . '/admin/manage_users.php');
}

// ── Delete user ───────────────────────────────────────────────
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $conn->query("DELETE FROM users WHERE id=$id");
    flashSet('success', 'User deleted.');
    redirect(BASE_URL . '/admin/manage_users.php');
}

// ── Delete agent ──────────────────────────────────────────────
if (isset($_GET['delete_agent'])) {
    $id = (int)$_GET['delete_agent'];
    $conn->query("DELETE FROM agents WHERE id=$id");
    flashSet('success', 'Agent deleted.');
    redirect(BASE_URL . '/admin/manage_users.php');
}

$search   = sanitize($conn, $_GET['q'] ?? '');
$user_where  = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$agent_where = $search
    ? "WHERE approval_status='approved' AND (name LIKE '%$search%' OR email LIKE '%$search%')"
    : "WHERE approval_status='approved'";

$users  = $conn->query("SELECT * FROM users $user_where ORDER BY created_at DESC");
$agents = $conn->query("SELECT a.*, c.name AS cat_name FROM agents a
    LEFT JOIN categories c ON a.category_id=c.id
    $agent_where ORDER BY a.approved_at DESC");
?>

<!-- Search -->
<div class="filter-row mb-20">
  <form method="GET">
    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or email...">
    </div>
  </form>
  <?php if ($search): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
</div>

<div class="tabs-wrapper">
  <div class="tabs">
    <button class="tab-btn active" data-tab="tab-users">👥 Users (<?= $users->num_rows ?>)</button>
    <button class="tab-btn" data-tab="tab-agents">🎓 Agents (<?= $agents->num_rows ?>)</button>
  </div>

  <!-- USERS -->
  <div class="tab-panel active" id="tab-users">
    <div class="card" style="margin-top:16px">
      <?php if ($users->num_rows === 0): ?>
        <div class="empty-state"><div class="empty-icon">👥</div><h3>No users found</h3></div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Name</th><th>Email</th><th>Phone</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php while ($u = $users->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone'] ?: '—') ?></td>
                <td><span class="status-pill <?= $u['status']==='active'?'pill-active':'pill-suspended' ?>"><?= ucfirst($u['status']) ?></span></td>
                <td style="color:var(--txt-muted);white-space:nowrap"><?= timeAgo($u['created_at']) ?></td>
                <td>
                  <div class="td-actions">
                    <a href="?toggle_user=<?= $u['id'] ?>" class="btn btn-sm <?= $u['status']==='active'?'btn-warning':'btn-success' ?>"
                       data-confirm="<?= $u['status']==='active'?'Suspend':'Activate' ?> <?= htmlspecialchars($u['name']) ?>?">
                      <?= $u['status']==='active' ? '🔒 Suspend' : '✓ Activate' ?>
                    </a>
                    <a href="?delete_user=<?= $u['id'] ?>" class="btn btn-danger btn-sm"
                       data-confirm="Permanently delete <?= htmlspecialchars($u['name']) ?>? This cannot be undone.">🗑 Delete</a>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- AGENTS -->
  <div class="tab-panel" id="tab-agents">
    <div class="card" style="margin-top:16px">
      <?php if ($agents->num_rows === 0): ?>
        <div class="empty-state"><div class="empty-icon">🎓</div><h3>No approved agents found</h3></div>
      <?php else: ?>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>Name</th><th>Email</th><th>Expertise</th><th>Category</th><th>Account</th><th>Approved</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php while ($a = $agents->fetch_assoc()): ?>
              <tr>
                <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
                <td><?= htmlspecialchars($a['email']) ?></td>
                <td><?= htmlspecialchars($a['expertise']) ?></td>
                <td><?= htmlspecialchars($a['cat_name'] ?? '—') ?></td>
                <td><span class="status-pill <?= $a['account_status']==='active'?'pill-active':'pill-suspended' ?>"><?= ucfirst($a['account_status']) ?></span></td>
                <td style="color:var(--txt-muted);white-space:nowrap"><?= timeAgo($a['approved_at']) ?></td>
                <td>
                  <div class="td-actions">
                    <a href="<?= $base ?>/admin/give_feedback.php?agent=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">⭐ Feedback</a>
                    <a href="?toggle_agent=<?= $a['id'] ?>" class="btn btn-sm <?= $a['account_status']==='active'?'btn-warning':'btn-success' ?>"
                       data-confirm="<?= $a['account_status']==='active'?'Suspend':'Activate' ?> <?= htmlspecialchars($a['name']) ?>?">
                      <?= $a['account_status']==='active' ? '🔒 Suspend' : '✓ Activate' ?>
                    </a>
                    <a href="?delete_agent=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                       data-confirm="Delete <?= htmlspecialchars($a['name']) ?> and all their content?">🗑 Delete</a>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
