<?php
$page_title = 'Discussion Forum';
$active_nav = 'forum';
require_once dirname(__DIR__) . '/includes/header.php';
requireUser();
$uid = $_SESSION['user_id'];

// ── Single topic view ─────────────────────────────────────────
if (isset($_GET['topic'])) {
    $tid = (int)$_GET['topic'];
    $conn->query("UPDATE forum_topics SET views=views+1 WHERE id=$tid");
    $topic = $conn->query("SELECT ft.*,
        CASE ft.author_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS author_name,
        c.name AS cat_name
        FROM forum_topics ft
        LEFT JOIN users u  ON ft.author_type='user'  AND ft.author_id=u.id
        LEFT JOIN agents a ON ft.author_type='agent' AND ft.author_id=a.id
        LEFT JOIN categories c ON ft.category_id = c.id
        WHERE ft.id=$tid")->fetch_assoc();

    if (!$topic) { flashSet('error','Topic not found.'); redirect(BASE_URL.'/user/forum.php'); }

    // Handle reply
    if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['reply'])) {
        $body = sanitize($conn, $_POST['body']??'');
        if ($body) {
            $conn->query("INSERT INTO forum_replies (topic_id,author_id,author_type,body) VALUES ($tid,$uid,'user','$body')");
            flashSet('success','Reply posted!');
            redirect(BASE_URL.'/user/forum.php?topic='.$tid);
        }
    }

    $replies = $conn->query("SELECT fr.*,
        CASE fr.author_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS author_name
        FROM forum_replies fr
        LEFT JOIN users u  ON fr.author_type='user'  AND fr.author_id=u.id
        LEFT JOIN agents a ON fr.author_type='agent' AND fr.author_id=a.id
        WHERE fr.topic_id=$tid ORDER BY fr.created_at ASC");
    ?>
    <a href="<?= $base ?>/user/forum.php" class="btn btn-secondary btn-sm mb-20">← Back to Forum</a>
    <div class="card mb-20">
      <h2 style="margin-bottom:8px"><?= htmlspecialchars($topic['title']) ?></h2>
      <div style="color:var(--txt-secondary);font-size:.85rem;margin-bottom:16px;display:flex;gap:16px;flex-wrap:wrap">
        <span>👤 <?= htmlspecialchars($topic['author_name']) ?></span>
        <?php if ($topic['cat_name']): ?><span>📂 <?= htmlspecialchars($topic['cat_name']) ?></span><?php endif; ?>
        <span>👁 <?= $topic['views'] ?> views</span>
        <span>🕐 <?= timeAgo($topic['created_at']) ?></span>
      </div>
      <hr class="divider">
      <div style="line-height:1.8;color:var(--txt-primary)"><?= nl2br(htmlspecialchars($topic['body'])) ?></div>
    </div>

    <!-- Replies -->
    <h3 style="margin-bottom:16px"><?= $replies->num_rows ?> Replies</h3>
    <?php while ($r = $replies->fetch_assoc()): ?>
      <div class="forum-reply">
        <div class="reply-header">
          <strong class="reply-author"><?= htmlspecialchars($r['author_name']) ?></strong>
          <span class="badge <?= $r['author_type']==='agent'?'badge--blue':'badge--gray' ?>" style="font-size:.7rem">
            <?= $r['author_type']==='agent'?'Agent':'User' ?>
          </span>
          <span class="reply-time"><?= timeAgo($r['created_at']) ?></span>
        </div>
        <p style="color:var(--txt-secondary);font-size:.9rem;line-height:1.6"><?= nl2br(htmlspecialchars($r['body'])) ?></p>
      </div>
    <?php endwhile; ?>

    <!-- Reply form -->
    <div class="card mt-20">
      <h4 style="margin-bottom:16px">Write a Reply</h4>
      <form method="POST">
        <div class="form-group">
          <textarea name="body" class="form-control" rows="4" placeholder="Share your thoughts..." required></textarea>
        </div>
        <button type="submit" name="reply" class="btn btn-primary">Post Reply</button>
      </form>
    </div>
    <?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
    <?php return;
}

// ── Handle new topic ─────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['new_topic'])) {
    $title = sanitize($conn, $_POST['title']??'');
    $body  = sanitize($conn, $_POST['body'] ??'');
    $cat   = (int)($_POST['category_id']??0);
    if ($title && $body) {
        $cat_val = $cat > 0 ? $cat : 'NULL';
        $conn->query("INSERT INTO forum_topics (author_id,author_type,category_id,title,body) VALUES ($uid,'user',$cat_val,'$title','$body')");
        flashSet('success','Topic posted!');
        redirect(BASE_URL.'/user/forum.php');
    }
}

// ── Topic listing ─────────────────────────────────────────────
$search = sanitize($conn, $_GET['q']??'');
$cat    = (int)($_GET['category']??0);
$where  = 'WHERE 1=1';
if ($search) $where .= " AND (ft.title LIKE '%$search%' OR ft.body LIKE '%$search%')";
if ($cat)    $where .= " AND ft.category_id=$cat";

$topics = $conn->query("SELECT ft.*,
    CASE ft.author_type WHEN 'user' THEN u.name WHEN 'agent' THEN a.name END AS author_name,
    c.name AS cat_name,
    (SELECT COUNT(*) FROM forum_replies fr WHERE fr.topic_id=ft.id) AS reply_count
    FROM forum_topics ft
    LEFT JOIN users u  ON ft.author_type='user'  AND ft.author_id=u.id
    LEFT JOIN agents a ON ft.author_type='agent' AND ft.author_id=a.id
    LEFT JOIN categories c ON ft.category_id=c.id
    $where ORDER BY ft.is_pinned DESC, ft.created_at DESC");

$cats = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<div class="flex-between mb-20" style="flex-wrap:wrap;gap:12px">
  <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap">
    <div class="search-bar">
      <span class="search-icon">🔍</span>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search forum...">
    </div>
    <select name="category" class="form-control" style="width:auto;min-width:160px">
      <option value="">All Categories</option>
      <?php while ($c = $cats->fetch_assoc()): ?>
        <option value="<?= $c['id'] ?>" <?= $cat==$c['id']?'selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
      <?php endwhile; ?>
    </select>
    <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
  </form>
  <button class="btn btn-primary" onclick="openModal('newTopicModal')">✏️ New Topic</button>
</div>

<?php if ($topics->num_rows === 0): ?>
  <div class="empty-state">
    <div class="empty-icon">💬</div>
    <h3>No discussions yet</h3>
    <p>Be the first to start a conversation!</p>
    <button class="btn btn-primary" onclick="openModal('newTopicModal')">Start Discussion</button>
  </div>
<?php else: ?>
  <?php while ($t = $topics->fetch_assoc()): ?>
    <div class="forum-topic">
      <div class="topic-icon"><?= $t['is_pinned'] ? '📌' : '💬' ?></div>
      <div class="topic-body">
        <div class="topic-title">
          <a href="?topic=<?= $t['id'] ?>"><?= htmlspecialchars($t['title']) ?></a>
          <?php if ($t['is_pinned']): ?><span class="badge badge--blue" style="font-size:.68rem">Pinned</span><?php endif; ?>
        </div>
        <div class="topic-meta">
          <span>👤 <?= htmlspecialchars($t['author_name']) ?></span>
          <?php if ($t['cat_name']): ?><span>📂 <?= htmlspecialchars($t['cat_name']) ?></span><?php endif; ?>
          <span>💬 <?= $t['reply_count'] ?> replies</span>
          <span>👁 <?= $t['views'] ?> views</span>
          <span><?= timeAgo($t['created_at']) ?></span>
        </div>
      </div>
    </div>
  <?php endwhile; ?>
<?php endif; ?>

<!-- New Topic Modal -->
<div class="modal-overlay" id="newTopicModal">
  <div class="modal">
    <div class="modal-header">
      <h3>Start a Discussion</h3>
      <button class="modal-close" onclick="closeModal('newTopicModal')">✕</button>
    </div>
    <form method="POST">
      <div class="form-group">
        <label>Topic Title <span class="required">*</span></label>
        <input type="text" name="title" class="form-control" placeholder="What's your question or topic?" required>
      </div>
      <div class="form-group">
        <label>Category</label>
        <?php $cats->data_seek(0); ?>
        <select name="category_id" class="form-control">
          <option value="">— Select Category —</option>
          <?php while ($c = $cats->fetch_assoc()): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Your Message <span class="required">*</span></label>
        <textarea name="body" class="form-control" rows="5" placeholder="Describe your question or share your idea..." required></textarea>
      </div>
      <div style="display:flex;gap:10px">
        <button type="submit" name="new_topic" class="btn btn-primary">Post Topic</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('newTopicModal')">Cancel</button>
      </div>
    </form>
  </div>
</div>

<?php require_once dirname(__DIR__) . '/includes/footer.php'; ?>
