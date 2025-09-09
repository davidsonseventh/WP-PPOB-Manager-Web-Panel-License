<?php
defined('ABSPATH') || exit;

class WPPPOB_Balances {
    
    public function __construct() {
        // Hook saat order WooCommerce selesai untuk memproses top-up saldo
        add_action('woocommerce_order_status_completed', [$this, 'process_topup_from_order'], 10, 1);
    }

    /**
     * Mengambil saldo terakhir dari seorang pengguna.
     * @param int $user_id ID Pengguna
     * @return float Saldo
     */
    public static function get($user_id) {
        global $wpdb;
        $balance = $wpdb->get_var(
            $wpdb->prepare("SELECT balance FROM {$wpdb->prefix}wppob_balances WHERE user_id = %d", $user_id)
        );
        return $balance ? (float) $balance : 0.00;
    }

    /**
     * Menambahkan saldo ke akun pengguna.
     * @param int $user_id
     * @param float $amount
     * @param string $context Konteks penambahan (misal: 'Top-up' atau 'Refund')
     */
    public static function add($user_id, $amount, $context = 'Penambahan manual') {
        global $wpdb;
        $current_balance = self::get($user_id);
        $new_balance = $current_balance + abs(floatval($amount));

        // insert or update
        return $wpdb->query(
            $wpdb->prepare(
                "INSERT INTO {$wpdb->prefix}wppob_balances (user_id, balance) VALUES (%d, %f)
                 ON DUPLICATE KEY UPDATE balance = %f",
                $user_id,
                $new_balance,
                $new_balance
            )
        );
    }

    /**
     * Mengurangi saldo dari akun pengguna.
     * @param int $user_id
     * @param float $amount
     * @param string $context Konteks pengurangan (misal: 'Pembelian Produk')
     * @return bool True jika berhasil, false jika saldo tidak cukup.
     */
    public static function deduct($user_id, $amount, $context = 'Pengurangan manual') {
        global $wpdb;
        $current_balance = self::get($user_id);
        $amount_to_deduct = abs(floatval($amount));

        if ($current_balance < $amount_to_deduct) {
            return false; // Saldo tidak cukup
        }

        $new_balance = $current_balance - $amount_to_deduct;

        return $wpdb->update(
            "{$wpdb->prefix}wppob_balances",
            ['balance' => $new_balance],
            ['user_id' => $user_id]
        );
    }

    /**
     * Memproses order top-up ketika statusnya menjadi 'completed'.
     * @param int $order_id
     */
    public function process_topup_from_order($order_id) {
        $order = wc_get_order($order_id);
        if (!$order || $order->get_meta('_wppob_topup_processed')) {
            return;
        }

        $user_id = $order->get_customer_id();
        if (!$user_id) return;
        
        $topup_amount = 0;

        foreach ($order->get_items() as $item) {
            $product = $item->get_product();
            // Produk top-up memiliki SKU khusus, misal 'saldo-topup'
            if ($product && $product->get_sku() === 'saldo-topup') {
                $topup_amount += $item->get_total();
            }
        }
        
        if ($topup_amount > 0) {
            self::add($user_id, $topup_amount, "Top-up dari Order #{$order_id}");
            $order->add_order_note(sprintf(__('Saldo sebesar %s telah ditambahkan ke akun pelanggan.', 'wp-ppob'), wppob_format_rp($topup_amount)));
            $order->update_meta_data('_wppob_topup_processed', true);
            $order->save();
        }
    }
}