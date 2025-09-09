<?php
require_once 'config.php';
$transaction_id = $_GET['trx_id'] ?? null;
$license_key = null;

if ($transaction_id) {
    // Ambil kunci lisensi dari database berdasarkan ID transaksi
    $stmt = $pdo->prepare("SELECT license_key, domain FROM licenses JOIN transactions ON licenses.transaction_id = transactions.id WHERE transactions.id = ?");
    $stmt->execute([$transaction_id]);
    $result = $stmt->fetch();
    if ($result) {
        $license_key = $result['license_key'];
        $domain = $result['domain'];
    }
}

if (!$license_key) {
    // die("Transaksi tidak ditemukan atau belum selesai.");
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <title>Pembelian Lisensi Berhasil</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="fb-header">Layanan Lisensi SnackRead</div>
    <div class="container">
        <h1>✅ Pembayaran Berhasil!</h1>
        <p>Terima kasih. Lisensi Anda telah berhasil dibuat dan dikirim ke email Anda.</p>
        <?php if ($license_key): ?>
            <div class="license-box">
                <p>Kunci lisensi Anda untuk domain <strong><?php echo htmlspecialchars($domain); ?></strong>:</p>
                <pre class="license-key"><?php echo htmlspecialchars($license_key); ?></pre>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>