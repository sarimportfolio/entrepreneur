<?php
$page_title = 'Dashboard';
$active_nav = 'dashboard';
require_once dirname(__DIR__) . '/includes/header.php';
requireUser();

$uid = $_SESSION['user_id'];

$agents   = $conn->query("SELECT COUNT(*) AS c FROM agents WHERE approval_status='approved'")->fetch_assoc()['c'];
$resources = $conn->query("SELECT COUNT(*) AS c FROM business_content WHERE status='published'")->fetch_assoc()['c'];
$topics    = $conn->query("SELECT COUNT(*) AS c FROM forum_topics")->fetch_assoc()['c'];
$msgs      = $conn->query("SELECT COUNT(*) AS c FROM messages WHERE receiver_id=$uid AND receiver_type='user' AND is_read=0")->fetch_assoc()['c'];

$recent = $conn->query("SELECT bc.*, a.name AS agent_name, c.name AS cat_name
    FROM business_content bc
    LEFT JOIN agents a ON bc.agent_id=a.id
    LEFT JOIN categories c ON bc.category_id=c.id
    WHERE bc.status='published' ORDER BY bc.created_at DESC LIMIT 6");

$forum = $conn->query("SELECT ft.*,
    CASE ft.author_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS author_name
    FROM forum_topics ft
    LEFT JOIN users u  ON ft.author_type='user'  AND ft.author_id=u.id
    LEFT JOIN agents a ON ft.author_type='agent' AND ft.author_id=a.id
    ORDER BY ft.created_at DESC LIMIT 5");
?>

<div class="stats-grid">
  <a href="<?= $base ?>/user/agents.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">👤</div>
    <div class="stat-info">
      <div class="value"><?= $agents ?></div>
      <div class="label">Expert Agents</div>
    </div>
  </a>
  <a href="<?= $base ?>/user/resources.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">📚</div>
    <div class="stat-info">
      <div class="value"><?= $resources ?></div>
      <div class="label">Resources</div>
    </div>
  </a>
  <a href="<?= $base ?>/user/forum.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">💬</div>
    <div class="stat-info">
      <div class="value"><?= $topics ?></div>
      <div class="label">Forum Topics</div>
    </div>
  </a>
  <a href="<?= $base ?>/user/messages.php" class="stat-card" style="text-decoration:none;color:inherit">
    <div class="stat-icon">✉️</div>
    <div class="stat-info">
      <div class="value"><?= $msgs ?></div>
      <div class="label">Unread Messages</div>
    </div>
  </a>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <h3>Latest Resources</h3>
      <a href="<?= $base ?>/user/resources.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($recent->num_rows === 0): ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">📭</div>
        <p>No resources yet.</p>
      </div>
    <?php else: ?>
      <?php while ($r = $recent->fetch_assoc()): ?>
        <div class="content-card" style="margin-bottom:12px;">
          <?= contentTypeBadge($r['content_type']) ?>
          <div class="title">
            <a href="<?= $base ?>/user/resources.php?view=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a>
          </div>
          <div class="desc"><?= htmlspecialchars(substr($r['description'], 0, 90)) ?>...</div>
          <div class="meta">
            <span>👤 <?= htmlspecialchars($r['agent_name']) ?></span>
            <span>📂 <?= htmlspecialchars($r['cat_name'] ?? '—') ?></span>
            <span><?= timeAgo($r['created_at']) ?></span>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <h3>Recent Discussions</h3>
      <a href="<?= $base ?>/user/forum.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <?php if ($forum->num_rows === 0): ?>
      <div class="empty-state" style="padding:30px">
        <div class="empty-icon">💬</div>
        <p>No discussions yet.</p>
      </div>
    <?php else: ?>
      <?php while ($t = $forum->fetch_assoc()): ?>
        <div class="forum-topic" style="margin-bottom:10px;">
          <div class="topic-icon">💬</div>
          <div class="topic-body">
            <div class="topic-title">
              <a href="<?= $base ?>/user/forum.php?topic=<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
            </div>
            <div class="topic-meta">
              <span>👤 <?= htmlspecialchars($t['author_name']) ?></span>
              <span>👁 <?= $t['views'] ?> views</span>
              <span><?= timeAgo($t['created_at']) ?></span>
            </div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
