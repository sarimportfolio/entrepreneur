<?php
$page_title = 'Agent Requests';
$active_nav = 'requests';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();

// ── Approve — just flip status, keep original password ────────
if (isset($_GET['approve'])) {
    $id  = (int)$_GET['approve'];
    $row = $conn->query("SELECT * FROM agents WHERE id=$id AND approval_status='pending'")->fetch_assoc();
    if ($row) {
        $conn->query("UPDATE agents SET approval_status='approved', approved_at=NOW() WHERE id=$id");
        flashSet('success', "Agent '{$row['name']}' approved. They can now login with the email and password they registered with.");
    }
    redirect(BASE_URL . '/admin/agent_requests.php');
}

// ── Reject ────────────────────────────────────────────────────
if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE agents SET approval_status='rejected' WHERE id=$id");
    flashSet('info', 'Agent registration rejected.');
    redirect(BASE_URL . '/admin/agent_requests.php');
}

// ── Single profile view ───────────────────────────────────────
if (isset($_GET['view'])) {
    $id    = (int)$_GET['view'];
    $agent = $conn->query("SELECT a.*, c.name AS cat_name FROM agents a
        LEFT JOIN categories c ON a.category_id=c.id
        WHERE a.id=$id")->fetch_assoc();

    if (!$agent) { flashSet('error','Not found.'); redirect(BASE_URL.'/admin/agent_requests.php'); }

    $pic_path = dirname(__DIR__) . '/uploads/profiles/' . ($agent['profile_pic'] ?? '');
    $has_pic  = !empty($agent['profile_pic']) && $agent['profile_pic'] !== 'default.png' && file_exists($pic_path);
    ?>
    <a href="<?= $base ?>/admin/agent_requests.php" class="btn btn-secondary btn-sm mb-20">← Back</a>
    <div class="card mb-20">
      <div class="profile-header">
        <?php if ($has_pic): ?>
          <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($agent['profile_pic']) ?>"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--clr-accent)">
        <?php else: ?>
          <?= initialsAvatar($agent['name'], 100) ?>
        <?php endif; ?>
        <div class="profile-info">
          <h2><?= htmlspecialchars($agent['name']) ?></h2>
          <div class="expertise"><?= htmlspecialchars($agent['expertise']) ?></div>
          <div class="meta">
            <span>📧 <?= htmlspecialchars($agent['email']) ?></span>
            <?php if ($agent['phone']): ?><span>📞 <?= htmlspecialchars($agent['phone']) ?></span><?php endif; ?>
            <?php if ($agent['cat_name']): ?><span>📂 <?= htmlspecialchars($agent['cat_name']) ?></span><?php endif; ?>
            <?php if ($agent['experience']): ?><span>⏱ <?= $agent['experience'] ?> yrs</span><?php endif; ?>
          </div>
          <div style="margin-top:10px">
            <span class="status-pill <?= $agent['approval_status']==='pending'?'pill-pending':($agent['approval_status']==='approved'?'pill-approved':'pill-rejected') ?>">
              <?= ucfirst($agent['approval_status']) ?>
            </span>
          </div>
        </div>
      </div>
      <?php if ($agent['bio']): ?>
        <hr class="divider">
        <h4 style="margin-bottom:8px">Bio</h4>
        <p style="color:var(--txt-secondary);line-height:1.7"><?= nl2br(htmlspecialchars($agent['bio'])) ?></p>
      <?php endif; ?>
      <?php if ($agent['linkedin'] || $agent['website']): ?>
        <div style="margin-top:16px;display:flex;gap:10px;flex-wrap:wrap">
          <?php if ($agent['linkedin']): ?><a href="<?= htmlspecialchars($agent['linkedin']) ?>" target="_blank" class="btn btn-secondary btn-sm">LinkedIn ↗</a><?php endif; ?>
          <?php if ($agent['website']): ?><a href="<?= htmlspecialchars($agent['website']) ?>" target="_blank" class="btn btn-secondary btn-sm">Website ↗</a><?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <?php if ($agent['approval_status'] === 'pending'): ?>
    <div style="display:flex;gap:12px;flex-wrap:wrap">
      <a href="?approve=<?= $agent['id'] ?>" class="btn btn-success"
         data-confirm="Approve <?= htmlspecialchars($agent['name']) ?>? They will login with their registered password.">
        ✓ Approve Agent
      </a>
      <a href="?reject=<?= $agent['id'] ?>" class="btn btn-danger"
         data-confirm="Reject this registration?">✕ Reject</a>
    </div>
    <?php elseif ($agent['approval_status'] === 'approved'): ?>
      <div class="flash flash--success" style="display:inline-flex">This agent is approved and active.</div>
    <?php else: ?>
      <div class="flash flash--error" style="display:inline-flex">This registration was rejected.</div>
    <?php endif; ?>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Listing ───────────────────────────────────────────────────
$filter  = sanitize($conn, $_GET['status'] ?? 'pending');
$allowed = ['pending','approved','rejected'];
if (!in_array($filter, $allowed)) $filter = 'pending';

$agents = $conn->query("SELECT a.*, c.name AS cat_name FROM agents a
    LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.approval_status='$filter' ORDER BY a.submitted_at DESC");

$counts = [];
foreach (['pending','approved','rejected'] as $s) {
    $counts[$s] = $conn->query("SELECT COUNT(*) AS c FROM agents WHERE approval_status='$s'")->fetch_assoc()['c'];
}
?>

<div class="card">
  <div class="card-header">
    <h3>Agent Registrations</h3>
    <div style="display:flex;gap:8px">
      <a href="?status=pending"  class="btn btn-sm <?= $filter==='pending' ?'btn-warning':'btn-secondary' ?>">Pending (<?= $counts['pending'] ?>)</a>
      <a href="?status=approved" class="btn btn-sm <?= $filter==='approved'?'btn-success':'btn-secondary' ?>">Approved (<?= $counts['approved'] ?>)</a>
      <a href="?status=rejected" class="btn btn-sm <?= $filter==='rejected'?'btn-danger':'btn-secondary' ?>">Rejected (<?= $counts['rejected'] ?>)</a>
    </div>
  </div>

  <?php if ($agents->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">📋</div><h3>No <?= $filter ?> requests</h3></div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Name</th><th>Email</th><th>Expertise</th><th>Category</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php while ($a = $agents->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['name']) ?></strong></td>
            <td><?= htmlspecialchars($a['email']) ?></td>
            <td><?= htmlspecialchars($a['expertise']) ?></td>
            <td><?= htmlspecialchars($a['cat_name'] ?? '—') ?></td>
            <td style="white-space:nowrap;color:var(--txt-muted)"><?= timeAgo($a['submitted_at']) ?></td>
            <td><span class="status-pill pill-<?= $a['approval_status'] ?>"><?= ucfirst($a['approval_status']) ?></span></td>
            <td>
              <div class="td-actions">
                <a href="?view=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">👁 View</a>
                <?php if ($a['approval_status'] === 'pending'): ?>
                  <a href="?approve=<?= $a['id'] ?>" class="btn btn-success btn-sm"
                     data-confirm="Approve <?= htmlspecialchars($a['name']) ?>?">✓ Approve</a>
                  <a href="?reject=<?= $a['id'] ?>" class="btn btn-danger btn-sm"
                     data-confirm="Reject?">✕ Reject</a>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
