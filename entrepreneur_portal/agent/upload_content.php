<?php
$page_title = 'Upload Content';
$active_nav = 'upload';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

$errors = [];
$cats   = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title   = sanitize($conn, $_POST['title']        ?? '');
    $type    = sanitize($conn, $_POST['content_type'] ?? '');
    $desc    = sanitize($conn, $_POST['description']  ?? '');
    $cat     = (int)($_POST['category_id'] ?? 0);
    $tags    = sanitize($conn, $_POST['tags']         ?? '');
    $status  = sanitize($conn, $_POST['status']       ?? 'published');

    if (empty($title)) $errors[] = 'Title is required.';
    if (empty($type))  $errors[] = 'Content type is required.';
    if (empty($desc))  $errors[] = 'Description / content is required.';

    $file_path = '';
    if (!empty($_FILES['file']['name'])) {
        $allowed = ['pdf','doc','docx','ppt','pptx','xls','xlsx','txt','jpg','jpeg','png'];
        $ext     = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'File type not allowed. Allowed: ' . implode(', ', $allowed);
        } elseif ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            $errors[] = 'File too large (max 10 MB).';
        } else {
            $file_path = uniqid('content_') . '.' . $ext;
            $dest = dirname(__DIR__) . '/uploads/content/' . $file_path;
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
                $errors[] = 'File upload failed. Check uploads/content/ folder permissions.';
                $file_path = '';
            }
        }
    }

    if (empty($errors)) {
        $cat_val = $cat > 0 ? $cat : 'NULL';
        $conn->query("INSERT INTO business_content
            (agent_id,category_id,title,content_type,description,file_path,tags,status)
            VALUES ($aid,$cat_val,'$title','$type','$desc','$file_path','$tags','$status')");
        flashSet('success', 'Content ' . ($status === 'draft' ? 'saved as draft' : 'published') . ' successfully!');
        redirect(BASE_URL . '/agent/my_content.php');
    }
}

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
?>

<div style="max-width:780px">
  <?php foreach ($errors as $e): ?>
    <div class="flash flash--error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <div class="card">
    <h3 style="margin-bottom:24px">Upload New Resource</h3>
    <form method="POST" enctype="multipart/form-data">

      <div class="form-row">
        <div class="form-group">
          <label>Title <span class="required">*</span></label>
          <input type="text" name="title" class="form-control"
                 placeholder="Resource title" value="<?= htmlspecialchars($_POST['title']??'') ?>" required>
        </div>
        <div class="form-group">
          <label>Content Type <span class="required">*</span></label>
          <select name="content_type" class="form-control" required>
            <option value="">— Select Type —</option>
            <?php foreach ($types as $k => $v): ?>
              <option value="<?= $k ?>" <?= ($_POST['content_type']??'')===$k?'selected':'' ?>><?= $v ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Business Category</label>
        <select name="category_id" class="form-control">
          <option value="">— Select Category —</option>
          <?php while ($c = $cats->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= ($_POST['category_id']??'')==$c['id']?'selected':'' ?>>
              <?= htmlspecialchars($c['name']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Description / Content <span class="required">*</span></label>
        <textarea name="description" class="form-control" rows="8"
                  placeholder="Provide detailed content, ideas, instructions, or information..." required><?= htmlspecialchars($_POST['description']??'') ?></textarea>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label>Tags <span class="text-muted text-small">(comma separated)</span></label>
          <input type="text" name="tags" class="form-control"
                 placeholder="e.g. startup, funding, SaaS" value="<?= htmlspecialchars($_POST['tags']??'') ?>">
        </div>
        <div class="form-group">
          <label>Visibility</label>
          <select name="status" class="form-control">
            <option value="published" <?= ($_POST['status']??'')==='published'?'selected':'' ?>>Published (visible to users)</option>
            <option value="draft"     <?= ($_POST['status']??'')==='draft'    ?'selected':'' ?>>Save as Draft</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Attachment <span class="text-muted text-small">(optional — PDF, DOC, PPT, XLS, images — max 10MB)</span></label>
        <input type="file" name="file" class="form-control"
               accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.jpg,.jpeg,.png">
      </div>

      <div style="display:flex;gap:12px;margin-top:8px">
        <button type="submit" class="btn btn-primary">🚀 Publish Resource</button>
        <button type="submit" name="status" value="draft" class="btn btn-secondary"
                onclick="document.querySelector('[name=status]').value='draft'">💾 Save Draft</button>
        <a href="<?= $base ?>/agent/my_content.php" class="btn btn-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
