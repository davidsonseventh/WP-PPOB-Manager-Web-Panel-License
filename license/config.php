<?php
// MENGAKTIFKAN PELAPORAN ERROR UNTUK MEMBANTU DEBUGGING
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- PENGATURAN DATABASE ---
define('DB_HOST', 'localhost');
define('DB_USER', 'snackrea_license');
define('DB_PASS', 'JYWgGpgvU5nvI+mi');
define('DB_NAME', 'snackrea_license');

// --- PENGATURAN LISENSI (HARUS SAMA DENGAN DI PLUGIN) ---
define('LICENSE_ENCRYPTION_KEY', 'ganti-dengan-kunci-rahasia-anda-yang-sangat-panjang');
define('LICENSE_ENCRYPTION_IV', 'ganti-dengan-16-karakter-acak'); // Harus tepat 16 karakter

// --- PENGATURAN SITUS ---
define('SITE_URL', 'https://kasir.webpulsa.shop/license'); // Ganti dengan URL Anda
define('SUPPORTED_GATEWAYS', ['midtrans', 'xendit', 'tripay', 'duitku', 'paypal', 'faucetpay']);

// --- KONEKSI DATABASE ---
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e){
    die("ERROR: Tidak dapat terhubung ke database. " . $e->getMessage());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>