<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$base  = BASE_URL;
$step  = $_POST['step'] ?? $_GET['step'] ?? 1;
$role  = sanitize($conn, $_POST['role'] ?? $_GET['role'] ?? 'user');
$error = '';

$questions = [
    'What is your mother\'s maiden name?',
    'What was the name of your first pet?',
    'What city were you born in?',
    'What is your oldest sibling\'s name?',
    'What was the name of your primary school?',
];

// ── Step 1: Enter email ──────────────────────────────────────
// ── Step 2: Answer security question ────────────────────────
// ── Step 3: Set new password ─────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($conn, $_POST['email'] ?? '');

    if ($step == 1) {
        // Find account
        $table = $role === 'agent' ? 'agents' : 'users';
        $extra = $role === 'agent' ? "AND approval_status='approved'" : '';
        $row   = $conn->query("SELECT * FROM $table WHERE email='$email' $extra LIMIT 1")->fetch_assoc();

        if (!$row) {
            $error = 'No account found with this email.';
            $step  = 1;
        } elseif (empty($row['security_q'])) {
            $error = 'No security question set for this account. Please contact admin.';
            $step  = 1;
        } else {
            $_SESSION['fp_email'] = $email;
            $_SESSION['fp_role']  = $role;
            $step = 2;
        }

    } elseif ($step == 2) {
        $email  = $_SESSION['fp_email'] ?? '';
        $role   = $_SESSION['fp_role']  ?? 'user';
        $answer = strtolower(trim($_POST['answer'] ?? ''));
        $table  = $role === 'agent' ? 'agents' : 'users';
        $row    = $conn->query("SELECT * FROM $table WHERE email='$email' LIMIT 1")->fetch_assoc();

        if (!$row || strtolower(trim($row['security_a'])) !== $answer) {
            $error = 'Incorrect answer. Please try again.';
            $step  = 2;
        } else {
            $_SESSION['fp_verified'] = true;
            $step = 3;
        }

    } elseif ($step == 3) {
        if (empty($_SESSION['fp_verified'])) {
            redirect(BASE_URL . '/auth/forgot_password.php');
        }
        $email   = $_SESSION['fp_email'] ?? '';
        $role    = $_SESSION['fp_role']  ?? 'user';
        $newpass = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $table   = $role === 'agent' ? 'agents' : 'users';

        if (strlen($newpass) < 6) {
            $error = 'Password must be at least 6 characters.';
            $step  = 3;
        } elseif ($newpass !== $confirm) {
            $error = 'Passwords do not match.';
            $step  = 3;
        } else {
            $hash = password_hash($newpass, PASSWORD_DEFAULT);
            $conn->query("UPDATE $table SET password='$hash' WHERE email='$email'");
            unset($_SESSION['fp_email'], $_SESSION['fp_role'], $_SESSION['fp_verified']);
            flashSet('success', 'Password changed successfully! Please log in.');
            redirect(BASE_URL . '/auth/login.php');
        }
    }
}

// Load question for step 2
$current_q = '';
if ($step == 2 && isset($_SESSION['fp_email'])) {
    $email  = $_SESSION['fp_email'];
    $role   = $_SESSION['fp_role'] ?? 'user';
    $table  = $role === 'agent' ? 'agents' : 'users';
    $row    = $conn->query("SELECT security_q FROM $table WHERE email='$email' LIMIT 1")->fetch_assoc();
    $current_q = $row['security_q'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password — Entrepreneur Portal</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
<link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🚀</text></svg>">
</head>
<body class="auth-page">
<div class="auth-card">
  <div class="auth-logo">
    <span class="logo-icon">🔑</span>
    <h1>Reset Password</h1>
    <p>
      <?php if ($step==1): ?>Step 1 of 3: Enter your email
      <?php elseif ($step==2): ?>Step 2 of 3: Answer security question
      <?php else: ?>Step 3 of 3: Set new password
      <?php endif; ?>
    </p>
  </div>

  <!-- Progress -->
  <div style="display:flex;gap:4px;margin-bottom:24px">
    <?php for ($i=1;$i<=3;$i++): ?>
      <div style="flex:1;height:4px;border-radius:99px;background:<?= $i<=$step?'var(--clr-accent)':'var(--clr-border)' ?>"></div>
    <?php endfor; ?>
  </div>

  <?php if ($error): ?>
    <div class="flash flash--error"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($step == 1): ?>
  <form method="POST">
    <input type="hidden" name="step" value="1">
    <div class="form-group">
      <label>Account Type</label>
      <div class="auth-tabs" style="margin-bottom:18px">
        <button type="button" class="auth-tab <?= $role==='user'?'active':'' ?>" onclick="setRole('user',this)">User</button>
        <button type="button" class="auth-tab <?= $role==='agent'?'active':'' ?>" onclick="setRole('agent',this)">Agent</button>
      </div>
      <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($role) ?>">
    </div>
    <div class="form-group">
      <label>Email Address <span class="required">*</span></label>
      <input type="email" name="email" class="form-control" placeholder="Your registered email" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Continue →</button>
  </form>

  <?php elseif ($step == 2): ?>
  <form method="POST">
    <input type="hidden" name="step"  value="2">
    <input type="hidden" name="email" value="<?= htmlspecialchars($_SESSION['fp_email'] ?? '') ?>">
    <div class="form-group">
      <label>Security Question</label>
      <div style="background:var(--clr-surface2);padding:12px 16px;border-radius:var(--radius-sm);color:var(--txt-primary);font-weight:500;margin-bottom:4px">
        <?= htmlspecialchars($current_q) ?>
      </div>
    </div>
    <div class="form-group">
      <label>Your Answer <span class="required">*</span></label>
      <input type="text" name="answer" class="form-control" placeholder="Your answer (case-insensitive)" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Verify →</button>
  </form>

  <?php else: ?>
  <form method="POST">
    <input type="hidden" name="step" value="3">
    <div class="form-group">
      <label>New Password <span class="required">*</span></label>
      <input type="password" name="new_password" class="form-control" placeholder="Min 6 characters" required>
    </div>
    <div class="form-group">
      <label>Confirm Password <span class="required">*</span></label>
      <input type="password" name="confirm_password" class="form-control" placeholder="Repeat new password" required>
    </div>
    <button type="submit" class="btn btn-primary btn-block">Set New Password</button>
  </form>
  <?php endif; ?>

  <hr class="divider">
  <p class="text-center text-small text-muted">
    Remember it? <a href="<?= $base ?>/auth/login.php">Back to Login</a>
  </p>
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
