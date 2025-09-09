<?php
require_once '../config.php';
require_once 'auth-check.php';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; // Kita buat sidebar terpisah ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2>Dashboard</h2>
                <span>Selamat datang, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>!</span>
            </div>
            <p>Pilih menu di sebelah kiri untuk mulai mengelola lisensi. Anda dapat mengatur plugin yang dijual, melihat lisensi yang sudah ada, dan membuat lisensi baru secara manual.</p>
        </div>
    </div>
</body>
</html>