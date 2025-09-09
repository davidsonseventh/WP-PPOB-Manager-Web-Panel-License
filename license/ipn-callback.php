<?php
require_once 'config.php';
require_once 'includes/functions.php';

$gateway = $_GET['gateway'] ?? 'unknown';

// Ambil payload notifikasi
$payload = file_get_contents('php://input');
$data = json_decode($payload, true);

switch ($gateway) {
    case 'duitku':
        // 1. Ambil Secret Key Anda dari database
        $settings = get_gateway_settings($pdo, 'duitku');
        $merchant_key = $settings['is_live'] ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];
        
        // 2. Ambil data notifikasi yang dikirim oleh Duitku via POST
        $merchantCode = $_POST['merchantCode'] ?? '';
        $amount = $_POST['amount'] ?? '';
        $merchantOrderId = $_POST['merchantOrderId'] ?? ''; // Ini adalah ID Transaksi dari sistem kita
        $signature = $_POST['signature'] ?? '';
        $resultCode = $_POST['resultCode'] ?? '';

        // 3. Buat signature pembanding untuk verifikasi keamanan
        // Formulanya: hash(sha256, merchantCode + amount + merchantOrderId + merchantKey)
        $my_signature = hash('sha256', $merchantCode . $amount . $merchantOrderId . $merchant_key);

        // 4. Verifikasi: Hanya proses jika signature cocok dan status pembayaran sukses ('00')
        if ($signature === $my_signature && $resultCode == '00') {
            $transaction_id = $merchantOrderId;
            $gateway_ref_id = $_POST['reference'] ?? 'N/A'; // Referensi dari Duitku

            // Panggil fungsi universal untuk memproses pembayaran yang sukses
            process_successful_payment($pdo, $transaction_id, $gateway_ref_id);
        }
        break;

    case 'tripay':
        // 1. Ambil Private Key Anda dari database
        $settings = get_gateway_settings($pdo, 'tripay');
        $private_key = $settings['is_live'] ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];

        // 2. Ambil notifikasi JSON mentah yang dikirim oleh Tripay
        $payload = file_get_contents('php://input');
        
        // 3. Ambil signature dari header
        $signature_from_header = isset($_SERVER['HTTP_X_CALLBACK_SIGNATURE']) ? $_SERVER['HTTP_X_CALLBACK_SIGNATURE'] : '';

        // 4. Buat signature pembanding untuk verifikasi keamanan
        $my_signature = hash_hmac('sha256', $payload, $private_key);

        // 5. Verifikasi: Hanya proses jika signature cocok
        if ($signature_from_header === $my_signature) {
            $data = json_decode($payload, true);
            
            // 6. Cek status pembayaran: Hanya proses jika statusnya 'PAID'
            if (isset($data['status']) && $data['status'] == 'PAID') {
                $transaction_id = $data['merchant_ref']; // ID Transaksi dari sistem kita
                $gateway_ref_id = $data['reference'];    // Referensi dari Tripay

                // Panggil fungsi universal untuk memproses pembayaran yang sukses
                process_successful_payment($pdo, $transaction_id, $gateway_ref_id);
            }
        }
        break;

    case 'faucetpay':
        $settings = get_gateway_settings($pdo, 'faucetpay');
        // Di FaucetPay, "API Password" untuk IPN sama untuk Live/Sandbox, kita asumsikan disimpan di 'secret_key_live'
        $api_password = $settings['secret_key_live'] ?? ''; 

        // Ambil data POST dari FaucetPay
        $token = $_POST['token'] ?? '';
        $payment_id = $_POST['payment_id'] ?? '';
        $transaction_id = $_POST['item_id'] ?? ''; // ID transaksi dari sistem kita
        $currency = $_POST['currency'] ?? '';
        
        // Verifikasi token (metode keamanan FaucetPay)
        $my_token = md5($api_password . '|' . $payment_id);
        
        if ($token === $my_token && $currency === 'USD') {
            process_successful_payment($pdo, $transaction_id, $payment_id);
        }
        break;
        
    case 'midtrans':
        // ... (Kode callback Midtrans dengan SDK) ...
        break;
        
    // case 'paypal': (PayPal menggunakan webhook, bukan IPN sederhana)
}

http_response_code(200);
echo "OK";
?>