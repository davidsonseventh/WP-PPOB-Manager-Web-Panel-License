<?php
require_once 'config.php';
require_once 'includes/functions.php';

$transaction_id = filter_input(INPUT_GET, 'trx_id', FILTER_VALIDATE_INT);
if (!$transaction_id) { die("Transaksi tidak valid."); }

// Logika untuk upload bukti transfer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['payment_proof'])) {
    // ... (Logika validasi & upload file ke folder /uploads/)
    // ... (Update nama file ke kolom `payment_proof` di tabel `transactions`)
    // ... (Update status menjadi `menunggu_konfirmasi`)
    $upload_success = true; // Ganti dengan hasil upload sebenarnya
}

$banks = $pdo->query("SELECT * FROM bank_accounts")->fetchAll();
$trx = $pdo->prepare("SELECT * FROM transactions WHERE id = ?");
$trx->execute([$transaction_id]);
$transaction = $trx->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Instruksi Pembayaran</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="fb-header">Layanan Lisensi SnackRead</div>
    <div class="container">
        <h1>Selesaikan Pembayaran Anda</h1>
        <p>Silakan transfer sejumlah <strong>Rp <?php echo number_format($transaction['amount']); ?></strong> ke salah satu rekening di bawah ini:</p>

        <div class="bank-list">
            <?php foreach ($banks as $bank): ?>
                <div class="bank-account">
                    <h4><?php echo htmlspecialchars($bank['bank_name']); ?></h4>
                    <p>No. Rekening: <strong><?php echo htmlspecialchars($bank['account_number']); ?></strong></p>
                    <p>Atas Nama: <strong><?php echo htmlspecialchars($bank['account_name']); ?></strong></p>
                </div>
            <?php endforeach; ?>
        </div>

        <hr>
        
        <?php if (isset($upload_success)): ?>
            <div class="success">Terima kasih! Bukti transfer Anda telah kami terima dan akan segera kami periksa.</div>
        <?php else: ?>
            <h3>Unggah Bukti Transfer</h3>
            <form action="" method="POST" enctype="multipart/form-data">
                <label>Pilih file gambar (JPG, PNG, PDF):</label>
                <input type="file" name="payment_proof" required>
                <button type="submit">Konfirmasi Pembayaran</button>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>