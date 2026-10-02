<?php
// ============================================================
//  Database Configuration
//  XAMPP defaults: host=localhost, user=root, pass=''
// ============================================================

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'entrepreneur_portal');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;color:#c0392b;">
            <h2>Database Connection Failed</h2>
            <p>' . $conn->connect_error . '</p>
            <p>Make sure XAMPP MySQL is running and you have imported <code>database/schema.sql</code>.</p>
         </div>');
}

$conn->set_charset('utf8mb4');

// Base URL - adjust if your folder name differs
define('BASE_URL', 'http://localhost/entrepreneur_portal');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
