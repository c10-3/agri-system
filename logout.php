<?php
declare(strict_types=1);
require_once __DIR__ . '/includes/auth.php';

logout();
session_start();
$_SESSION['flash_error'] = 'You have been logged out.';
header('Location: login.php');
exit;
