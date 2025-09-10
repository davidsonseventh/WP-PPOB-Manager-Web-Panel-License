<?php
// Pastikan file WordPress yang diperlukan dimuat,
// meskipun dalam konteks plugin, ini biasanya sudah ditangani.
if (!defined('ABSPATH') ) {
    require_once( $_SERVER. '/wp-load.php' );
}

// Memeriksa apakah pengguna saat ini dapat mengelola opsi
// Ini adalah cara yang aman dan direkomendasikan untuk otentikasi dalam dasbor admin WordPress.
if (! current_user_can('manage_options') ) {
    // Jika tidak, tampilkan pesan kesalahan atau arahkan kembali.
    wp_die( __('Anda tidak memiliki hak akses yang memadai untuk mengakses halaman ini.') );
}

// Jika otentikasi berhasil, skrip tidak akan melakukan apa pun
// dan akan membiarkan eksekusi berlanjut ke file `dashboard.php`.
?>