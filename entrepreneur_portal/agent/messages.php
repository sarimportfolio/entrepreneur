<?php
$page_title = 'Messages';
$active_nav = 'messages';
require_once dirname(__DIR__) . '/includes/header.php';
requireAgent();
$aid = $_SESSION['agent_id'];

// ── Send ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send'])) {
    $to      = (int)($_POST['receiver_id'] ?? 0);
    $rtype   = sanitize($conn, $_POST['receiver_type'] ?? 'user');
    $subject = sanitize($conn, $_POST['subject'] ?? '');
    $body    = sanitize($conn, $_POST['body'] ?? '');
    if ($to && $body) {
        $conn->query("INSERT INTO messages (sender_id,sender_type,receiver_id,receiver_type,subject,body)
                      VALUES ($aid,'agent',$to,'$rtype','$subject','$body')");
        flashSet('success', 'Message sent!');
        redirect(BASE_URL . '/agent/messages.php');
    }
}

// ── View single ───────────────────────────────────────────────
if (isset($_GET['view'])) {
    $mid = (int)$_GET['view'];
    $conn->query("UPDATE messages SET is_read=1 WHERE id=$mid AND receiver_id=$aid AND receiver_type='agent'");
    $msg = $conn->query("SELECT m.*,
        CASE m.sender_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS sender_name,
        CASE m.receiver_type WHEN 'user' THEN u2.name WHEN 'agent' THEN a2.name END AS receiver_name
        FROM messages m
        LEFT JOIN users u    ON m.sender_type='user'    AND m.sender_id=u.id
        LEFT JOIN agents a   ON m.sender_type='agent'   AND m.sender_id=a.id
        LEFT JOIN users u2   ON m.receiver_type='user'  AND m.receiver_id=u2.id
        LEFT JOIN agents a2  ON m.receiver_type='agent' AND m.receiver_id=a2.id
        WHERE m.id=$mid AND ((m.receiver_id=$aid AND m.receiver_type='agent') OR (m.sender_id=$aid AND m.sender_type='agent'))")->fetch_assoc();

    if (!$msg) { flashSet('error', 'Message not found.'); redirect(BASE_URL . '/agent/messages.php'); }

    // Can reply if someone sent to us
    $can_reply   = ($msg['receiver_id'] == $aid && $msg['receiver_type'] === 'agent');
    $reply_to_id = $msg['sender_id'];
    $reply_type  = $msg['sender_type'];
    ?>
    <a href="<?= $base ?>/agent/messages.php" class="btn btn-secondary btn-sm mb-20">← Back to Inbox</a>
    <div class="card">
      <h3 style="margin-bottom:6px"><?= htmlspecialchars($msg['subject'] ?: '(no subject)') ?></h3>
      <div style="color:var(--txt-secondary);font-size:.85rem;margin-bottom:20px">
        From: <strong><?= htmlspecialchars($msg['sender_name']) ?></strong>
        → To: <strong><?= htmlspecialchars($msg['receiver_name']) ?></strong>
        · <?= timeAgo($msg['sent_at']) ?>
      </div>
      <hr class="divider">
      <div style="line-height:1.8;margin:16px 0"><?= nl2br(htmlspecialchars($msg['body'])) ?></div>

      <?php if ($can_reply): ?>
        <hr class="divider">
        <h4 style="margin-bottom:12px">Reply</h4>
        <form method="POST">
          <input type="hidden" name="receiver_id"   value="<?= $reply_to_id ?>">
          <input type="hidden" name="receiver_type" value="<?= $reply_type ?>">
          <input type="hidden" name="subject"       value="Re: <?= htmlspecialchars($msg['subject']) ?>">
          <div class="form-group">
            <textarea name="body" class="form-control" rows="4" placeholder="Write your reply..." required></textarea>
          </div>
          <button type="submit" name="send" class="btn btn-primary">Send Reply</button>
        </form>
      <?php endif; ?>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Inbox + Sent lists ────────────────────────────────────────
$inbox = $conn->query("SELECT m.*,
    CASE m.sender_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS sender_name
    FROM messages m
    LEFT JOIN users u  ON m.sender_type='user'  AND m.sender_id=u.id
    LEFT JOIN agents a ON m.sender_type='agent' AND m.sender_id=a.id
    WHERE m.receiver_id=$aid AND m.receiver_type='agent'
    ORDER BY m.sent_at DESC");

$sent = $conn->query("SELECT m.*,
    CASE m.receiver_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS receiver_name
    FROM messages m
    LEFT JOIN users u  ON m.receiver_type='user'  AND m.receiver_id=u.id
    LEFT JOIN agents a ON m.receiver_type='agent' AND m.receiver_id=a.id
    WHERE m.sender_id=$aid AND m.sender_type='agent'
    ORDER BY m.sent_at DESC");

// Users and agents for compose
$users_list  = $conn->query("SELECT id,'user' AS type, name FROM users WHERE status='active' ORDER BY name");
$agents_list = $conn->query("SELECT id,'agent' AS type, name FROM agents WHERE approval_status='approved' AND id != $aid ORDER BY name");
?>

<div class="flex-between mb-20">
  <div></div>
  <button class="btn btn-primary" onclick="openModal('composeModal')">✉️ Compose Message</button>
</div>

<div class="tabs-wrapper">
  <div class="tabs">
    <button class="tab-btn active" data-tab="inbox">📥 Inbox (<?= $inbox->num_rows ?>)</button>
    <button class="tab-btn" data-tab="sent">📤 Sent (<?= $sent->num_rows ?>)</button>
  </div>

  <div class="tab-panel active" id="inbox">
    <?php if ($inbox->num_rows === 0): ?>
      <div class="empty-state"><div class="empty-icon">📭</div><h3>Inbox is empty</h3></div>
    <?php else: ?>
      <?php while ($m = $inbox->fetch_assoc()): ?>
        <a href="?view=<?= $m['id'] ?>" class="msg-thread <?= !$m['is_read']?'unread':'' ?>" style="text-decoration:none;color:inherit">
          <div class="msg-info">
            <div class="msg-subject"><?= htmlspecialchars($m['subject'] ?: '(no subject)') ?></div>
            <div class="msg-preview">From: <?= htmlspecialchars($m['sender_name']) ?> · <?= htmlspecialchars(substr($m['body'],0,60)) ?>...</div>
            <div class="msg-meta"><?= timeAgo($m['sent_at']) ?> <?= !$m['is_read'] ? '<span class="badge badge--blue">New</span>' : '' ?></div>
          </div>
        </a>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <div class="tab-panel" id="sent">
    <?php if ($sent->num_rows === 0): ?>
      <div class="empty-state"><div class="empty-icon">📤</div><h3>No sent messages</h3></div>
    <?php else: ?>
      <?php while ($m = $sent->fetch_assoc()): ?>
        <div class="msg-thread">
          <div class="msg-info">
            <div class="msg-subject"><?= htmlspecialchars($m['subject'] ?: '(no subject)') ?></div>
            <div class="msg-preview">To: <?= htmlspecialchars($m['receiver_name']) ?> · <?= htmlspecialchars(substr($m['body'],0,60)) ?>...</div>
            <div class="msg-meta"><?= timeAgo($m['sent_at']) ?></div>
          </div>
        </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Compose Modal -->
<div class="modal-overlay" id="composeModal">
  <div class="modal">
    <div class="modal-header">
      <h3>New Message</h3>
      <button class="modal-close" onclick="closeModal('composeModal')">✕</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Send To <span class="required">*</span></label>
        <select name="receiver_id" class="form-control" required onchange="setReceiverType(this)">
          <option value="">— Select Recipient —</option>
          <optgroup label="Users (Entrepreneurs)">
            <?php while ($u = $users_list->fetch_assoc()): ?>
              <option value="<?= $u['id'] ?>" data-type="user"><?= htmlspecialchars($u['name']) ?></option>
            <?php endwhile; ?>
          </optgroup>
          <optgroup label="Other Agents">
            <?php while ($ag = $agents_list->fetch_assoc()): ?>
              <option value="<?= $ag['id'] ?>" data-type="agent"><?= htmlspecialchars($ag['name']) ?></option>
            <?php endwhile; ?>
          </optgroup>
        </select>
        <input type="hidden" name="receiver_type" id="receiverType" value="user">
      </div>
      <div class="form-group">
        <label>Subject</label>
        <input type="text" name="subject" class="form-control" placeholder="Message subject">
      </div>
      <div class="form-group">
        <label>Message <span class="required">*</span></label>
        <textarea name="body" class="form-control" rows="5" placeholder="Write your message..." required></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" name="send" class="btn btn-primary">Send</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('composeModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<script>
function setReceiverType(sel) {
  const opt = sel.options[sel.selectedIndex];
  document.getElementById('receiverType').value = opt.dataset.type || 'user';
}
</script>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
