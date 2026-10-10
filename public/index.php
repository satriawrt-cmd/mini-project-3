<?php
require_once __DIR__ . '/../helpers/auth.php';
header('Location: ' . (current_user() ? 'dashboard.php' : 'login.php'));
exit;
