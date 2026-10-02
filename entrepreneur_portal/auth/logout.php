<?php
session_start();
session_destroy();
header('Location: ' . 'http://localhost/entrepreneur_portal/auth/login.php');
exit;
