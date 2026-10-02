<?php
$page_title = 'My Profile';
$active_nav = 'profile';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();
$admin_id = $_SESSION['admin_id'];
$errors   = [];

$admin = $conn->query("SELECT * FROM admins WHERE id=$admin_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($conn, $_POST['name'] ?? '');
    if (empty($name)) $errors[] = 'Name is required.';

    $pic_set = '';
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $ext     = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Image must be JPG, PNG, GIF or WEBP.';
        } elseif ($_FILES['profile_pic']['size'] > 3*1024*1024) {
            $errors[] = 'Image too large (max 3MB).';
        } else {
            $dir   = dirname(__DIR__) . '/uploads/profiles/';
            if (!is_dir($dir)) mkdir($dir, 0777, true);
            $fname = 'admin_' . $admin_id . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dir . $fname)) {
                if (!empty($admin['profile_pic'])) @unlink($dir . $admin['profile_pic']);
                $pic_set = ", profile_pic='$fname'";
            } else { $errors[] = 'Upload failed.'; }
        }
    }

    $pw_set = '';
    if (!empty($_POST['new_password'])) {
        if (!password_verify($_POST['current_password'] ?? '', $admin['password']))
            $errors[] = 'Current password is incorrect.';
        elseif (strlen($_POST['new_password']) < 6)
            $errors[] = 'New password must be at least 6 characters.';
        elseif ($_POST['new_password'] !== $_POST['confirm_password'])
            $errors[] = 'New passwords do not match.';
        else {
            $hash   = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pw_set = ", password='$hash'";
        }
    }

    if (empty($errors)) {
        $conn->query("UPDATE admins SET name='$name' $pic_set $pw_set WHERE id=$admin_id");
        $_SESSION['admin_name'] = $name;
        flashSet('success', 'Profile updated!');
        redirect(BASE_URL . '/admin/profile.php');
    }
    $admin['name'] = $name;
}

$pic_path = dirname(__DIR__) . '/uploads/profiles/' . ($admin['profile_pic'] ?? '');
$has_pic  = !empty($admin['profile_pic']) && file_exists($pic_path);
$pic_url  = $has_pic ? $base . '/uploads/profiles/' . htmlspecialchars($admin['profile_pic']) : '';
?>

<?php foreach ($errors as $e): ?>
  <div class="flash flash--error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<div class="card mb-24">
  <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap">
    <?php if ($has_pic): ?>
      <img src="<?= $pic_url ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #f59e0b">
    <?php else: ?>
      <?= initialsAvatar($admin['name'], 100) ?>
    <?php endif; ?>
    <div>
      <h2 style="margin-bottom:4px"><?= htmlspecialchars($admin['name']) ?></h2>
      <span class="status-pill" style="background:rgba(245,158,11,.15);color:#f59e0b">Administrator</span>
      <div style="color:var(--txt-secondary);font-size:.85rem;margin-top:8px"><?= htmlspecialchars($admin['email']) ?></div>
    </div>
  </div>
</div>

<div class="card" style="max-width:680px">
  <h3 style="margin-bottom:24px">Edit Profile</h3>
  <form method="POST" enctype="multipart/form-data">
    <div class="form-group">
      <label>Full Name <span class="required">*</span></label>
      <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($admin['name']) ?>" required>
    </div>
    <div class="form-group">
      <label>Profile Picture <span class="text-muted text-small">(JPG, PNG, WEBP — max 3MB)</span></label>
      <?php if ($has_pic): ?>
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
          <img src="<?= $pic_url ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
          <span class="text-small text-muted">Upload new to replace</span>
        </div>
      <?php endif; ?>
      <input type="file" name="profile_pic" class="form-control" accept="image/*">
    </div>

    <hr class="divider">
    <h4 style="margin-bottom:16px">Change Password</h4>
    <div class="form-group">
      <label>Current Password</label>
      <input type="password" name="current_password" class="form-control" placeholder="Enter current password">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password">
      </div>
    </div>
    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
