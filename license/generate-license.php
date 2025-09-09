<?php
// --- PENGATURAN KRUSIAL ---
// Kunci-kunci ini HARUS SAMA PERSIS dengan yang ada di dalam plugin Anda.
// Simpan di tempat yang aman dan jangan pernah diubah setelah lisensi dibuat.
define('LICENSE_ENCRYPTION_KEY', 'ganti-dengan-kunci-rahasia-anda-yang-sangat-panjang');
define('LICENSE_ENCRYPTION_IV', 'ganti-dengan-16-karakter-acak'); // Harus tepat 16 karakter

$generated_key = null;
$input_domain = '';
$input_expiry_date = '';

// Cek apakah form sudah disubmit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input_domain = trim(strtolower($_POST['domain'] ?? ''));
    $input_expiry_date = trim($_POST['expiry_date'] ?? '');

    // Pastikan domain bersih (tanpa http://, www., dan garis miring di akhir)
    $input_domain = preg_replace('/^https?:\/\//', '', $input_domain);
    $input_domain = preg_replace('/^www\./', '', $input_domain);
    $input_domain = rtrim($input_domain, '/');

    if (!empty($input_domain) && !empty($input_expiry_date)) {
        // Data yang akan dienkripsi: domain|tanggal_kedaluwarsa
        $data_to_encrypt = "$input_domain|$input_expiry_date";
        
        // Enkripsi data menggunakan OpenSSL
        $encrypted_data = openssl_encrypt($data_to_encrypt, 'aes-256-cbc', LICENSE_ENCRYPTION_KEY, 0, LICENSE_ENCRYPTION_IV);
        
        // Ubah hasil enkripsi menjadi format yang lebih mudah disalin (Base64)
        $generated_key = base64_encode($encrypted_data);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Generator Lisensi Manual (Offline)</title>
    <style>
        /* CSS dari sebelumnya bisa digunakan di sini */
        body { font-family: sans-serif; text-align: center; margin-top: 50px; }
        .container { max-width: 500px; margin: auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input[type="text"], input[type="date"] { width: 100%; padding: 8px; box-sizing: border-box; margin-bottom: 10px; }
        button { width: 100%; padding: 10px; background: #0073aa; color: #fff; border: none; cursor: pointer; }
        pre { background: #eee; padding: 10px; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Generator Lisensi (Offline)</h1>
        <form action="" method="POST">
            <label for="domain">Nama Domain:</label>
            <input type="text" id="domain" name="domain" value="<?php echo htmlspecialchars($input_domain); ?>" placeholder="cth: snackread.web.id" required>

            <label for="expiry_date">Tanggal Kedaluwarsa:</label>
            <input type="date" id="expiry_date" name="expiry_date" value="<?php echo htmlspecialchars($input_expiry_date); ?>" required>

            <button type="submit">Buat Kunci Lisensi</button>
        </form>

        <?php if ($generated_key): ?>
            <div class="result">
                <h2>✅ Kunci Lisensi Berhasil Dibuat:</h2>
                <pre><?php echo htmlspecialchars($generated_key); ?></pre>
                <p><small>Kunci ini berisi data untuk domain <strong><?php echo htmlspecialchars($input_domain); ?></strong> yang aktif hingga <strong><?php echo htmlspecialchars($input_expiry_date); ?></strong>.</small></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>