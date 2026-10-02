<?php
$page_title = 'Give Feedback';
$active_nav = 'feedback';
require_once dirname(__DIR__) . '/includes/header.php';
requireAdmin();
$admin_id = $_SESSION['admin_id'];

// ── Submit feedback ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $agent_id = (int)($_POST['agent_id'] ?? 0);
    $rating   = max(1, min(5, (int)($_POST['rating'] ?? 5)));
    $comment  = sanitize($conn, $_POST['comment'] ?? '');

    if ($agent_id) {
        // Allow admin to give feedback multiple times
        $conn->query("INSERT INTO feedback (from_id,from_type,agent_id,rating,comment)
                      VALUES ($admin_id,'admin',$agent_id,$rating,'$comment')");
        flashSet('success', 'Feedback submitted successfully!');
        redirect(BASE_URL . '/admin/give_feedback.php');
    }
}

// Pre-select agent from query string (coming from manage_users page)
$preselect = (int)($_GET['agent'] ?? 0);

$agents = $conn->query("SELECT a.id, a.name, a.expertise,
    (SELECT AVG(rating) FROM feedback WHERE agent_id=a.id) AS avg_rating,
    (SELECT COUNT(*) FROM feedback WHERE agent_id=a.id) AS fb_count
    FROM agents a WHERE a.approval_status='approved' ORDER BY a.name");

// Existing feedback by admin
$given = $conn->query("SELECT f.*, a.name AS agent_name FROM feedback f
    LEFT JOIN agents a ON f.agent_id=a.id
    WHERE f.from_id=$admin_id AND f.from_type='admin'
    ORDER BY f.created_at DESC");
?>

<div class="grid-2">
  <!-- Feedback form -->
  <div class="card">
    <h3 style="margin-bottom:20px">Submit Feedback to Agent</h3>
    <form method="POST">
      <div class="form-group">
        <label>Select Agent <span class="required">*</span></label>
        <select name="agent_id" class="form-control" required>
          <option value="">— Select an Agent —</option>
          <?php while ($a = $agents->fetch_assoc()): ?>
            <option value="<?= $a['id'] ?>" <?= $preselect==$a['id']?'selected':'' ?>>
              <?= htmlspecialchars($a['name']) ?> — <?= htmlspecialchars($a['expertise']) ?>
              <?php if ($a['avg_rating']): ?>(★ <?= round($a['avg_rating'],1) ?>)<?php endif; ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Rating <span class="required">*</span></label>
        <div class="rating-input">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i===5?'checked':'' ?>>
            <label for="star<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>
      </div>

      <div class="form-group">
        <label>Feedback Comment <span class="required">*</span></label>
        <textarea name="comment" class="form-control" rows="5"
                  placeholder="Provide specific feedback on the agent's content quality, professionalism, and contribution to the platform..." required></textarea>
      </div>

      <button type="submit" name="submit_feedback" class="btn btn-primary">⭐ Submit Feedback</button>
    </form>
  </div>

  <!-- Agents quick stats -->
  <div class="card">
    <h3 style="margin-bottom:20px">Agent Ratings Overview</h3>
    <?php
    $agents->data_seek(0);
    while ($a = $agents->fetch_assoc()):
    ?>
      <div style="padding:12px 0;border-bottom:1px solid var(--clr-border);display:flex;align-items:center;justify-content:space-between;gap:12px">
        <div>
          <div style="font-weight:600"><?= htmlspecialchars($a['name']) ?></div>
          <div style="font-size:.8rem;color:var(--txt-secondary)"><?= htmlspecialchars($a['expertise']) ?></div>
        </div>
        <div style="text-align:right">
          <?php if ($a['avg_rating']): ?>
            <div><?= ratingStars(round($a['avg_rating'])) ?></div>
            <div style="font-size:.75rem;color:var(--txt-muted)"><?= round($a['avg_rating'],1) ?>/5 (<?= $a['fb_count'] ?> reviews)</div>
          <?php else: ?>
            <div style="color:var(--txt-muted);font-size:.82rem">No reviews yet</div>
          <?php endif; ?>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<!-- Feedback history -->
<div class="card mt-24">
  <div class="card-header">
    <h3>My Feedback History</h3>
  </div>
  <?php if ($given->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">📝</div><h3>No feedback given yet</h3></div>
  <?php else: ?>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr><th>Agent</th><th>Rating</th><th>Comment</th><th>Date</th></tr>
        </thead>
        <tbody>
          <?php while ($fb = $given->fetch_assoc()): ?>
          <tr>
            <td><strong><?= htmlspecialchars($fb['agent_name']) ?></strong></td>
            <td><?= ratingStars($fb['rating']) ?></td>
            <td style="max-width:300px;color:var(--txt-secondary)">
              <?= htmlspecialchars(substr($fb['comment'], 0, 100)) ?><?= strlen($fb['comment']) > 100 ? '...' : '' ?>
            </td>
            <td style="color:var(--txt-muted);white-space:nowrap"><?= timeAgo($fb['created_at']) ?></td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
