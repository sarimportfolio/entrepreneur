<?php
$page_title = 'Business Categories';
$active_nav = 'categories';
require_once dirname(__DIR__) . '/includes/header.php';
requireUser();

// ── View agents inside a category ────────────────────────────
if (isset($_GET['id'])) {
    $cid     = (int)$_GET['id'];
    $cat_row = $conn->query("SELECT * FROM categories WHERE id=$cid")->fetch_assoc();
    if (!$cat_row) { flashSet('error','Category not found.'); redirect(BASE_URL.'/user/categories.php'); }

    $agents = $conn->query("SELECT a.*,
        (SELECT AVG(rating) FROM feedback WHERE agent_id=a.id) AS avg_rating
        FROM agents a
        WHERE a.category_id=$cid AND a.approval_status='approved'
        ORDER BY avg_rating DESC");

    $content = $conn->query("SELECT bc.*, a.name AS agent_name FROM business_content bc
        LEFT JOIN agents a ON bc.agent_id=a.id
        WHERE bc.category_id=$cid AND bc.status='published'
        ORDER BY bc.created_at DESC LIMIT 9");
    ?>
    <a href="<?= $base ?>/user/categories.php" class="btn btn-secondary btn-sm mb-20">← All Categories</a>

    <div style="display:flex;align-items:center;gap:16px;margin-bottom:24px">
      <div style="font-size:2.5rem"><?= $cat_row['icon'] ?></div>
      <div>
        <h2 style="margin-bottom:4px"><?= htmlspecialchars($cat_row['name']) ?></h2>
        <p style="color:var(--txt-secondary)"><?= htmlspecialchars($cat_row['description']) ?></p>
      </div>
    </div>

    <!-- Agents in this category -->
    <div class="card mb-24">
      <div class="card-header">
        <h3>Agents in <?= htmlspecialchars($cat_row['name']) ?> (<?= $agents->num_rows ?>)</h3>
        <a href="<?= $base ?>/user/agents.php?category=<?= $cid ?>" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if ($agents->num_rows === 0): ?>
        <div class="empty-state" style="padding:30px">
          <div class="empty-icon">👤</div>
          <p>No agents in this category yet.</p>
        </div>
      <?php else: ?>
        <div class="grid-3">
          <?php while ($a = $agents->fetch_assoc()): ?>
            <div class="agent-card">
              <?php
              $c_pic_path = dirname(__DIR__) . '/uploads/profiles/' . ($a['profile_pic'] ?? '');
              $c_has_pic  = !empty($a['profile_pic']) && $a['profile_pic'] !== 'default.png' && file_exists($c_pic_path);
              ?>
              <?php if ($c_has_pic): ?>
                <img src="<?= $base ?>/uploads/profiles/<?= htmlspecialchars($a['profile_pic']) ?>"
                     style="width:70px;height:70px;border-radius:50%;object-fit:cover;border:2px solid var(--clr-border);margin:0 auto;display:block">
              <?php else: ?>
                <?= initialsAvatar($a['name'], 70) ?>
              <?php endif; ?>
              <h4 style="margin-top:10px"><?= htmlspecialchars($a['name']) ?></h4>
              <div class="expertise"><?= htmlspecialchars($a['expertise']) ?></div>
              <?php if ($a['avg_rating']): ?>
                <div style="margin-bottom:6px"><?= ratingStars(round($a['avg_rating'])) ?></div>
              <?php endif; ?>
              <div style="margin-top:12px;display:flex;gap:8px;justify-content:center;flex-wrap:wrap">
                <a href="<?= $base ?>/user/agents.php?view=<?= $a['id'] ?>" class="btn btn-outline btn-sm">View Profile</a>
                <a href="<?= $base ?>/user/messages.php?compose=<?= $a['id'] ?>" class="btn btn-secondary btn-sm">✉️ Message</a>
              </div>
            </div>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Resources in this category -->
    <div class="card">
      <div class="card-header">
        <h3>Resources in <?= htmlspecialchars($cat_row['name']) ?> (<?= $content->num_rows ?>)</h3>
        <a href="<?= $base ?>/user/resources.php?category=<?= $cid ?>" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if ($content->num_rows === 0): ?>
        <div class="empty-state" style="padding:30px">
          <div class="empty-icon">📭</div>
          <p>No resources in this category yet.</p>
        </div>
      <?php else: ?>
        <div class="grid-3">
          <?php while ($r = $content->fetch_assoc()): ?>
            <a href="<?= $base ?>/user/resources.php?view=<?= $r['id'] ?>" class="content-card" style="text-decoration:none;color:inherit;display:block">
              <?= contentTypeBadge($r['content_type']) ?>
              <div class="title" style="margin-top:8px"><?= htmlspecialchars($r['title']) ?></div>
              <div class="desc"><?= htmlspecialchars(substr($r['description'],0,80)) ?>...</div>
              <div class="meta"><span>👤 <?= htmlspecialchars($r['agent_name']) ?></span></div>
            </a>
          <?php endwhile; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Category listing ──────────────────────────────────────────
$search = sanitize($conn, $_GET['q'] ?? '');
$where  = $search ? "WHERE c.name LIKE '%$search%' OR c.description LIKE '%$search%'" : '';

$cats = $conn->query("SELECT c.*,
    (SELECT COUNT(*) FROM business_content bc WHERE bc.category_id=c.id AND bc.status='published') AS content_count,
    (SELECT COUNT(*) FROM agents a WHERE a.category_id=c.id AND a.approval_status='approved') AS agent_count
    FROM categories c $where ORDER BY c.name");
?>

<div class="filter-row">
  <div class="search-bar">
    <span class="search-icon">🔍</span>
    <form method="GET" style="display:contents">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search categories...">
    </form>
  </div>
  <?php if ($search): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
</div>

<?php if ($cats->num_rows === 0): ?>
  <div class="empty-state">
    <div class="empty-icon">📂</div>
    <h3>No categories found</h3>
  </div>
<?php else: ?>
<div class="category-grid">
  <?php while ($c = $cats->fetch_assoc()): ?>
    <a href="?id=<?= $c['id'] ?>" class="category-card">
      <span class="cat-icon"><?= $c['icon'] ?></span>
      <div class="cat-name"><?= htmlspecialchars($c['name']) ?></div>
      <div class="cat-count"><?= $c['content_count'] ?> resources · <?= $c['agent_count'] ?> agents</div>
      <?php if ($c['description']): ?>
        <p style="font-size:.8rem;color:var(--txt-secondary);margin-top:8px;line-height:1.4">
          <?= htmlspecialchars(substr($c['description'], 0, 70)) ?>...
        </p>
      <?php endif; ?>
    </a>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
