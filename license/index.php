<?php
require_once 'config.php';

// Ambil semua plugin dari database
$plugins = $pdo->query("SELECT * FROM plugins ORDER BY plugin_name ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Toko Lisensi Plugin</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="fb-header">Toko Lisensi SnackRead</div>
    <div class="homepage-container">
        <h1>Plugin Premium Kami</h1>
        <p>Dapatkan lisensi resmi untuk plugin premium kami dan nikmati semua fiturnya.</p>
        
        <div class="plugin-grid">
            <?php if (empty($plugins)): ?>
                <p>Saat ini belum ada plugin yang tersedia.</p>
            <?php else: ?>
                <?php foreach ($plugins as $plugin): ?>
                    <div class="plugin-card">
                        <h3><?php echo htmlspecialchars($plugin['plugin_name']); ?></h3>
                        <div class="price">Rp <?php echo number_format($plugin['price_per_year']); ?><span>/tahun</span></div>
                        <div class="plugin-actions">
                            <a href="purchase.php?plugin=<?php echo htmlspecialchars($plugin['plugin_identifier']); ?>" class="button primary">Beli Lisensi</a>
                            <?php if (!empty($plugin['download_link'])): ?>
                                <a href="<?php echo htmlspecialchars($plugin['download_link']); ?>" class="button secondary" target="_blank">Download</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>