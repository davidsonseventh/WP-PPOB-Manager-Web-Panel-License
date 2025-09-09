<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Jika session admin_logged_in tidak ada atau tidak bernilai true,
// arahkan kembali ke halaman login.
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: index.php");
    exit();
}
?>