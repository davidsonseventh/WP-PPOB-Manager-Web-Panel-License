<?php
// Ganti 'password_rahasia_anda' dengan password yang ingin Anda gunakan.
$password_untuk_admin = 'D4v1d@091293';

// Membuat hash yang aman dari password Anda
$hashed_password = password_hash($password_untuk_admin, PASSWORD_DEFAULT);

echo "Gunakan hash di bawah ini untuk kolom password di phpMyAdmin:";
echo "<br><br>";
echo "<pre>" . htmlspecialchars($hashed_password) . "</pre>";
?>