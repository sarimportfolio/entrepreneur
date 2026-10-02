<?php
$page_title = 'My Feedback';
$active_nav = 'feedback';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

$filter = sanitize($conn, $_GET['rating'] ?? '');
$where  = "WHERE f.agent_id=$aid";
if ($filter) $where .= " AND f.rating=" . (int)$filter;

$feedbacks = $conn->query("SELECT f.*,
    CASE f.from_type WHEN 'user' THEN u.name WHEN 'admin' THEN ad.name END AS from_name
    FROM feedback f
    LEFT JOIN users u   ON f.from_type='user'  AND f.from_id=u.id
    LEFT JOIN admins ad ON f.from_type='admin' AND f.from_id=ad.id
    $where ORDER BY f.created_at DESC");

$avg   = getAvgRating($conn, $aid);
$total = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE agent_id=$aid")->fetch_assoc()['c'];

// Rating distribution
$dist = [];
for ($i = 1; $i <= 5; $i++) {
    $dist[$i] = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE agent_id=$aid AND rating=$i")->fetch_assoc()['c'];
}
?>

<!-- Summary card -->
<div class="card mb-24">
  <div style="display:flex;gap:40px;flex-wrap:wrap;align-items:center">
    <div style="text-align:center">
      <div style="font-size:3rem;font-weight:700;color:var(--clr-accent)"><?= $avg ?? '—' ?></div>
      <div><?= $avg ? ratingStars(round($avg)) : '—' ?></div>
      <div style="color:var(--txt-secondary);font-size:.85rem;margin-top:4px"><?= $total ?> total reviews</div>
    </div>
    <div style="flex:1;min-width:200px">
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <?php $pct = $total > 0 ? ($dist[$i] / $total * 100) : 0; ?>
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
          <span style="width:10px;text-align:right;font-size:.8rem"><?= $i ?></span>
          <span style="color:var(--clr-warning)">★</span>
          <div style="flex:1;height:8px;background:var(--clr-surface2);border-radius:99px;overflow:hidden">
            <div style="width:<?= $pct ?>%;height:100%;background:var(--clr-warning);border-radius:99px"></div>
          </div>
          <span style="width:24px;font-size:.8rem;color:var(--txt-muted)"><?= $dist[$i] ?></span>
        </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- Filter + list -->
<div class="card">
  <div class="card-header">
    <h3>All Feedback</h3>
    <div style="display:flex;gap:8px">
      <a href="?" class="btn btn-sm <?= !$filter?'btn-primary':'btn-secondary' ?>">All</a>
      <?php for ($i = 5; $i >= 1; $i--): ?>
        <a href="?rating=<?= $i ?>" class="btn btn-sm <?= $filter==$i?'btn-primary':'btn-secondary' ?>"><?= $i ?>★</a>
      <?php endfor; ?>
    </div>
  </div>

  <?php if ($feedbacks->num_rows === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">⭐</div>
      <h3>No feedback yet</h3>
      <p>Feedback from users and admin will appear here.</p>
    </div>
  <?php else: ?>
    <?php while ($fb = $feedbacks->fetch_assoc()): ?>
      <div class="forum-reply" style="margin-bottom:12px">
        <div class="reply-header">
          <strong class="reply-author"><?= htmlspecialchars($fb['from_name'] ?? 'Unknown') ?></strong>
          <span class="badge <?= $fb['from_type']==='admin'?'badge--purple':'badge--gray' ?>" style="font-size:.7rem">
            <?= ucfirst($fb['from_type']) ?>
          </span>
          <?= ratingStars($fb['rating']) ?>
          <span class="reply-time"><?= timeAgo($fb['created_at']) ?></span>
        </div>
        <?php if ($fb['comment']): ?>
          <p style="color:var(--txt-secondary);font-size:.9rem;line-height:1.6;margin-top:6px">
            "<?= nl2br(htmlspecialchars($fb['comment'])) ?>"
          </p>
        <?php else: ?>
          <p style="color:var(--txt-muted);font-size:.85rem;font-style:italic">No comment provided.</p>
        <?php endif; ?>
      </div>
    <?php endwhile; ?>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
