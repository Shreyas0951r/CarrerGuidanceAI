<?php
require_once '../config/database.php';

requirePostRequest();
requireValidCsrfToken($_POST['csrf_token'] ?? '');

destroyCurrentSession();
redirect('auth/admin-login.php');
?>
