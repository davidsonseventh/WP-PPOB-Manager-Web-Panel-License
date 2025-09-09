<?php
// Payment gateway mengirim notifikasi ke sini...
// Anda memverifikasi notifikasi tersebut...

// Jika pembayaran valid, ambil data dari session
session_start();
$pending = $_SESSION['pending_license'];

// --- LOGIKA MEMBUAT KUNCI LISENSI ---
$domain = $pending['domain'];
$expiry_date = $pending['expiry_date'];
$plugin_id = $pending['plugin_id'];
$secret_key = "KunciSangatRahasiaMilikAnda12345"; // Ganti dengan kunci Anda

$data_to_encrypt = "$domain|$expiry_date|$plugin_id|$secret_key";
$license_key = 'WPPPOB-' . strtoupper(substr(hash('sha256', $data_to_encrypt), 0, 16));

// Simpan ke database tabel `licenses`
// ... kode untuk INSERT ke database ...

// Hapus session
unset($_SESSION['pending_license']);

// Anda bisa menyimpan $license_key untuk ditampilkan di halaman sukses
$_SESSION['new_license_key'] = $license_key;