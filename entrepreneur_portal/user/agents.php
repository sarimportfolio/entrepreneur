<?php
$page_title = 'Find Agents';
$active_nav = 'agents';
require_once dirname(__DIR__) . '/includes/header.php';
requireUser();
$uid = $_SESSION['user_id'];

// ── Single agent profile view ─────────────────────────────────
if (isset($_GET['view'])) {
    $mid    = (int)$_GET['view'];
    $agent = $conn->query("SELECT a.*, c.name AS cat_name FROM agents a
        LEFT JOIN categories c ON a.category_id=c.id
        WHERE a.id=$mid AND a.approval_status='approved'")->fetch_assoc();

    if (!$agent) { flashSet('error','Agent not found.'); redirect(BASE_URL.'/user/agents.php'); }

    $content   = $conn->query("SELECT * FROM business_content WHERE agent_id=$mid AND status='published' ORDER BY created_at DESC LIMIT 8");
    $feedbacks = $conn->query("SELECT f.*,
        CASE f.from_type WHEN 'user' THEN u.name WHEN 'admin' THEN ad.name END AS from_name
        FROM feedback f
        LEFT JOIN users u   ON f.from_type='user'  AND f.from_id=u.id
        LEFT JOIN admins ad ON f.from_type='admin' AND f.from_id=ad.id
        WHERE f.agent_id=$mid ORDER BY f.created_at DESC");

    $avg  = getAvgRating($conn, $mid);
    $gave = $conn->query("SELECT id FROM feedback WHERE from_id=$uid AND from_type='user' AND agent_id=$mid")->num_rows > 0;

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback']) && !$gave) {
        $rating  = max(1, min(5, (int)($_POST['rating'] ?? 5)));
        $comment = sanitize($conn, $_POST['comment'] ?? '');
        $conn->query("INSERT INTO feedback (from_id,from_type,agent_id,rating,comment) VALUES ($uid,'user',$mid,$rating,'$comment')");
        flashSet('success','Feedback submitted!');
        redirect(BASE_URL.'/user/agents.php?view='.$mid);
    }
    ?>
    <a href="<?= $base ?>/user/agents.php" class="btn btn-secondary btn-sm mb-20">← Back to Agents</a>

    <div class="card mb-20">
      <div class="profile-header">
        <?php
        $v_pic_path = dirname(__DIR__) . '/uploads/profiles/' . ($agent['profile_pic'] ?? '');
        $v_has_pic  = !empty($agent['profile_pic']) && $agent['profile_pic'] !== 'default.png' && file_exists($v_pic_path);
        ?>
        <?php if ($v_has_pic): ?>
          <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($agent['profile_pic']) ?>"
               style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--clr-accent);flex-shrink:0">
        <?php else: ?>
          <?= initialsAvatar($agent['name'], 100, 'avatar-xl') ?>
        <?php endif; ?>
        <div class="profile-info">
          <h2><?= htmlspecialchars($agent['name']) ?></h2>
          <div class="expertise"><?= htmlspecialchars($agent['expertise']) ?></div>
          <?php if ($avg): ?>
            <div style="margin-bottom:8px"><?= ratingStars(round($avg)) ?> <span style="font-size:.85rem;color:var(--txt-secondary)"><?= $avg ?>/5</span></div>
          <?php endif; ?>
          <div class="meta">
            <?php if ($agent['cat_name']): ?><span>📂 <?= htmlspecialchars($agent['cat_name']) ?></span><?php endif; ?>
            <?php if ($agent['experience']): ?><span>⏱ <?= $agent['experience'] ?> yrs exp.</span><?php endif; ?>
            <?php if ($agent['specialization']): ?><span>🎯 <?= htmlspecialchars($agent['specialization']) ?></span><?php endif; ?>
            <?php if ($agent['phone']): ?><span>📞 <?= htmlspecialchars($agent['phone']) ?></span><?php endif; ?>
          </div>
          <?php if ($agent['linkedin'] || $agent['website']): ?>
          <div style="margin-top:10px;display:flex;gap:10px;flex-wrap:wrap">
            <?php if ($agent['linkedin']): ?><a href="<?= htmlspecialchars($agent['linkedin']) ?>" target="_blank" class="btn btn-secondary btn-sm">LinkedIn ↗</a><?php endif; ?>
            <?php if ($agent['website']): ?><a href="<?= htmlspecialchars($agent['website']) ?>" target="_blank" class="btn btn-secondary btn-sm">Website ↗</a><?php endif; ?>
          </div>
          <?php endif; ?>
        </div>
        <div style="margin-left:auto">
          <a href="<?= $base ?>/user/messages.php?compose=<?= $mid ?>" class="btn btn-primary btn-sm">✉️ Send Message</a>
        </div>
      </div>
      <?php if ($agent['bio']): ?>
        <hr class="divider">
        <h4 style="margin-bottom:8px">About</h4>
        <p style="color:var(--txt-secondary);line-height:1.7"><?= nl2br(htmlspecialchars($agent['bio'])) ?></p>
      <?php endif; ?>
    </div>

    <?php if ($content->num_rows > 0): ?>
    <div class="card mb-20">
      <div class="card-header"><h3>Resources by <?= htmlspecialchars($agent['name']) ?></h3></div>
      <div class="grid-2">
        <?php while ($r = $content->fetch_assoc()): ?>
          <div class="content-card">
            <?= contentTypeBadge($r['content_type']) ?>
            <div class="title"><a href="<?= $base ?>/user/resources.php?view=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></div>
            <div class="desc"><?= htmlspecialchars(substr($r['description'],0,80)) ?>...</div>
          </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

    <div class="grid-2">
      <div class="card">
        <div class="card-header"><h3>Reviews (<?= $feedbacks->num_rows ?>)</h3></div>
        <?php if ($feedbacks->num_rows === 0): ?>
          <p class="text-muted text-small" style="padding:10px 0">No reviews yet. Be the first!</p>
        <?php else: ?>
          <?php while ($fb = $feedbacks->fetch_assoc()): ?>
            <div class="forum-reply" style="margin-bottom:10px">
              <div class="reply-header">
                <strong class="reply-author"><?= htmlspecialchars($fb['from_name']) ?></strong>
                <span><?= ratingStars($fb['rating']) ?></span>
                <span class="reply-time"><?= timeAgo($fb['created_at']) ?></span>
              </div>
              <p style="color:var(--txt-secondary);font-size:.9rem"><?= nl2br(htmlspecialchars($fb['comment'])) ?></p>
            </div>
          <?php endwhile; ?>
        <?php endif; ?>
      </div>

      <?php if (!$gave): ?>
      <div class="card">
        <div class="card-header"><h3>Leave a Review</h3></div>
        <form method="POST">
          <div class="form-group">
            <label>Rating</label>
            <div class="rating-input">
              <?php for ($i=5;$i>=1;$i--): ?>
                <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i===5?'checked':'' ?>>
                <label for="star<?= $i ?>">★</label>
              <?php endfor; ?>
            </div>
          </div>
          <div class="form-group">
            <label>Comments</label>
            <textarea name="comment" class="form-control" rows="4" placeholder="Share your experience..."></textarea>
          </div>
          <button type="submit" name="submit_feedback" class="btn btn-primary">Submit Review</button>
        </form>
      </div>
      <?php else: ?>
      <div class="card"><div class="empty-state"><div class="empty-icon">✅</div><h3>Review Submitted</h3><p>You've already reviewed this agent.</p></div></div>
      <?php endif; ?>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Agent listing ─────────────────────────────────────────────
$search = sanitize($conn, $_GET['q']        ?? '');
$cat    = (int)($_GET['category']            ?? 0);

$where  = "WHERE a.approval_status='approved'";
if ($search) $where .= " AND (a.name LIKE '%$search%' OR a.expertise LIKE '%$search%' OR a.specialization LIKE '%$search%')";
if ($cat)    $where .= " AND a.category_id=$cat";

$agents = $conn->query("SELECT a.*, c.name AS cat_name,
    (SELECT AVG(rating) FROM feedback WHERE agent_id=a.id) AS avg_rating
    FROM agents a
    LEFT JOIN categories c ON a.category_id=c.id
    $where ORDER BY avg_rating DESC, a.approved_at DESC");

$cats = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="filter-row">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:200px">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search agents by name or expertise...">
    </div>
    <select name="category" class="form-control" style="width:auto;min-width:180px" onchange="this.form.submit()">
      <option value="">All Categories</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Search</button>
    <?php if ($search || $cat): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<?php if ($agents->num_rows === 0): ?>
  <div class="empty-state">
    <div class="empty-icon">👤</div>
    <h3>No agents found</h3>
    <p>Try adjusting your search or filters.</p>
    <?php if ($search || $cat): ?><a href="?" class="btn btn-secondary btn-sm">Clear Filters</a><?php endif; ?>
  </div>
<?php else: ?>
<div class="grid-3">
  <?php while ($m = $agents->fetch_assoc()): ?>
    <div class="agent-card">
      <?php
      $m_pic_path = dirname(__DIR__) . '/uploads/profiles/' . ($m['profile_pic'] ?? '');
      $m_has_pic  = !empty($m['profile_pic']) && $m['profile_pic'] !== 'default.png' && file_exists($m_pic_path);
      ?>
      <?php if ($m_has_pic): ?>
        <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($m['profile_pic']) ?>"
             style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid var(--clr-border);margin:0 auto;display:block">
      <?php else: ?>
        <?= initialsAvatar($m['name'], 70) ?>
      <?php endif; ?>
      <h4 style="margin-top:10px"><?= htmlspecialchars($m['name']) ?></h4>
      <div class="expertise"><?= htmlspecialchars($m['expertise']) ?></div>
      <?php if ($m['avg_rating']): ?>
        <div style="margin-bottom:6px"><?= ratingStars(round($m['avg_rating'])) ?> <span class="text-small text-muted"><?= round($m['avg_rating'],1) ?></span></div>
      <?php endif; ?>
      <div class="category"><?= htmlspecialchars($m['cat_name'] ?? '—') ?><?= $m['experience'] ? ' · '.$m['experience'].' yrs' : '' ?></div>
      <div style="margin-top:14px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
        <a href="?view=<?= $m['id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
        <a href="<?= $base ?>/user/messages.php?compose=<?= $m['id'] ?>" class="btn btn-secondary btn-sm">✉️ Message</a>
      </div>
    </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
