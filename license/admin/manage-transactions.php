<?php
require_once '../config.php';
require_once 'auth-check.php';
require_once '../includes/functions.php';

// Logika untuk menyetujui (approve) transaksi manual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_trx'])) {
    $trx_id = intval($_POST['trx_id']);
    
    // Ambil detail transaksi yang akan di-approve
    $stmt_trx = $pdo->prepare("SELECT * FROM transactions WHERE id = ? AND status = 'menunggu_konfirmasi'");
    $stmt_trx->execute([$trx_id]);
    $transaction = $stmt_trx->fetch(PDO::FETCH_ASSOC);

    if ($transaction) {
        // Buat kunci lisensi
        $license_key = generate_license_key($transaction['license_domain'], $transaction['expiry_date']);
        
        // Simpan kunci lisensi ke tabel 'licenses'
        $stmt_insert_license = $pdo->prepare("INSERT INTO licenses (plugin_id, transaction_id, license_key, domain, expiry_date) VALUES (?, ?, ?, ?, ?)");
        $stmt_insert_license->execute([
            $transaction['plugin_id'],
            $transaction['id'],
            $license_key,
            $transaction['license_domain'],
            $transaction['expiry_date']
        ]);
        
        // Update status transaksi menjadi 'completed'
        $pdo->prepare("UPDATE transactions SET status = 'completed' WHERE id = ?")->execute([$trx_id]);
        
        // (Opsional) Kirim email ke pelanggan berisi kunci lisensi
        // mail($transaction['license_email'], "Pembayaran Dikonfirmasi - Kunci Lisensi Anda", "Berikut kunci Anda: $license_key");
    }
    
    header("Location: manage-transactions.php");
    exit();
}

// Ambil semua transaksi untuk ditampilkan
$transactions = $pdo->query(
    "SELECT t.*, p.plugin_name 
     FROM transactions t 
     LEFT JOIN plugins p ON t.plugin_id = p.id 
     ORDER BY t.created_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Transaksi</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2>Kelola Transaksi</h2>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Plugin</th>
                        <th>Domain</th>
                        <th>Total (IDR)</th>
                        <th>Gateway</th>
                        <th>Status</th>
                        <th>Tgl Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr><td colspan="8" style="text-align: center;">Belum ada transaksi.</td></tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $trx): ?>
                            <tr>
                                <td><?php echo $trx['id']; ?></td>
                                <td><?php echo htmlspecialchars($trx['plugin_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($trx['license_domain']); ?></td>
                                <td><?php echo number_format($trx['amount']); ?></td>
                                <td><?php echo ucfirst($trx['gateway']); ?></td>
                                <td><span class="status-badge status-<?php echo $trx['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $trx['status'])); ?></span></td>
                                <td><?php echo date("d M Y, H:i", strtotime($trx['created_at'])); ?></td>
                                <td>
                                    <?php if ($trx['status'] === 'menunggu_konfirmasi' && !empty($trx['payment_proof'])): ?>
                                        <a href="../uploads/<?php echo htmlspecialchars($trx['payment_proof']); ?>" target="_blank" class="button-small">Lihat Bukti</a>
                                        <form action="" method="POST" onsubmit="return confirm('Anda yakin ingin menyetujui transaksi ini?');" style="display:inline;">
                                            <input type="hidden" name="trx_id" value="<?php echo $trx['id']; ?>">
                                            <button type="submit" name="approve_trx" class="button-small approve">Approve</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>