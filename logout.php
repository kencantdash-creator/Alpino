<?php
require __DIR__ . '/includes/db.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    unset($_SESSION['admin']);
    session_regenerate_id(true);
    flash('You have been logged out.');
}
header('Location: index.php');
exit;
