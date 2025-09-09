<?php
require_once 'config.php';
require_once 'includes/functions.php';

// Membutuhkan file autoload dari Composer untuk gateway berbasis SDK
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// 1. Validasi Input dari form purchase.php
$plugin_identifier = trim($_POST['plugin_identifier'] ?? '');
$domain = filter_input(INPUT_POST, 'domain', FILTER_SANITIZE_URL);
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$phone = preg_replace('/[^0-9\+]/', '', $_POST['phone'] ?? '');
$duration_value = filter_input(INPUT_POST, 'duration_value', FILTER_VALIDATE_INT);
$duration_unit = in_array($_POST['duration_unit'], ['days', 'months', 'years']) ? $_POST['duration_unit'] : 'years';
$gateway = in_array($_POST['gateway'], SUPPORTED_GATEWAYS) ? $_POST['gateway'] : '';

if (empty($plugin_identifier) || !$domain || !$email || !$duration_value || empty($gateway)) {
    die("Error: Data yang Anda masukkan tidak lengkap atau tidak valid. Silakan kembali dan coba lagi.");
}

// 2. Ambil detail plugin dari database
$stmt = $pdo->prepare("SELECT * FROM plugins WHERE plugin_identifier = ?");
$stmt->execute([$plugin_identifier]);
$plugin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$plugin) {
    die("Error: Plugin dengan identifier '$plugin_identifier' tidak ditemukan.");
}
$plugin_id = $plugin['id'];

// 3. Hitung harga final dan tanggal kedaluwarsa
$price_per_year = $plugin['price_per_year'];
$final_amount = 0;
switch ($duration_unit) {
    case 'months': $final_amount = ($price_per_year / 12) * $duration_value; break;
    case 'days': $final_amount = ($price_per_year / 365) * $duration_value; break;
    default: $final_amount = $price_per_year * $duration_value;
}
$final_amount = ceil($final_amount);
$expiry_date = (new DateTime())->modify("+" . $duration_value . " " . $duration_unit)->format('Y-m-d');

// 4. Buat record transaksi di database
$stmt_insert = $pdo->prepare(
    "INSERT INTO transactions (plugin_id, license_domain, license_email, license_phone, expiry_date, amount, currency, gateway, status) 
     VALUES (?, ?, ?, ?, ?, ?, 'IDR', ?, 'pending')"
);
$stmt_insert->execute([$plugin_id, $domain, $email, $phone, $expiry_date, $final_amount, $gateway]);
$transaction_id = $pdo->lastInsertId();

// 5. Arahkan ke gateway yang dipilih
$settings = get_gateway_settings($pdo, $gateway);
$user_details = ['email' => $email, 'phone' => $phone];
$plugin_name = $plugin['plugin_name'];

switch ($gateway) {
    
    
    
    case 'manual_transfer':
        // Update status transaksi menjadi 'menunggu_pembayaran'
        $pdo->prepare("UPDATE transactions SET status = 'menunggu_pembayaran' WHERE id = ?")
            ->execute([$transaction_id]);
        // Arahkan ke halaman instruksi pembayaran
        header('Location: manual-payment.php?trx_id=' . $transaction_id);
        exit();
        break;
    
    
    case 'midtrans':
        initiate_midtrans_payment($settings, $transaction_id, $final_amount, $user_details);
        break;
    case 'duitku':
        initiate_duitku_payment($settings, $transaction_id, $final_amount, $plugin_name, $user_details);
        break;
    case 'tripay':
        initiate_tripay_payment($settings, $transaction_id, $final_amount, $plugin_name, $user_details);
        break;
    case 'faucetpay':
        initiate_faucetpay_payment($settings, $transaction_id, $final_amount, $plugin_name);
        break;
    case 'paypal':
        initiate_paypal_payment($settings, $transaction_id, $final_amount, $plugin_name);
        break;
    case 'xendit':
        die("Metode pembayaran Xendit akan segera hadir.");
        break;
    default:
        die("Metode pembayaran tidak valid.");
}

// --- FUNGSI-FUNGSI UNTUK SETIAP GATEWAY ---


// --- FUNGSI BARU UNTUK PEMBAYARAN MANUAL ---
function initiate_manual_payment($pdo, $trx_id, $plugin_name, $amount, $gateway, $user, $domain) {
    $pdo->prepare("UPDATE transactions SET status = 'menunggu_pembayaran' WHERE id = ?")
        ->execute([$trx_id]);

    $admin_email = "ganti-dengan-email-admin-anda@gmail.com"; // **GANTI INI**
    $subject = "Pesanan Lisensi Baru (Manual) - Transaksi #$trx_id";
    $body = "Detail pesanan:\nID: $trx_id\nPlugin: $plugin_name\nDomain: $domain\nTotal: Rp " . number_format($amount) . "\n\nKontak Pelanggan:\nEmail: " . $user['email'] . "\nTelepon/WA: " . $user['phone'];
    mail($admin_email, $subject, $body);

    $_SESSION['manual_payment_details'] = [
        'gateway' => ucfirst($gateway),
        'total' => number_format($amount)
    ];
    
    header('Location: /manual-payment.php?trx_id=' . $trx_id);
    exit();
}




function initiate_midtrans_payment($settings, $trx_id, $amount, $user) {
    \Midtrans\Config::$serverKey = $settings['is_live'] ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];
    \Midtrans\Config::$isProduction = (bool)($settings['is_live'] ?? 0);
    \Midtrans\Config::$isSanitized = true; \Midtrans\Config::$is3ds = true;
    
    $params = ['transaction_details' => ['order_id' => $trx_id, 'gross_amount' => $amount], 'customer_details' => $user];
    try {
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        $_SESSION['snap_token'] = $snapToken;
        header('Location: payment-page.php?trx_id=' . $trx_id);
        exit();
    } catch (Exception $e) { die('Error Midtrans: ' . $e->getMessage()); }
}

function initiate_duitku_payment($settings, $trx_id, $amount, $product_name, $user) {
    $is_live = (bool)($settings['is_live'] ?? 0);
    $merchant_code = $is_live ? $settings['api_key_live'] : $settings['api_key_sandbox'];
    $merchant_key = $is_live ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];
    $endpoint = $is_live ? 'https://passport.duitku.com/webapi/api/merchant/v2/inquiry' : 'https://sandbox.duitku.com/webapi/api/merchant/v2/inquiry';

    if (empty($merchant_code) || empty($merchant_key)) {
        die("Error: Duitku belum dikonfigurasi oleh admin.");
    }

    // --- PERBAIKAN DI SINI ---
    // Hapus variabel $datetime. Signature tidak lagi memerlukannya.
    $signature = hash('sha256', $merchant_code . $trx_id . $amount . $merchant_key);

    $params = json_encode([
        'merchantCode' => $merchant_code,
        'paymentAmount' => $amount,
        'merchantOrderId' => (string)$trx_id,
        'productDetails' => "Lisensi " . $product_name,
        'email' => $user['email'],
        'phoneNumber' => $user['phone'],
        'returnUrl' => SITE_URL . '/success.php?trx_id=' . $trx_id,
        'callbackUrl' => SITE_URL . '/ipn-callback.php?gateway=duitku',
        'signature' => $signature,
        'expiryPeriod' => 60
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE); // Ambil status HTTP
    curl_close($ch);
    
    $result = json_decode($response);

    // --- BLOK DEBUGGING ---
    // Jika tidak ada paymentUrl, hentikan skrip dan tampilkan semua informasi
    if (!isset($result->paymentUrl)) {
        echo "<h1>Proses Gagal</h1>";
        echo "<p>Server Duitku menolak permintaan. Berikut adalah detailnya:</p>";
        
        echo "<h3>Data yang Dikirim (Request):</h3>";
        echo "<pre>" . htmlspecialchars(json_encode(json_decode($params), JSON_PRETTY_PRINT)) . "</pre>";

        echo "<h3>Respons Mentah dari Duitku:</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        echo "<h3>Status Kode HTTP:</h3>";
        echo "<pre>" . $http_status . "</pre>";

        die(); // Hentikan eksekusi
    }
    // --- AKHIR BLOK DEBUGGING ---

    header('Location: ' . $result->paymentUrl);
    exit();
}

function initiate_tripay_payment($settings, $trx_id, $amount, $product_name, $user) {
    $is_live = (bool)($settings['is_live'] ?? 0);
    $api_key = $is_live ? $settings['api_key_live'] : $settings['api_key_sandbox'];
    $private_key = $is_live ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];
    $merchant_code = $is_live ? $settings['api_key_live'] : $settings['api_key_sandbox'];
    $endpoint = $is_live ? 'https://tripay.co.id/api/transaction/create' : 'https://tripay.co.id/api-sandbox/transaction/create';
    
    if (empty($api_key) || empty($private_key) || empty($merchant_code)) {
        die("Error: Tripay belum dikonfigurasi oleh admin.");
    }
    
    $signature = hash_hmac('sha256', $merchant_code . $trx_id . $amount, $private_key);
    $payload = [
        'method' => 'QRIS', 'merchant_ref' => $trx_id, 'amount' => $amount, 'customer_name' => $user['email'],
        'customer_email' => $user['email'], 'customer_phone' => $user['phone'],
        'order_items' => [['name' => "Lisensi " . $product_name, 'price' => $amount, 'quantity' => 1]],
        'callback_url' => SITE_URL . '/ipn-callback.php?gateway=tripay', 'return_url' => SITE_URL . '/success.php?trx_id=' . $trx_id,
        'signature' => $signature
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint); curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1); curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer " . $api_key]);
    
    $response = curl_exec($ch); curl_close($ch);
    $result = json_decode($response);
    if ($result && isset($result->success) && $result->success) {
        header('Location: ' . $result->data->checkout_url); exit();
    } else { die("Error Tripay: " . ($result->message ?? 'Gagal membuat permintaan pembayaran.')); }
}

function initiate_faucetpay_payment($settings, $trx_id, $amount_idr, $item_name) {
    $merchant_username = $settings['is_live'] ? $settings['api_key_live'] : $settings['api_key_sandbox'];
    if (empty($merchant_username)) { die("Error: FaucetPay belum dikonfigurasi oleh admin."); }
    
    $amount_usd = round($amount_idr / 15000, 2);
    $callback_url = SITE_URL . '/ipn-callback.php?gateway=faucetpay';

    echo "<form action='https://faucetpay.io/merchant/webscr' method='post' id='faucetpay_form'>";
    echo "<input type='hidden' name='merchant_username' value='" . htmlspecialchars($merchant_username) . "'>";
    echo "<input type='hidden' name='item_description' value='" . htmlspecialchars("Lisensi " . $item_name) . "'>";
    echo "<input type='hidden' name='item_id' value='" . htmlspecialchars($trx_id) . "'>";
    echo "<input type='hidden' name='amount1' value='" . htmlspecialchars($amount_usd) . "'>";
    echo "<input type='hidden' name='currency1' value='USD'>";
    echo "<input type='hidden' name='callback_url' value='" . htmlspecialchars($callback_url) . "'>";
    echo "<input type='hidden' name='success_url' value='" . SITE_URL . "/success.php?trx_id=$trx_id'>";
    echo "<input type='hidden' name='cancel_url' value='" . SITE_URL . "/purchase.php'>";
    echo "</form>";
    echo "<p>Anda akan diarahkan ke FaucetPay...</p>";
    echo "<script>document.getElementById('faucetpay_form').submit();</script>";
    exit();
}

function initiate_paypal_payment($settings, $trx_id, $amount_idr, $product_name) {
    $is_live = (bool)($settings['is_live'] ?? 0);
    $client_id = $is_live ? $settings['api_key_live'] : $settings['api_key_sandbox'];
    $client_secret = $is_live ? $settings['secret_key_live'] : $settings['secret_key_sandbox'];
    $usd_rate = floatval($settings['usd_rate'] ?? 15000);
    $amount_usd = round($amount_idr / $usd_rate, 2);

    $environment = $is_live ? new \PayPalCheckoutSdk\Core\ProductionEnvironment($client_id, $client_secret) : new \PayPalCheckoutSdk\Core\SandboxEnvironment($client_id, $client_secret);
    $client = new \PayPalCheckoutSdk\Core\PayPalHttpClient($environment);

    $request = new \PayPalCheckoutSdk\Orders\OrdersCreateRequest();
    $request->prefer('return=representation');
    $request->body = [ "intent" => "CAPTURE", "purchase_units" => [["reference_id" => $trx_id, "description" => "Lisensi " . $product_name, "amount" => ["value" => (string)$amount_usd, "currency_code" => "USD"]]], "application_context" => ["return_url" => SITE_URL . "/paypal-return.php?trx_id=$trx_id", "cancel_url" => SITE_URL . "/purchase.php"] ];

    try {
        $response = $client->execute($request);
        foreach ($response->result->links as $link) {
            if ($link->rel == 'approve') {
                header("Location: " . $link->href);
                exit();
            }
        }
    } catch (Exception $e) { die("Error PayPal: " . $e->getMessage()); }
}
?>