<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isset($_SESSION['user_id'])) redirect(BASE_URL . '/user/dashboard.php');

$errors = [];
$base   = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = sanitize($conn, $_POST['name']     ?? '');
    $email    = sanitize($conn, $_POST['email']    ?? '');
    $phone    = sanitize($conn, $_POST['phone']    ?? '');
    $password = $_POST['password']  ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name))     $errors[] = 'Full name is required.';
    if (empty($email))    $errors[] = 'Email is required.';
    if (!filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
    if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        // Check duplicate email
        $check = $conn->query("SELECT id FROM users WHERE email='$email'");
        if ($check->num_rows > 0) {
            $errors[] = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $sec_q = sanitize($conn, $_POST['security_q'] ?? '');
            $sec_a = sanitize($conn, strtolower(trim($_POST['security_a'] ?? '')));
            $conn->query("INSERT INTO users (name,email,password,phone,security_q,security_a) VALUES ('$name','$email','$hash','$phone','$sec_q','$sec_a')");
            flashSet('success', 'Account created! Please log in.');
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
<title>Register — Entrepreneur Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-icon">🚀</span>
    <h1>Create Account</h1>
    <p>Join as an aspiring entrepreneur</p>
  </div>

  <?php foreach ($errors as $e): ?>
    <div class="flash flash--error"><?= htmlspecialchars($e) ?></div>
  <?php endforeach; ?>

  <form method="POST">
    <div class="form-group">
      <label>Full Name <span class="required">*</span></label>
      <input type="text" name="name" class="form-control" placeholder="Your full name" value="<?= htmlspecialchars($_POST['name']??'') ?>" required>
    </div>

    <div class="form-group">
      <label>Email Address <span class="required">*</span></label>
      <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
    </div>

    <div class="form-group">
      <label>Phone Number</label>
      <input type="tel" name="phone" class="form-control" placeholder="+92 300 0000000" value="<?= htmlspecialchars($_POST['phone']??'') ?>">
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

    <hr class="divider">
    <p class="text-small text-muted mb-12">🔑 Set a security question for password recovery</p>

    <div class="form-group">
      <label>Security Question <span class="required">*</span></label>
      <select name="security_q" class="form-control" required>
        <option value="">— Choose a question —</option>
        <option value="What is your mother's maiden name?">What is your mother's maiden name?</option>
        <option value="What was the name of your first pet?">What was the name of your first pet?</option>
        <option value="What city were you born in?">What city were you born in?</option>
        <option value="What is your oldest sibling's name?">What is your oldest sibling's name?</option>
        <option value="What was the name of your primary school?">What was the name of your primary school?</option>
      </select>
    </div>
    <div class="form-group">
      <label>Your Answer <span class="required">*</span></label>
      <input type="text" name="security_a" class="form-control" placeholder="Remember this answer exactly" required>
      <div class="form-hint">Answer is saved in lowercase. You can enter it in any case when recovering.</div>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:4px;">Create Account</button>
  </form>

  <hr class="divider">
  <p class="text-center text-muted text-small">
    Already have an account? <a href="<?= $base ?>/auth/login.php">Sign In</a>
  </p>
  <p class="text-center text-small mt-8">
    Want to join as a agent? <a href="<?= $base ?>/auth/register_agent.php">Register as Agent</a>
  </p>
</div>
</body>
</html>
