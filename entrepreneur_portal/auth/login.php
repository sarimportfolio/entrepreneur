<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

if (isset($_SESSION['user_id']))  redirect(BASE_URL . '/user/dashboard.php');
if (isset($_SESSION['agent_id'])) redirect(BASE_URL . '/agent/dashboard.php');
if (isset($_SESSION['admin_id'])) redirect(BASE_URL . '/admin/dashboard.php');

$error = '';
$base  = BASE_URL;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitize($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? 'user';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        if ($role === 'admin') {
            $res = $conn->query("SELECT * FROM admins WHERE email='$email' LIMIT 1");
            $row = $res->fetch_assoc();
            if ($row && password_verify($password, $row['password'])) {
                $_SESSION['admin_id']   = $row['id'];
                $_SESSION['admin_name'] = $row['name'];
                redirect(BASE_URL . '/admin/dashboard.php');
            } else {
                $error = 'Invalid admin credentials.';
            }
        } elseif ($role === 'agent') {
            $res = $conn->query("SELECT * FROM agents WHERE email='$email' LIMIT 1");
            $row = $res->fetch_assoc();
            if (!$row) {
                $error = 'No agent account found with this email.';
            } elseif ($row['approval_status'] !== 'approved') {
                $error = 'Your account is pending admin approval. Please wait.';
            } elseif ($row['account_status'] === 'suspended') {
                $error = 'Your account has been suspended. Contact admin.';
            } elseif (!password_verify($password, $row['password'])) {
                $error = 'Incorrect password.';
            } else {
                $_SESSION['agent_id']   = $row['id'];
                $_SESSION['agent_name'] = $row['name'];
                redirect(BASE_URL . '/agent/dashboard.php');
            }
        } else {
            $res = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
            $row = $res->fetch_assoc();
            if (!$row) {
                $error = 'No account found with this email.';
            } elseif ($row['status'] === 'suspended') {
                $error = 'Your account has been suspended.';
            } elseif (!password_verify($password, $row['password'])) {
                $error = 'Incorrect password.';
            } else {
                $_SESSION['user_id']   = $row['id'];
                $_SESSION['user_name'] = $row['name'];
                redirect(BASE_URL . '/user/dashboard.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login — Entrepreneur Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-icon">🚀</span>
    <h1>Entrepreneur Portal</h1>
    <p>Sign in to your account</p>
  </div>

  <?php if ($error): ?>
    <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <!-- Role selector tabs -->
    <div class="auth-tabs">
      <button type="button" class="auth-tab <?= ($_POST['role']??'user')==='user'?'active':'' ?>" onclick="setRole('user',this)">User</button>
      <button type="button" class="auth-tab <?= ($_POST['role']??'')==='agent'?'active':'' ?>" onclick="setRole('agent',this)">Agent</button>
      <button type="button" class="auth-tab <?= ($_POST['role']??'')==='admin'?'active':'' ?>" onclick="setRole('admin',this)">Admin</button>
    </div>
    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($_POST['role']??'user') ?>">

    <div class="form-group">
      <label>Email Address <span class="required">*</span></label>
      <input type="email" name="email" class="form-control" placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email']??'') ?>" required>
    </div>

    <div class="form-group">
      <label>Password <span class="required">*</span></label>
      <input type="password" name="password" class="form-control" placeholder="••••••••" required>
    </div>

    <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">Sign In</button>
  </form>

  <div style="text-align:right;margin-top:10px">
    <a href="<?= $base ?>/auth/forgot_password.php" class="text-small" style="color:var(--txt-secondary)">Forgot password?</a>
  </div>

  <hr class="divider">

  <p class="text-center text-muted text-small">
    Don't have an account?
    <a href="<?= $base ?>/auth/register_user.php">Register as User</a> or
    <a href="<?= $base ?>/auth/register_agent.php">Register as Agent</a>
  </p>
  <p class="text-center mt-12"><a href="<?= $base ?>/" style="color:var(--txt-muted);font-size:.85rem;">← Back to Home</a></p>
</div>

<script>
function setRole(role, btn) {
  document.getElementById('roleInput').value = role;
  document.querySelectorAll('.auth-tab').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
}
</script>
</body>
</html>
