<?php
$page_title = 'Manage Content';
$active_nav = 'content';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();

$types = [
    'business_idea'   => 'Business Idea',
    'template'        => 'Template',
    'framework'       => 'Framework',
    'market_research' => 'Market Research',
    'startup_plan'    => 'Startup Plan',
    'partnership'     => 'Partnership',
    'financial'       => 'Financial',
    'opportunity'     => 'Opportunity',
    'other'           => 'Other',
];

// ── Delete ────────────────────────────────────────────────────
if (isset($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $conn->query("SELECT file_path FROM business_content WHERE id=$id")->fetch_assoc();
    if ($row) {
        if ($row['file_path']) @unlink(dirname(__DIR__) . '/uploads/content/' . $row['file_path']);
        $conn->query("DELETE FROM business_content WHERE id=$id");
        flashSet('success', 'Content deleted.');
    }
    redirect(BASE_URL . '/admin/manage_content.php');
}

// ── Edit save ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_edit'])) {
    $eid   = (int)$_POST['edit_id'];
    $title = sanitize($conn, $_POST['title']        ?? '');
    $type  = sanitize($conn, $_POST['content_type'] ?? '');
    $desc  = sanitize($conn, $_POST['description']  ?? '');
    $cat   = (int)($_POST['category_id'] ?? 0);
    $tags  = sanitize($conn, $_POST['tags']         ?? '');
    $stat  = sanitize($conn, $_POST['status']       ?? 'published');
    $cat_v = $cat > 0 ? $cat : 'NULL';

    $file_set = '';
    if (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','jpg','jpeg','png'];
        if (in_array($ext, $allowed) && $_FILES['file']['size'] <= 10*1024*1024) {
            $fname = uniqid('content_') . '.' . $ext;
            if (move_uploaded_file($_FILES['file']['tmp_name'], dirname(__DIR__) . '/uploads/content/' . $fname)) {
                $file_set = ", file_path='$fname'";
            }
        }
    }

    $conn->query("UPDATE business_content SET title='$title', content_type='$type', description='$desc',
        category_id=$cat_v, tags='$tags', status='$stat' $file_set WHERE id=$eid");
    flashSet('success', 'Content updated.');
    redirect(BASE_URL . '/admin/manage_content.php');
}

// ── Admin upload ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_upload'])) {
    $title = sanitize($conn, $_POST['title']        ?? '');
    $type  = sanitize($conn, $_POST['content_type'] ?? '');
    $desc  = sanitize($conn, $_POST['description']  ?? '');
    $cat   = (int)($_POST['category_id'] ?? 0);
    $tags  = sanitize($conn, $_POST['tags']         ?? '');
    $aid_p = (int)($_POST['agent_id']    ?? 0);
    $cat_v = $cat > 0 ? $cat : 'NULL';
    $file_path = '';

    if (!empty($_FILES['file']['name'])) {
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','jpg','jpeg','png'];
        if (in_array($ext, $allowed) && $_FILES['file']['size'] <= 10*1024*1024) {
            $file_path = uniqid('content_') . '.' . $ext;
            move_uploaded_file($_FILES['file']['tmp_name'], dirname(__DIR__) . '/uploads/content/' . $file_path);
        }
    }

    if ($title && $type && $aid_p) {
        $conn->query("INSERT INTO business_content (agent_id,category_id,title,content_type,description,file_path,tags,status)
            VALUES ($aid_p,$cat_v,'$title','$type','$desc','$file_path','$tags','published')");
        flashSet('success', 'Content published by admin.');
        redirect(BASE_URL . '/admin/manage_content.php');
    }
}

// ── Edit load ─────────────────────────────────────────────────
$editing = null;
if (isset($_GET['edit'])) {
    $eid     = (int)$_GET['edit'];
    $editing = $conn->query("SELECT * FROM business_content WHERE id=$eid")->fetch_assoc();
}

$search = sanitize($conn, $_GET['q']    ?? '');
$cat_f  = (int)($_GET['category']       ?? 0);
$type_f = sanitize($conn, $_GET['type'] ?? '');

$where  = 'WHERE 1=1';
if ($search) $where .= " AND (bc.title LIKE '%$search%' OR a.name LIKE '%$search%')";
if ($cat_f)  $where .= " AND bc.category_id=$cat_f";
if ($type_f) $where .= " AND bc.content_type='$type_f'";

$content = $conn->query("SELECT bc.*, a.name AS agent_name, c.name AS cat_name
    FROM business_content bc
    LEFT JOIN agents a ON bc.agent_id=a.id
    LEFT JOIN categories c ON bc.category_id=c.id
    $where ORDER BY bc.created_at DESC");

$cats   = $conn->query("SELECT * FROM categories ORDER BY name");
$agents = $conn->query("SELECT id,name FROM agents WHERE approval_status='approved' ORDER BY name");
?>

<!-- Edit form -->
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
        <label>Title</label>
        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($editing['title']) ?>" required>
      </div>
      <div class="form-group">
        <label>Type</label>
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
      <label>Description</label>
      <textarea name="description" class="form-control" rows="6"><?= htmlspecialchars($editing['description']) ?></textarea>
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>Tags</label>
        <input type="text" name="tags" class="form-control" value="<?= htmlspecialchars($editing['tags']) ?>">
      </div>
      <div class="form-group">
        <label>Replace File</label>
        <input type="file" name="file" class="form-control">
      </div>
    </div>
    <button type="submit" name="save_edit" class="btn btn-primary">💾 Save</button>
  </form>
</div>
<?php endif; ?>

<!-- Admin upload -->
<div class="card mb-24">
  <div class="card-header">
    <h3>Admin Upload Content</h3>
    <button class="btn btn-outline btn-sm" onclick="this.closest('.card').querySelector('.upload-form').classList.toggle('hidden')">Toggle ▾</button>
  </div>
  <div class="upload-form hidden">
    <form method="POST" enctype="multipart/form-data" style="margin-top:16px">
      <div class="form-row">
        <div class="form-group">
          <label>Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Assign to Agent <span class="required">*</span></label>
          <select name="agent_id" class="form-control" required>
            <option value="">— Select Agent —</option>
            <?php $agents->data_seek(0); while ($ag = $agents->fetch_assoc()): ?>
              <option value="<?= $ag['id'] ?>"><?= htmlspecialchars($ag['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Type</label>
          <select name="content_type" class="form-control">
            <?php foreach ($types as $k => $v): ?>
              <option value="<?= $k ?>"><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Category</label>
          <select name="category_id" class="form-control">
            <option value="">— None —</option>
            <?php $cats->data_seek(0); while ($c = $cats->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div class="form-group">
        <label>Description</label>
        <textarea name="description" class="form-control" rows="4"></textarea>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Tags</label>
          <input type="text" name="tags" class="form-control" placeholder="comma separated">
        </div>
        <div class="form-group">
          <label>Attachment</label>
          <input type="file" name="file" class="form-control">
        </div>
      </div>
      <button type="submit" name="admin_upload" class="btn btn-primary">🚀 Publish</button>
    </form>
  </div>
</div>

<!-- Content list -->
<div class="card">
  <div class="card-header">
    <h3>All Content (<?= $content->num_rows ?>)</h3>
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
      <div class="search-bar" style="min-width:160px">
        <span class="search-icon">🔍</span>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search...">
      </div>
      <select name="type" class="form-control" style="width:auto;min-width:140px">
        <option value="">All Types</option>
        <?php foreach ($types as $k => $v): ?>
          <option value="<?= $k ?>" <?= $type_f===$k?'selected':'' ?>><?= $v ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
      <?php if ($search || $cat_f || $type_f): ?><a href="?" class="btn btn-secondary btn-sm">Clear</a><?php endif; ?>
    </form>
  </div>

  <?php if ($content->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">📭</div><h3>No content found</h3></div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Title</th><th>Type</th><th>Agent</th><th>Category</th><th>Views</th><th>Status</th><th>Date</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php while ($r = $content->fetch_assoc()): ?>
          <tr>
            <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
              <strong title="<?= htmlspecialchars($r['title']) ?>"><?= htmlspecialchars($r['title']) ?></strong>
            </td>
            <td><?= contentTypeBadge($r['content_type']) ?></td>
            <td><?= htmlspecialchars($r['agent_name']) ?></td>
            <td><?= htmlspecialchars($r['cat_name'] ?? '—') ?></td>
            <td>👁 <?= $r['views'] ?></td>
            <td><span class="status-pill <?= $r['status']==='published'?'pill-approved':'pill-pending' ?>"><?= ucfirst($r['status']) ?></span></td>
            <td style="white-space:nowrap;color:var(--txt-muted)"><?= timeAgo($r['created_at']) ?></td>
            <td>
              <div class="td-actions">
                <a href="?edit=<?= $r['id'] ?>" class="btn btn-secondary btn-sm">✏️ Edit</a>
                <a href="?delete=<?= $r['id'] ?>" class="btn btn-danger btn-sm"
                   data-confirm="Delete '<?= htmlspecialchars($r['title']) ?>'?">🗑 Delete</a>
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
