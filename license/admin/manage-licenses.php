<?php
require_once '../config.php';
require_once 'auth-check.php';
require_once '../includes/functions.php';

$search_term = trim($_GET['search'] ?? '');
$licenses = [];

$sql = "SELECT licenses.*, plugins.plugin_name 
        FROM licenses 
        LEFT JOIN plugins ON licenses.plugin_id = plugins.id";

if (!empty($search_term)) {
    $sql .= " WHERE licenses.domain LIKE ? OR licenses.license_key LIKE ?";
    $stmt = $pdo->prepare($sql . " ORDER BY licenses.created_at DESC");
    $stmt->execute(['%' . $search_term . '%', '%' . $search_term . '%']);
} else {
    $stmt = $pdo->query($sql . " ORDER BY licenses.created_at DESC");
}
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Lisensi</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2>Kelola Lisensi</h2>
            </div>

            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Cari berdasarkan domain atau kunci lisensi..." value="<?php echo htmlspecialchars($search_term); ?>">
                <button type="submit">Cari</button>
                 <?php if (!empty($search_term)): ?>
                    <a href="manage-licenses.php">Reset Pencarian</a>
                <?php endif; ?>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Plugin</th>
                        <th>Domain</th>
                        <th>Kunci Lisensi</th>
                        <th>Tgl Kedaluwarsa</th>
                        <th>Tgl Dibuat</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($licenses)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center;">Tidak ada lisensi yang ditemukan.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($licenses as $license): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($license['plugin_name'] ?? 'Plugin Dihapus'); ?></td>
                                <td><?php echo htmlspecialchars($license['domain']); ?></td>
                                <td><code><?php echo htmlspecialchars($license['license_key']); ?></code></td>
                                <td><?php echo date("d M Y", strtotime($license['expiry_date'])); ?></td>
                                <td><?php echo date("d M Y, H:i", strtotime($license['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>