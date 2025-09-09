<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Ambil identifier plugin dari URL (?plugin=wppob-manager)
$plugin_identifier = trim($_GET['plugin'] ?? '');

if (empty($plugin_identifier)) {
    die("Error: Plugin tidak ditentukan.");
}

// Ambil detail plugin dari database
$stmt = $pdo->prepare("SELECT * FROM plugins WHERE plugin_identifier = ?");
$stmt->execute([$plugin_identifier]);
$plugin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plugin) {
    die("Error: Plugin tidak ditemukan.");
}

$plugin_name = $plugin['plugin_name'];
$price_per_year = $plugin['price_per_year'];

// Ambil gateway yang aktif dari database
$active_gateways = [];
$stmt_gateways = $pdo->query("SELECT gateway_code FROM gateway_settings WHERE setting_key = 'is_enabled' AND setting_value = '1'");
while ($row = $stmt_gateways->fetch(PDO::FETCH_ASSOC)) {
    $active_gateways[] = $row['gateway_code'];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Beli Lisensi untuk <?php echo htmlspecialchars($plugin_name); ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="fb-header">Layanan Lisensi SnackRead</div>
    <div class="container">
        <h1>Pembelian Lisensi untuk <?php echo htmlspecialchars($plugin_name); ?></h1>
        <p>Harga dasar: <strong>Rp <?php echo number_format($price_per_year); ?> / tahun</strong></p>

        <form action="process-purchase.php" method="POST">
            <input type="hidden" id="price_per_year" value="<?php echo $price_per_year; ?>">
            <input type="hidden" name="plugin_identifier" value="<?php echo htmlspecialchars($plugin['plugin_identifier']); ?>">

            <label for="domain">Nama Domain:</label>
            <input type="text" id="domain" name="domain" placeholder="contoh.com" required>
            <label for="email">Alamat Email:</label>
            <input type="email" id="email" name="email" placeholder="emailanda@contoh.com" required>
            <label for="phone">No. Telepon/WA (Opsional):</label>
            <input type="tel" id="phone" name="phone">
            
            <label for="duration_value">Masa Aktif:</label>
            <div class="duration-group">
                <input type="number" id="duration_value" name="duration_value" value="1" min="1" required>
                <select id="duration_unit" name="duration_unit">
                    <option value="years">Tahun</option>
                    <option value="months">Bulan</option>
                    <option value="days">Hari</option>
                </select>
            </div>
            <h3>Total Pembayaran: <span id="total_price">Rp <?php echo number_format($price_per_year); ?></span></h3>
            
            <?php if (!empty($active_gateways)): ?>
                <h4>Pilih Metode Pembayaran:</h4>
                <div class="payment-options">
                    
                    
                    
    <label>
    <input type="radio" name="gateway" value="manual_transfer">
    Transfer Bank Manual
</label>

<?php foreach ($active_gateways as $index => $gateway): ?>
    <label>
        <input type="radio" name="gateway" value="<?php echo $gateway; ?>" <?php echo ($index === 0 && !isset($_POST['gateway'])) ? 'checked' : ''; ?>>
        <?php echo ucfirst($gateway); ?>
    </label>
<?php endforeach; ?>
                    
                    <?php foreach ($active_gateways as $index => $gateway): ?>
                        <label>
                            <input type="radio" name="gateway" value="<?php echo $gateway; ?>" <?php echo ($index === 0) ? 'checked' : ''; ?>>
                            <?php echo ucfirst($gateway); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
                <button type="submit">Lanjutkan ke Pembayaran</button>
            <?php else: ?>
                <p class="error">Saat ini tidak ada metode pembayaran yang tersedia.</p>
            <?php endif; ?>
        </form>
    </div>
    <script src="calculator.js"></script>
</body>
</html>