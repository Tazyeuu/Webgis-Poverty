<?php
require_once __DIR__ . '/config/session.php';
startAppSession();
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/index.php');
    } else {
        header('Location: user/index.php');
    }
    exit;
}
header('Location: login.php');
exit;
