<?php
require_once '../config.php';
require_once 'auth-check.php';

// Logika untuk menyimpan atau menghapus rekening
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_bank'])) {
        $stmt = $pdo->prepare("INSERT INTO bank_accounts (bank_name, account_number, account_name) VALUES (?, ?, ?)");
        $stmt->execute([$_POST['bank_name'], $_POST['account_number'], $_POST['account_name']]);
    }
    if (isset($_POST['delete_bank'])) {
        $stmt = $pdo->prepare("DELETE FROM bank_accounts WHERE id = ?");
        $stmt->execute([$_POST['bank_id']]);
    }
    header("Location: manage-banks.php");
    exit();
}

$banks = $pdo->query("SELECT * FROM bank_accounts ORDER BY bank_name ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Rekening Bank</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header"><h2>Kelola Rekening Bank</h2></div>
            
            <form action="" method="POST">
                <h3>Tambah Rekening Baru</h3>
                <input type="hidden" name="add_bank" value="1">
                <label>Nama Bank (cth: BCA, Mandiri)</label>
                <input type="text" name="bank_name" required>
                <label>Nomor Rekening</label>
                <input type="text" name="account_number" required>
                <label>Atas Nama</label>
                <input type="text" name="account_name" required>
                <button type="submit">Tambah Rekening</button>
            </form>

            <h3>Daftar Rekening Bank</h3>
            <table>
                <thead><tr><th>Nama Bank</th><th>Nomor Rekening</th><th>Atas Nama</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php foreach ($banks as $bank): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                            <td><?php echo htmlspecialchars($bank['account_number']); ?></td>
                            <td><?php echo htmlspecialchars($bank['account_name']); ?></td>
                            <td>
                                <form action="" method="POST" onsubmit="return confirm('Yakin ingin menghapus rekening ini?');">
                                    <input type="hidden" name="bank_id" value="<?php echo $bank['id']; ?>">
                                    <button type="submit" name="delete_bank" class="delete-button">Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>