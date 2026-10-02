<?php
$page_title = 'My Content';
$active_nav = 'content';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

$types = [
    'business_idea'   => 'Business Idea',
    'template'        => 'Template',
    'framework'       => 'Framework',
    'market_research' => 'Market Research',
    'startup_plan'    => 'Startup Plan',
    'partnership'     => 'Partnership Detail',
    'financial'       => 'Financial Support',
    'opportunity'     => 'Opportunity',
    'other'           => 'Other',
];

// ── Delete ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->query("SELECT file_path FROM business_content WHERE id=$id AND agent_id=$aid")->fetch_assoc();
    if ($row) {
        if ($row['file_path']) {
            @unlink(dirname(__DIR__) . '/uploads/content/' . $row['file_path']);
        }
        $conn->query("DELETE FROM business_content WHERE id=$id AND agent_id=$aid");
        flashSet('success', 'Content deleted.');
    }
    redirect(BASE_URL . '/agent/my_content.php');
}

// ── Edit (load) ───────────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $eid     = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM business_content WHERE id=$eid AND agent_id=$aid")->fetch_assoc();
    if (!$editing) { flashSet('error','Content not found.'); redirect(BASE_URL.'/agent/my_content.php'); }
}

// ── Edit (save) ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $eid   = (int)$_POST['edit_id'];
    $title = sanitize($conn, $_POST['title']        ?? '');
    $type  = sanitize($conn, $_POST['content_type'] ?? '');
    $desc  = sanitize($conn, $_POST['description']  ?? '');
    $cat   = (int)($_POST['category_id'] ?? 0);
    $tags  = sanitize($conn, $_POST['tags']         ?? '');
    $stat  = sanitize($conn, $_POST['status']       ?? 'published');

    $cat_val   = $cat > 0 ? $cat : 'NULL';
    $file_set  = '';

    if (!empty($_FILES['file']['name'])) {
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','jpg','jpeg','png'];
        $ext     = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed) && $_FILES['file']['size'] <= 10*1024*1024) {
            $fname = uniqid('content_') . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], dirname(__DIR__) . '/uploads/content/' . $fname)) {
                $file_set = ", file_path='$fname'";
            }
        }
    }

    $conn->query("UPDATE business_content SET
        title='$title', content_type='$type', description='$desc',
        category_id=$cat_val, tags='$tags', status='$stat' $file_set
        WHERE id=$eid AND agent_id=$aid");

    flashSet('success', 'Content updated.');
    redirect(BASE_URL . '/agent/my_content.php');
}

$cats    = $conn->query("SELECT * FROM categories ORDER BY name");
$filter  = sanitize($conn, $_GET['status'] ?? '');
$where   = "WHERE agent_id=$aid";
if ($filter) $where .= " AND status='$filter'";
$content = $conn->query("SELECT bc.*, c.name AS cat_name FROM business_content bc
    LEFT JOIN categories c ON bc.category_id=c.id
    $where ORDER BY bc.created_at DESC");
?>

<!-- Edit form (shown inline when editing) -->
<?php if ($editing): ?>
<div class="card mb-24" style="max-width:780px">
  <div class="card-header">
    <h3>Edit: <?= htmlspecialchars($editing['title']) ?></h3>
    <a href="?" class="btn btn-secondary btn-sm">✕ Cancel</a>
  </div>
  <form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="edit_id" value="<?= $editing['id'] ?>">
    <div class="form-row">
      <div class="form-group">
        <label>Title <span class="required">*</span></label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editing['title']) ?>" required>
      </div>
      <div class="form-group">
        <label>Content Type</label>
        <select name="content_type" class="form-control">
          <?php foreach ($types as $k => $v): ?>
            <option value="<?= $k ?>" <?= $editing['content_type']===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Category</label>
        <select name="category_id" class="form-control">
          <option value="">— None —</option>
          <?php $cats->data_seek(0); while ($c = $cats->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= $editing['category_id']==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status" class="form-control">
          <option value="published" <?= $editing['status']==='published'?'selected':'' ?>>Published</option>
          <option value="draft"     <?= $editing['status']==='draft'    ?'selected':'' ?>>Draft</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label>Description / Content</label>
      <textarea name="description" class="form-control" rows="7"><?= htmlspecialchars($editing['description']) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Tags</label>
        <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($editing['tags']) ?>">
      </div>
      <div class="form-group">
        <label>Replace Attachment</label>
        <input type="file" name="file" class="form-control">
        <?php if ($editing['file_path']): ?>
          <div class="form-hint">Current: <?= htmlspecialchars($editing['file_path']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <button type="submit" name="save_edit" class="btn btn-primary">💾 Save Changes</button>
  </form>
</div>
<?php endif; ?>

<!-- Content list -->
<div class="card">
  <div class="card-header">
    <h3>My Resources</h3>
    <div style="display:flex;gap:10px;align-items:center">
      <a href="?" class="btn btn-sm <?= !$filter?'btn-primary':'btn-secondary' ?>">All</a>
      <a href="?status=published" class="btn btn-sm <?= $filter==='published'?'btn-primary':'btn-secondary' ?>">Published</a>
      <a href="?status=draft"     class="btn btn-sm <?= $filter==='draft'    ?'btn-primary':'btn-secondary' ?>">Drafts</a>
      <a href="<?= $base ?>/agent/upload_content.php" class="btn btn-primary btn-sm">➕ Upload</a>
    </div>
  </div>

  <?php if ($content->num_rows === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">📭</div>
      <h3>No content yet</h3>
      <p>Upload your first business resource to help entrepreneurs.</p>
      <a href="<?= $base ?>/agent/upload_content.php" class="btn btn-primary">Upload Now</a>
    </div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Title</th>
            <th>Type</th>
            <th>Category</th>
            <th>Views</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($r = $content->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($r['title']) ?></strong></td>
            <td><?= contentTypeBadge($r['content_type']) ?></td>
            <td><?= htmlspecialchars($r['cat_name'] ?? '—') ?></td>
            <td>👁 <?= $r['views'] ?></td>
            <td>
              <span class="status-pill <?= $r['status']==='published'?'pill-approved':'pill-pending' ?>">
                <?= ucfirst($r['status']) ?>
              </span>
            </td>
            <td style="white-space:nowrap;color:var(--txt-muted)"><?= timeAgo($r['created_at']) ?></td>
            <td>
              <div class="td-actions">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Delete '<?= htmlspecialchars($r['title']) ?>'? This cannot be undone.">🗑 Delete</a>
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
