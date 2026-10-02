<?php
$page_title = 'Agent Dashboard';
$active_nav = 'dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

$content_count = $conn->query("SELECT COUNT(*) AS c FROM business_content WHERE agent_id=$aid")->fetch_assoc()['c'];
$views_total   = $conn->query("SELECT COALESCE(SUM(views),0) AS c FROM business_content WHERE agent_id=$aid")->fetch_assoc()['c'];
$avg           = getAvgRating($conn, $aid);
$msgs          = getUnreadCount($conn, $aid, 'agent');

$recent    = $conn->query("SELECT * FROM business_content WHERE agent_id=$aid ORDER BY created_at DESC LIMIT 5");
$feedbacks = $conn->query("SELECT f.*, u.name AS from_name FROM feedback f
    LEFT JOIN users u ON f.from_id=u.id AND f.from_type='user'
    WHERE f.agent_id=$aid ORDER BY f.created_at DESC LIMIT 3");
?>

<div class="stats-grid">
  <a href="<?= $base ?>/agent/my_content.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">📁</div>
    <div class="stat-info"><div class="value"><?= $content_count ?></div><div class="label">Content Uploaded</div></div>
  </a>
  <a href="<?= $base ?>/agent/my_content.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">👁</div>
    <div class="stat-info"><div class="value"><?= $views_total ?></div><div class="label">Total Views</div></div>
  </a>
  <a href="<?= $base ?>/agent/view_feedback.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">⭐</div>
    <div class="stat-info"><div class="value"><?= $avg ?? '—' ?></div><div class="label">Avg Rating</div></div>
  </a>
  <a href="<?= $base ?>/agent/messages.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">✉️</div>
    <div class="stat-info"><div class="value"><?= $msgs ?></div><div class="label">Unread Messages</div></div>
  </a>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <h3>My Recent Content</h3>
      <a href="<?= $base ?>/agent/upload_content.php" class="btn btn-primary btn-sm">➕ Upload</a>
    </div>
    <?php if ($recent->num_rows === 0): ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">📭</div>
        <p>No content uploaded yet.</p>
        <a href="<?= $base ?>/agent/upload_content.php" class="btn btn-primary btn-sm">Upload First Resource</a>
      </div>
    <?php else: ?>
      <?php while ($r = $recent->fetch_assoc()): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--clr-border);display:flex;justify-content:space-between;align-items:center;gap:10px">
          <div>
            <?= contentTypeBadge($r['content_type']) ?>
            <div style="font-weight:600;margin-top:4px"><?= htmlspecialchars($r['title']) ?></div>
            <div style="font-size:.8rem;color:var(--txt-muted)">👁 <?= $r['views'] ?> views · <?= timeAgo($r['created_at']) ?></div>
          </div>
          <a href="<?= $base ?>/agent/my_content.php?edit=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Recent Feedback</h3>
      <a href="<?= $base ?>/agent/view_feedback.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($feedbacks->num_rows === 0): ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">⭐</div>
        <p>No feedback received yet.</p>
      </div>
    <?php else: ?>
      <?php while ($fb = $feedbacks->fetch_assoc()): ?>
        <div style="padding:12px 0;border-bottom:1px solid var(--clr-border)">
          <div style="display:flex;align-items:center;gap:10px;margin-bottom:4px">
            <strong style="font-size:.9rem"><?= htmlspecialchars($fb['from_name']) ?></strong>
            <?= ratingStars($fb['rating']) ?>
          </div>
          <p style="color:var(--txt-secondary);font-size:.85rem"><?= htmlspecialchars(substr($fb['comment'],0,80)) ?>...</p>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
