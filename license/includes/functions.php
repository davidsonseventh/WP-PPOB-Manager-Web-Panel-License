<?php
// Pastikan config dimuat sebelum file ini
if (!defined('LICENSE_ENCRYPTION_KEY')) {
    // Sesuaikan path agar selalu benar dari manapun file ini dipanggil
    require_once dirname(__DIR__) . '/config.php';
}

/**
 * Membuat kunci lisensi terenkripsi.
 */
function generate_license_key($domain, $expiry_date) {
    // Bersihkan domain
    $domain = strtolower(trim($domain));
    $domain = preg_replace('/^https?:\/\//', '', $domain);
    $domain = preg_replace('/^www\./', '', $domain);
    $domain = rtrim($domain, '/');

    $data_to_encrypt = "$domain|$expiry_date";
    $encrypted_data = openssl_encrypt($data_to_encrypt, 'aes-256-cbc', LICENSE_ENCRYPTION_KEY, 0, LICENSE_ENCRYPTION_IV);
    return base64_encode($encrypted_data);
}

/**
 * FUNGSI BARU YANG HILANG: Mengambil pengaturan gateway dari database.
 */
function get_gateway_settings($pdo, $gateway_code) {
    $settings = [];
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM gateway_settings WHERE gateway_code = ?");
    $stmt->execute([$gateway_code]);
    
    // Inisialisasi nilai default
    $defaults = [
        'is_live' => 0,
        'api_key_sandbox' => '',
        'secret_key_sandbox' => '',
        'api_key_live' => '',
        'secret_key_live' => ''
    ];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    // Gabungkan dengan nilai default untuk memastikan semua key ada
    return array_merge($defaults, $settings);
}