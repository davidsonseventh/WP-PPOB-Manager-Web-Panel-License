<?php
require_once '../config.php';
require_once 'auth-check.php';

// (Logika PHP untuk menyimpan dan mengambil data tetap sama seperti sebelumnya)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['settings'])) {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO gateway_settings (gateway_code, setting_key, setting_value) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    
    // Pastikan semua checkbox memiliki nilai default jika tidak dicentang
    foreach (SUPPORTED_GATEWAYS as $gateway_code) {
        $_POST['settings'][$gateway_code]['is_enabled'] = isset($_POST['settings'][$gateway_code]['is_enabled']) ? 1 : 0;
        $_POST['settings'][$gateway_code]['is_live'] = isset($_POST['settings'][$gateway_code]['is_live']) ? 1 : 0;
    }

    foreach ($_POST['settings'] as $gateway_code => $gateway_settings) {
        foreach ($gateway_settings as $key => $value) {
            $stmt->execute([$gateway_code, $key, trim($value)]);
        }
    }
    $pdo->commit();
    $success_message = "Pengaturan berhasil disimpan!";
}

$settings = [];
$stmt_select = $pdo->query("SELECT * FROM gateway_settings");
while ($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['gateway_code']][$row['setting_key']] = $row['setting_value'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Pengaturan Payment Gateway</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header">
                <h2>Pengaturan Payment Gateway</h2>
            </div>

            <?php if (isset($success_message)) { echo "<div class='success'>$success_message</div>"; } ?>

            <form action="" method="POST">
                <?php foreach (SUPPORTED_GATEWAYS as $gateway): ?>
                    <div class="gateway-settings-box">
                        <div class="gateway-header">
                            <h3><?php echo ucfirst($gateway); ?></h3>
                            <div class="checkbox-group">
                                <label for="is_enabled_<?php echo $gateway; ?>">Aktifkan Metode Pembayaran</label>
                                <input type="checkbox" name="settings[<?php echo $gateway; ?>][is_enabled]" value="1" id="is_enabled_<?php echo $gateway; ?>" <?php echo (($settings[$gateway]['is_enabled'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            </div>
                        </div>
                        
                        <div class="checkbox-group">
                            <input type="checkbox" name="settings[<?php echo $gateway; ?>][is_live]" value="1" id="is_live_<?php echo $gateway; ?>" <?php echo (($settings[$gateway]['is_live'] ?? 0) == 1) ? 'checked' : ''; ?>>
                            <label for="is_live_<?php echo $gateway; ?>">Aktifkan Mode Live</label>
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>API Key / Client ID (Sandbox)</label>
                                <input type="text" name="settings[<?php echo $gateway; ?>][api_key_sandbox]" value="<?php echo htmlspecialchars($settings[$gateway]['api_key_sandbox'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>API Key / Client ID (Live)</label>
                                <input type="text" name="settings[<?php echo $gateway; ?>][api_key_live]" value="<?php echo htmlspecialchars($settings[$gateway]['api_key_live'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Secret Key / Server Key (Sandbox)</label>
                                <input type="text" name="settings[<?php echo $gateway; ?>][secret_key_sandbox]" value="<?php echo htmlspecialchars($settings[$gateway]['secret_key_sandbox'] ?? ''); ?>">
                            </div>
                            <div class="form-group">
                                <label>Secret Key / Server Key (Live)</label>
                                <input type="text" name="settings[<?php echo $gateway; ?>][secret_key_live]" value="<?php echo htmlspecialchars($settings[$gateway]['secret_key_live'] ?? ''); ?>">
                            </div>
                        </div>
                        <?php if ($gateway === 'paypal'): ?>
                            <div class="form-group paypal-rate">
                                <label>Konversi Rate (1 USD = ? IDR)</label>
                                <input type="number" step="1" name="settings[<?php echo $gateway; ?>][usd_rate]" value="<?php echo htmlspecialchars($settings[$gateway]['usd_rate'] ?? '15000'); ?>">
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <button type="submit">Simpan Semua Pengaturan</button>
            </form>
        </div>
    </div>
</body>
</html>