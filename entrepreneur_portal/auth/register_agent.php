<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isset($_SESSION['agent_id'])) redirect(BASE_URL . '/agent/dashboard.php');

$errors = [];
$base   = BASE_URL;

// Load categories
$cats = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($conn, $_POST['name']         ?? '');
    $email    = sanitize($conn, $_POST['email']        ?? '');
    $phone    = sanitize($conn, $_POST['phone']        ?? '');
    $bio      = sanitize($conn, $_POST['bio']          ?? '');
    $expertise= sanitize($conn, $_POST['expertise']    ?? '');
    $spec     = sanitize($conn, $_POST['specialization']?? '');
    $exp      = (int)($_POST['experience'] ?? 0);
    $cat      = (int)($_POST['category_id'] ?? 0);
    $linkedin = sanitize($conn, $_POST['linkedin']     ?? '');
    $website  = sanitize($conn, $_POST['website']      ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name))      $errors[] = 'Full name is required.';
    if (empty($email))     $errors[] = 'Email is required.';
    if (!filter_var($_POST['email']??'', FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (empty($expertise)) $errors[] = 'Area of expertise is required.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $check = $conn->query("SELECT id FROM agents WHERE email='$email'");
        if ($check->num_rows > 0) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash    = password_hash($password, PASSWORD_DEFAULT);
            $cat_val = $cat > 0 ? $cat : 'NULL';
            $conn->query("INSERT INTO agents
                (name,email,password,phone,bio,expertise,specialization,experience,category_id,linkedin,website)
                VALUES ('$name','$email','$hash','$phone','$bio','$expertise','$spec',$exp,$cat_val,'$linkedin','$website')");
            flashSet('success', 'Registration submitted! Please wait for admin approval. You will receive login credentials once approved.');
            redirect(BASE_URL . '/auth/login.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register as Agent — Entrepreneur Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="auth-page" style="padding:40px 16px;">
<div class="auth-card" style="max-width:600px;">
  <div class="auth-logo">
    <span class="logo-icon">🎓</span>
    <h1>Join as an Agent</h1>
    <p>Register your profile for admin approval</p>
  </div>

  <div class="notice">📋 Your registration will be reviewed by admin. You'll receive login credentials once approved.</div>

  <?php foreach ($errors as $e): ?>
    <div class="flash flash--error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <form method="POST">
    <div class="form-row">
      <div class="form-group">
        <label>Full Name <span class="required">*</span></label>
        <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Email Address <span class="required">*</span></label>
        <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Phone Number</label>
        <input type="tel" name="phone" class="form-control" placeholder="+92 300 0000000" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
      </div>
      <div class="form-group">
        <label>Years of Experience</label>
        <input type="number" name="experience" class="form-control" min="0" max="50" value="<?= htmlspecialchars($_POST['experience']??'0') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Area of Expertise <span class="required">*</span></label>
        <input type="text" name="expertise" class="form-control" placeholder="e.g. Startup Funding, E-Commerce" value="<?= htmlspecialchars($_POST['expertise']??'') ?>" required>
      </div>
      <div class="form-group">
        <label>Specialization</label>
        <input type="text" name="specialization" class="form-control" placeholder="e.g. SaaS, AgriTech" value="<?= htmlspecialchars($_POST['specialization']??'') ?>">
      </div>
    </div>

    <div class="form-group">
      <label>Business Category</label>
      <select name="category_id" class="form-control">
        <option value="">— Select Category —</option>
        <?php $cats->data_seek(0); while ($cat_row = $cats->fetch_assoc()): ?>
          <option value="<?= $cat_row['id'] ?>" <?= ($_POST['category_id']??'')==$cat_row['id']?'selected':'' ?>>
            <?= htmlspecialchars($cat_row['name']) ?>
          </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div class="form-group">
      <label>Professional Bio</label>
      <textarea name="bio" class="form-control" rows="3" placeholder="Tell entrepreneurs about your background and what you can help with..."><?= htmlspecialchars($_POST['bio']??'') ?></textarea>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>LinkedIn Profile</label>
        <input type="url" name="linkedin" class="form-control" placeholder="https://linkedin.com/in/..." value="<?= htmlspecialchars($_POST['linkedin']??'') ?>">
      </div>
      <div class="form-group">
        <label>Website</label>
        <input type="url" name="website" class="form-control" placeholder="https://yoursite.com" value="<?= htmlspecialchars($_POST['website']??'') ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label>Password <span class="required">*</span></label>
        <input type="password" name="password" class="form-control" placeholder="Min 6 characters" required>
      </div>
      <div class="form-group">
        <label>Confirm Password <span class="required">*</span></label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg">Submit Registration</button>
  </form>

  <hr class="divider">
  <p class="text-center text-muted text-small">
    Already approved? <a href="<?= $base ?>/auth/login.php">Sign In</a>
  </p>
</div>
</body>
</html>
