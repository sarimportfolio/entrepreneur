<?php
session_start();
require_once dirname(__DIR__) . '/config/db.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) exit;

if (isset($_SESSION['user_id'])) {
    $uid = $_SESSION['user_id'];
    $conn->query("UPDATE messages SET is_read=1 WHERE id=$id AND receiver_id=$uid AND receiver_type='user'");
} elseif (isset($_SESSION['agent_id'])) {
    $aid = $_SESSION['agent_id'];
    $conn->query("UPDATE messages SET is_read=1 WHERE id=$id AND receiver_id=$aid AND receiver_type='agent'");
}
echo 'ok';
