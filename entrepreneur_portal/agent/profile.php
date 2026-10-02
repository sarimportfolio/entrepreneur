<?php
$page_title = 'My Profile';
$active_nav = 'profile';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

$agent  = $conn->query("SELECT a.*, c.name AS cat_name FROM agents a
    LEFT JOIN categories c ON a.category_id=c.id
    WHERE a.id=$aid")->fetch_assoc();
$cats   = $conn->query("SELECT * FROM categories ORDER BY name");
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = sanitize($conn, $_POST['name']           ?? '');
    $phone   = sanitize($conn, $_POST['phone']          ?? '');
    $bio     = sanitize($conn, $_POST['bio']            ?? '');
    $expert  = sanitize($conn, $_POST['expertise']      ?? '');
    $spec    = sanitize($conn, $_POST['specialization'] ?? '');
    $exp     = (int)($_POST['experience']               ?? 0);
    $cat     = (int)($_POST['category_id']              ?? 0);
    $linkedin= sanitize($conn, $_POST['linkedin']       ?? '');
    $website = sanitize($conn, $_POST['website']        ?? '');

    if (empty($name)) $errors[] = 'Name is required.';

    // ── Profile picture upload ────────────────────────────────
    $pic_set = '';
    if (!empty($_FILES['profile_pic']['name']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['jpg','jpeg','png','gif','webp'];
        $ext     = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed)) {
            $errors[] = 'Profile picture must be JPG, PNG, GIF or WEBP.';
        } elseif ($_FILES['profile_pic']['size'] > 3*1024*1024) {
            $errors[] = 'Profile picture too large (max 3 MB).';
        } else {
            $upload_dir = dirname(__DIR__) . '/uploads/profiles/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $fname = 'agent_' . $aid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $fname)) {
                // Remove old pic
                if (!empty($agent['profile_pic']) && $agent['profile_pic'] !== 'default.png') {
                    @unlink($upload_dir . $agent['profile_pic']);
                }
                $pic_set = ", profile_pic='$fname'";
            } else {
                $errors[] = 'Upload failed. Check that uploads/profiles/ folder exists and is writable.';
            }
        }
    }

    // ── Password change ───────────────────────────────────────
    $pw_set = '';
    if (!empty($_POST['new_password'])) {
        if (strlen($_POST['new_password']) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($_POST['new_password'] !== $_POST['confirm_password']) {
            $errors[] = 'Passwords do not match.';
        } else {
            $hash   = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $pw_set = ", password='$hash'";
        }
    }

    if (empty($errors)) {
        $cat_val = $cat > 0 ? $cat : 'NULL';
        $conn->query("UPDATE agents SET
            name='$name', phone='$phone', bio='$bio', expertise='$expert',
            specialization='$spec', experience=$exp, category_id=$cat_val,
            linkedin='$linkedin', website='$website' $pic_set $pw_set
            WHERE id=$aid");
        $_SESSION['agent_name'] = $name;
        flashSet('success', 'Profile updated successfully!');
        redirect(BASE_URL . '/agent/profile.php');
    }

    // Re-merge for re-display
    $agent = array_merge($agent, [
        'name'=>$name,'phone'=>$phone,'bio'=>$bio,'expertise'=>$expert,
        'specialization'=>$spec,'experience'=>$exp,'category_id'=>$cat,
        'linkedin'=>$linkedin,'website'=>$website,
    ]);
}

$avg      = getAvgRating($conn, $aid);
$fb_count = $conn->query("SELECT COUNT(*) AS c FROM feedback WHERE agent_id=$aid")->fetch_assoc()['c'];

// Check if a real profile pic exists on disk
$pic_path    = dirname(__DIR__) . '/uploads/profiles/' . ($agent['profile_pic'] ?? '');
$has_pic     = !empty($agent['profile_pic']) && $agent['profile_pic'] !== 'default.png' && file_exists($pic_path);
$pic_url     = $has_pic ? BASE_URL . '/uploads/profiles/' . htmlspecialchars($agent['profile_pic']) : '';
?>

<?php foreach ($errors as $e): ?>
  <div class="flash flash--error"><?= htmlspecialchars($e) ?></div>
<?php endforeach; ?>

<!-- Profile preview -->
<div class="card mb-24">
  <div class="profile-header">
    <div>
      <?php if ($has_pic): ?>
        <img src="<?= $pic_url ?>" alt="Profile"
             style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--clr-accent)">
      <?php else: ?>
        <?= initialsAvatar($agent['name'], 100) ?>
      <?php endif; ?>
    </div>
    <div class="profile-info">
      <h2><?= htmlspecialchars($agent['name']) ?></h2>
      <div class="expertise"><?= htmlspecialchars($agent['expertise'] ?? '') ?></div>
      <?php if ($avg): ?>
        <div><?= ratingStars(round($avg)) ?> <span class="text-small text-muted"><?= $avg ?>/5 (<?= $fb_count ?> reviews)</span></div>
      <?php endif; ?>
      <div class="meta" style="margin-top:8px">
        <span class="status-pill pill-approved">✓ Approved Agent</span>
        <?php if ($agent['cat_name']): ?><span>📂 <?= htmlspecialchars($agent['cat_name']) ?></span><?php endif; ?>
        <?php if ($agent['experience']): ?><span>⏱ <?= $agent['experience'] ?> yrs</span><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Edit form -->
<div class="card" style="max-width:780px">
  <h3 style="margin-bottom:24px">Edit Profile</h3>
  <form method="POST" enctype="multipart/form-data">

    <div class="form-row">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($agent['name']) ?>" required>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($agent['phone'] ?? '') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Area of Expertise</label>
        <input type="text" name="expertise" class="form-control" value="<?= htmlspecialchars($agent['expertise'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label>Specialization</label>
        <input type="text" name="specialization" class="form-control" value="<?= htmlspecialchars($agent['specialization'] ?? '') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Years of Experience</label>
        <input type="number" name="experience" class="form-control" min="0" max="60" value="<?= (int)($agent['experience'] ?? 0) ?>">
      </div>
      <div class="form-group">
        <label>Business Category</label>
        <select name="category_id" class="form-control">
          <option value="">— None —</option>
          <?php while ($c = $cats->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>" <?= ($agent['category_id']==$c['id'])?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>

    <div class="form-group">
      <label>Professional Bio</label>
      <textarea name="bio" class="form-control" rows="5"><?= htmlspecialchars($agent['bio'] ?? '') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>LinkedIn</label>
        <input type="url" name="linkedin" class="form-control" value="<?= htmlspecialchars($agent['linkedin'] ?? '') ?>" placeholder="https://linkedin.com/in/...">
      </div>
      <div class="form-group">
        <label>Website</label>
        <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($agent['website'] ?? '') ?>" placeholder="https://yoursite.com">
      </div>
    </div>

    <div class="form-group">
      <label>Profile Picture <span class="text-muted text-small">(JPG, PNG, WEBP — max 3MB)</span></label>
      <?php if ($has_pic): ?>
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
          <img src="<?= $pic_url ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover">
          <span class="text-small text-muted">Current picture — upload new to replace</span>
        </div>
      <?php else: ?>
        <div style="margin-bottom:8px;display:flex;align-items:center;gap:10px">
          <?= initialsAvatar($agent['name'], 48) ?>
          <span class="text-small text-muted">No picture — showing initials. Upload to add one.</span>
        </div>
      <?php endif; ?>
      <input type="file" name="profile_pic" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
    </div>

    <hr class="divider">
    <h4 style="margin-bottom:16px">Change Password <span class="text-small text-muted">(leave blank to keep current)</span></h4>
    <div class="form-row">
      <div class="form-group">
        <label>New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters">
      </div>
      <div class="form-group">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password">
      </div>
    </div>

    <button type="submit" class="btn btn-primary">💾 Save Profile</button>
  </form>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
