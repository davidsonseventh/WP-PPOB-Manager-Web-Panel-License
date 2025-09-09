<?php
require_once '../config.php';
require_once 'auth-check.php';
require_once '../includes/functions.php'; // Kita akan buat file functions.php

$generated_key = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $domain = trim($_POST['domain']);
    $expiry_date = $_POST['expiry_date'];
    $plugin_id = $_POST['plugin_id'];
    
    $generated_key = generate_license_key($domain, $expiry_date);

    $stmt = $pdo->prepare("INSERT INTO licenses (plugin_id, license_key, domain, expiry_date) VALUES (?, ?, ?, ?)");
    $stmt->execute([$plugin_id, $generated_key, $domain, $expiry_date]);
    $success_message = "Lisensi berhasil dibuat!";
}

$plugins = $pdo->query("SELECT * FROM plugins")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Buat Lisensi Manual</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="admin-wrapper">
        <?php include 'sidebar.php'; ?>
        <div class="admin-content">
            <div class="admin-header"><h2>Buat Lisensi Manual</h2></div>
            
            <?php if (isset($success_message)): ?>
                <div class="success">
                    <?php echo $success_message; ?>
                    <h3>Kunci Lisensi:</h3>
                    <pre><?php echo htmlspecialchars($generated_key); ?></pre>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <label>Pilih Plugin:</label>
                <select name="plugin_id" required>
                    <option value="">-- Pilih Plugin --</option>
                    <?php foreach($plugins as $plugin): ?>
                        <option value="<?php echo $plugin['id']; ?>"><?php echo htmlspecialchars($plugin['plugin_name']); ?></option>
                    <?php endforeach; ?>
                </select>
                
                <label>Nama Domain:</label>
                <input type="text" name="domain" placeholder="contoh.com" required>
                
                <label>Tanggal Kedaluwarsa:</label>
                <input type="date" name="expiry_date" required>
                
                <button type="submit">Buat Kunci</button>
            </form>
        </div>
    </div>
</body>
</html>