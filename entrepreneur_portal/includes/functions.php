<?php
// ============================================================
//  Shared Helper Functions
// ============================================================

function sanitize($conn, $val) {
    return $conn->real_escape_string(htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8'));
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['agent_id']) || isset($_SESSION['admin_id']);
}

function requireUser() {
    if (!isset($_SESSION['user_id'])) redirect(BASE_URL . '/auth/login.php');
}

function requireAgent() {
    if (!isset($_SESSION['agent_id'])) redirect(BASE_URL . '/auth/login.php');
}

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) redirect(BASE_URL . '/auth/login.php');
}

function flashSet($key, $msg) {
    $_SESSION['flash'][$key] = $msg;
}

function flashGet($key) {
    if (isset($_SESSION['flash'][$key])) {
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
    return null;
}

function showFlash() {
    foreach (['success','error','info'] as $type) {
        $msg = flashGet($type);
        if ($msg) {
            echo "<div class=\"flash flash--$type\">$msg</div>";
        }
    }
}

function timeAgo($datetime) {
    $now  = new DateTime();
    $ago  = new DateTime($datetime);
    $diff = $now->diff($ago);
    if ($diff->d === 0 && $diff->h === 0 && $diff->i < 2)  return 'just now';
    if ($diff->d === 0 && $diff->h === 0)  return $diff->i . ' min ago';
    if ($diff->d === 0)                     return $diff->h . ' hr ago';
    if ($diff->d < 7)                       return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
    return $ago->format('d M Y');
}

function getUnreadCount($conn, $id, $type) {
    $type = $conn->real_escape_string($type);
    $res  = $conn->query("SELECT COUNT(*) AS c FROM messages
                          WHERE receiver_id=$id AND receiver_type='$type' AND is_read=0");
    return $res->fetch_assoc()['c'] ?? 0;
}

function ratingStars($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) {
        $out .= $i <= $rating ? '<span class="star star--on">★</span>' : '<span class="star star--off">★</span>';
    }
    return $out;
}

function getAvgRating($conn, $agent_id) {
    $res = $conn->query("SELECT AVG(rating) AS avg FROM feedback WHERE agent_id=$agent_id");
    $row = $res->fetch_assoc();
    return $row['avg'] ? round($row['avg'], 1) : null;
}

// Generate initials avatar HTML (no image needed)
function initialsAvatar($name, $size = 40, $extra_class = '') {
    $colors = ['#4f8ef7','#7c5ef7','#22c55e','#f59e0b','#ef4444','#06b6d4','#ec4899','#8b5cf6','#14b8a6','#f97316'];
    $words  = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($words, 0, 2) as $w) {
        $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    }
    if (!$initials) $initials = '?';
    $color = $colors[abs(crc32($name)) % count($colors)];
    $fs    = round($size * 0.38);
    return "<div class=\"initials-avatar $extra_class\" style=\"width:{$size}px;height:{$size}px;background:$color;font-size:{$fs}px\">$initials</div>";
}

function contentTypeBadge($type) {
    $map = [
        'business_idea'   => ['Business Idea',    'badge--blue'],
        'template'        => ['Template',          'badge--green'],
        'framework'       => ['Framework',         'badge--purple'],
        'market_research' => ['Market Research',   'badge--orange'],
        'startup_plan'    => ['Startup Plan',      'badge--teal'],
        'partnership'     => ['Partnership',       'badge--pink'],
        'financial'       => ['Financial',         'badge--gold'],
        'opportunity'     => ['Opportunity',       'badge--red'],
        'other'           => ['Other',             'badge--gray'],
    ];
    $d = $map[$type] ?? ['Unknown', 'badge--gray'];
    return "<span class=\"badge {$d[1]}\">{$d[0]}</span>";
}
