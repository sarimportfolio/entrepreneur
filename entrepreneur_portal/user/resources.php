<?php
$page_title = 'Business Resources';
$active_nav = 'resources';
require_once dirname(__DIR__) . '/includes/header.php';
requireUser();

// ── Single resource view ─────────────────────────────────────
if (isset($_GET['view'])) {
    $rid = (int)$_GET['view'];
    $r   = $conn->query("SELECT bc.*, a.name AS agent_name, a.id AS agent_id, c.name AS cat_name
        FROM business_content bc
        LEFT JOIN agents a ON bc.agent_id = a.id
        LEFT JOIN categories c ON bc.category_id = c.id
        WHERE bc.id=$rid AND bc.status='published'")->fetch_assoc();

    if (!$r) { flashSet('error', 'Resource not found.'); redirect(BASE_URL . '/user/resources.php'); }
    $conn->query("UPDATE business_content SET views=views+1 WHERE id=$rid");
    ?>
    <a href="<?= $base ?>/user/resources.php" class="btn btn-secondary btn-sm mb-20">← Back to Resources</a>
    <div class="card">
      <?= contentTypeBadge($r['content_type']) ?>
      <h2 style="margin:12px 0 8px"><?= htmlspecialchars($r['title']) ?></h2>
      <div style="display:flex;gap:16px;flex-wrap:wrap;color:var(--txt-secondary);font-size:.85rem;margin-bottom:20px">
        <span>👤 <a href="<?= $base ?>/user/agents.php?view=<?= $r['agent_id'] ?>"><?= htmlspecialchars($r['agent_name']) ?></a></span>
        <?php if ($r['cat_name']): ?><span>📂 <?= htmlspecialchars($r['cat_name']) ?></span><?php endif; ?>
        <span>👁 <?= $r['views'] ?> views</span>
        <span>🕐 <?= timeAgo($r['created_at']) ?></span>
      </div>
      <hr class="divider">
      <div style="line-height:1.8;color:var(--txt-primary)">
        <?= nl2br(htmlspecialchars($r['description'])) ?>
      </div>
      <?php if ($r['file_path']): ?>
        <div style="margin-top:20px">
          <a href="<?= $base ?>/uploads/content/<?= htmlspecialchars($r['file_path']) ?>" class="btn btn-primary" download>
            ⬇️ Download Attachment
          </a>
        </div>
      <?php endif; ?>
      <?php if ($r['tags']): ?>
        <div style="margin-top:20px">
          <?php foreach (explode(',', $r['tags']) as $tag): ?>
            <span class="badge badge--gray"><?= htmlspecialchars(trim($tag)) ?></span>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Listing ──────────────────────────────────────────────────
$search = sanitize($conn, $_GET['q']       ?? '');
$cat    = (int)($_GET['category'] ?? 0);
$type   = sanitize($conn, $_GET['type']    ?? '');

$where = "WHERE bc.status='published'";
if ($search) $where .= " AND (bc.title LIKE '%$search%' OR bc.description LIKE '%$search%' OR bc.tags LIKE '%$search%')";
if ($cat)    $where .= " AND bc.category_id=$cat";
if ($type)   $where .= " AND bc.content_type='$type'";

$resources = $conn->query("SELECT bc.*, a.name AS agent_name, c.name AS cat_name
    FROM business_content bc
    LEFT JOIN agents a ON bc.agent_id = a.id
    LEFT JOIN categories c ON bc.category_id = c.id
    $where ORDER BY bc.created_at DESC");

$cats  = $conn->query("SELECT * FROM categories ORDER BY name");
$types = ['business_idea'=>'Business Ideas','template'=>'Templates','framework'=>'Frameworks',
          'market_research'=>'Market Research','startup_plan'=>'Startup Plans',
          'partnership'=>'Partnerships','financial'=>'Financial','opportunity'=>'Opportunities','other'=>'Other'];
?>

<div class="filter-row">
  <form method="GET" style="display:flex;gap:12px;flex-wrap:wrap;width:100%">
    <div class="search-bar" style="flex:1;min-width:200px">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search resources...">
    </div>
    <select name="category" class="form-control" style="width:auto;min-width:160px">
      <option value="">All Categories</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <select name="type" class="form-control" style="width:auto;min-width:160px">
      <option value="">All Types</option>
      <?php foreach ($types as $k => $v): ?>
        <option value="<?= $k ?>" <?= $type===$k?'selected':'' ?>><?= $v ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary btn-sm">Filter</button>
    <?php if ($search || $cat || $type): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
  </form>
</div>

<?php if ($resources->num_rows === 0): ?>
  <div class="empty-state">
    <div class="empty-icon">📭</div>
    <h3>No resources found</h3>
    <p>Try adjusting your filters.</p>
  </div>
<?php else: ?>
<div class="grid-3">
  <?php while ($r = $resources->fetch_assoc()): ?>
    <a href="?view=<?= $r['id'] ?>" class="content-card" style="text-decoration:none;color:inherit;display:block">
      <?= contentTypeBadge($r['content_type']) ?>
      <div class="title"><?= htmlspecialchars($r['title']) ?></div>
      <div class="desc"><?= htmlspecialchars(substr($r['description'], 0, 100)) ?>...</div>
      <div class="meta">
        <span>👤 <?= htmlspecialchars($r['agent_name']) ?></span>
        <span>📂 <?= htmlspecialchars($r['cat_name'] ?? '—') ?></span>
        <span>👁 <?= $r['views'] ?></span>
        <span><?= timeAgo($r['created_at']) ?></span>
      </div>
    </a>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
