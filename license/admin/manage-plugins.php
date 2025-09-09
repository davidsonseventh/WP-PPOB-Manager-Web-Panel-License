<?php
require_once '../config.php';
require_once 'auth-check.php';

// Logika untuk menyimpan (INSERT atau UPDATE)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plugin_name'])) {
    $plugin_id = $_POST['plugin_id'] ?? 0;
    $plugin_name = trim($_POST['plugin_name']);
    $plugin_identifier = trim($_POST['plugin_identifier']);
    $price_per_year = floatval($_POST['price_per_year']);
    $download_link = filter_input(INPUT_POST, 'download_link', FILTER_SANITIZE_URL); // Ambil link download

    if ($plugin_id > 0) { // Update
        $stmt = $pdo->prepare("UPDATE plugins SET plugin_name = ?, plugin_identifier = ?, price_per_year = ?, download_link = ? WHERE id = ?");
        $stmt->execute([$plugin_name, $plugin_identifier, $price_per_year, $download_link, $plugin_id]);
    } else { // Insert
        $stmt = $pdo->prepare("INSERT INTO plugins (plugin_name, plugin_identifier, price_per_year, download_link) VALUES (?, ?, ?, ?)");
        $stmt->execute([$plugin_name, $plugin_identifier, $price_per_year, $download_link]);
    }
    header("Location: manage-plugins.php");
    exit();
}

// ... (sisa logika PHP untuk mode edit dan mengambil daftar plugin tetap sama)
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kelola Plugin</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header"><h2>Kelola Plugin</h2></div>
            
            <form action="" method="POST">
                <h3><?php echo $edit_mode ? 'Edit Plugin' : 'Tambah Plugin Baru'; ?></h3>
                <input type="hidden" name="plugin_id" value="<?php echo $plugin_to_edit['id'] ?? 0; ?>">
                
                <label>Nama Plugin</label>
                <input type="text" name="plugin_name" value="<?php echo htmlspecialchars($plugin_to_edit['plugin_name'] ?? ''); ?>" required>
                
                <label>Identifier Plugin (unik, cth: wppob-manager)</label>
                <input type="text" name="plugin_identifier" value="<?php echo htmlspecialchars($plugin_to_edit['plugin_identifier'] ?? ''); ?>" required>
                
                <label>Harga per Tahun (IDR)</label>
                <input type="number" step="1000" name="price_per_year" value="<?php echo htmlspecialchars($plugin_to_edit['price_per_year'] ?? ''); ?>" required>
                
                <label>Link Download Plugin (URL)</label>
                <input type="text" name="download_link" placeholder="https://domain.com/path/to/plugin.zip" value="<?php echo htmlspecialchars($plugin_to_edit['download_link'] ?? ''); ?>">

                <button type="submit"><?php echo $edit_mode ? 'Perbarui Plugin' : 'Simpan Plugin'; ?></button>
                <?php if ($edit_mode): ?>
                    <a href="manage-plugins.php">Batal Edit</a>
                <?php endif; ?>
            </form>

            </div>
    </div>
</body>
</html>