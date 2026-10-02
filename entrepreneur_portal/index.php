<?php
session_start();
require_once 'config/db.php';

// Redirect already logged-in users
if (isset($_SESSION['admin_id'])) header('Location: ' . BASE_URL . '/admin/dashboard.php');
if (isset($_SESSION['agent_id'])) header('Location: ' . BASE_URL . '/agent/dashboard.php');
if (isset($_SESSION['user_id']))  header('Location: ' . BASE_URL . '/user/dashboard.php');

$base = BASE_URL;

// Counts for display
$users_count   = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$agents_count  = $conn->query("SELECT COUNT(*) AS c FROM agents WHERE approval_status='approved'")->fetch_assoc()['c'];
$content_count = $conn->query("SELECT COUNT(*) AS c FROM business_content")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Entrepreneur Portal — Your Startup Journey Starts Here</title>
<link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body class="landing">

<nav class="landing-nav">
  <div class="logo">🚀 <span>Entrepreneur Portal</span></div>
  <div class="nav-links">
    <a href="<?= $base ?>/auth/login.php" class="btn btn-secondary btn-sm">Log In</a>
    <a href="<?= $base ?>/auth/register_user.php" class="btn btn-primary btn-sm">Get Started</a>
  </div>
</nav>

<section class="hero">
  <div class="hero-badge">✨ Free platform for aspiring entrepreneurs</div>
  <h1>Turn Your Business Idea Into Reality</h1>
  <p>Connect with expert agents, access curated business resources, and join a thriving community of entrepreneurs — all in one place.</p>
  <div class="hero-btns">
    <a href="<?= $base ?>/auth/register_user.php" class="btn btn-primary btn-lg">Start for Free</a>
    <a href="<?= $base ?>/auth/register_agent.php" class="btn btn-outline btn-lg">Join as Agent</a>
  </div>
</section>

<!-- Stats -->
<div style="display:flex;justify-content:center;gap:40px;flex-wrap:wrap;padding:20px 40px 40px;border-top:1px solid var(--clr-border);border-bottom:1px solid var(--clr-border);max-width:700px;margin:0 auto;">
  <div style="text-align:center">
    <div style="font-size:2rem;font-weight:700;color:var(--clr-accent)"><?= $users_count ?>+</div>
    <div style="color:var(--txt-secondary);font-size:.9rem">Entrepreneurs</div>
  </div>
  <div style="text-align:center">
    <div style="font-size:2rem;font-weight:700;color:var(--clr-accent2)"><?= $agents_count ?>+</div>
    <div style="color:var(--txt-secondary);font-size:.9rem">Expert Agents</div>
  </div>
  <div style="text-align:center">
    <div style="font-size:2rem;font-weight:700;color:var(--clr-success)"><?= $content_count ?>+</div>
    <div style="color:var(--txt-secondary);font-size:.9rem">Resources</div>
  </div>
</div>

<!-- Features -->
<section class="features" style="padding-top:50px;">
  <div class="feature-card">
    <div class="f-icon">🎓</div>
    <h3>Expert Agentship</h3>
    <p>Connect directly with approved business agents and entrepreneurs who have real-world experience in your industry.</p>
  </div>
  <div class="feature-card">
    <div class="f-icon">📚</div>
    <h3>Business Resources</h3>
    <p>Access business ideas, frameworks, market research, startup plans, financial guides, and templates — all curated for you.</p>
  </div>
  <div class="feature-card">
    <div class="f-icon">💬</div>
    <h3>Community Forum</h3>
    <p>Ask questions, share ideas, and engage with a growing community of like-minded entrepreneurs and business agents.</p>
  </div>
  <div class="feature-card">
    <div class="f-icon">✉️</div>
    <h3>Private Messaging</h3>
    <p>Send and receive private messages with agents for personalized one-on-one guidance on your startup journey.</p>
  </div>
  <div class="feature-card">
    <div class="f-icon">🏷️</div>
    <h3>Business Categories</h3>
    <p>Explore 10+ business categories including Technology, Finance, E-Commerce, Healthcare, Agriculture, and more.</p>
  </div>
  <div class="feature-card">
    <div class="f-icon">⭐</div>
    <h3>Feedback System</h3>
    <p>Rate and review agents, helping the community identify the best experts and maintain quality on the platform.</p>
  </div>
</section>

<!-- CTA -->
<div style="text-align:center;padding:60px 20px;">
  <h2 style="margin-bottom:12px;">Ready to Start Your Journey?</h2>
  <p style="color:var(--txt-secondary);margin-bottom:24px;">Join hundreds of entrepreneurs already using our platform.</p>
  <a href="<?= $base ?>/auth/register_user.php" class="btn btn-primary btn-lg">Create Free Account</a>
</div>

<footer style="text-align:center;padding:20px;border-top:1px solid var(--clr-border);color:var(--txt-muted);font-size:.85rem;">
  © 2025 Entrepreneur Portal — CS619 Project | Group F25PROJECTC2D81
</footer>
<script src="<?= $base ?>/assets/js/main.js"></script>
</body>
</html>
