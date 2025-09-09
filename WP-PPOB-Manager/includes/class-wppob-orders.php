<?php
defined('ABSPATH') || exit;

class WPPPOB_Orders {
    public function __construct() {
        add_action('woocommerce_order_status_processing', [$this, 'handle_order'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_customer_no_to_item'], 10, 4);
    }
    
    public function save_customer_no_to_item($item, $cart_item_key, $values, $order) {
        if (isset($values['wppob_customer_no'])) {
            $item->add_meta_data('_wppob_customer_no', sanitize_text_field($values['wppob_customer_no']), true);
        }
    }

    public function handle_order($order_id, $order) {
        global $wpdb;
        if ($order->get_meta('_wppob_processed')) return;

        foreach ($order->get_items() as $item_id => $item) {
            $product = $item->get_product();
            if (!$product || strpos($product->get_sku(), 'wppob-') !== 0) continue;

            $customer_no = $item->get_meta('_wppob_customer_no');
            if (empty($customer_no)) {
                $order->add_order_note('Gagal: Nomor Tujuan PPOB tidak ditemukan.');
                continue;
            }
            
            $ref_id = "WP-{$order_id}-{$item_id}";
            $base_price = (float)$product->get_meta('_wppob_base_price');
            $sale_price = (float)$item->get_total();
            $profit = $sale_price - $base_price;
            $sku = str_replace('wppob-', '', $product->get_sku());

            // Masukkan ke tabel transaksi kustom
            $wpdb->insert("{$wpdb->prefix}wppob_transactions", [
                'user_id'       => $order->get_customer_id(),
                'order_id'      => $order_id,
                'product_code'  => $sku,
                'customer_no'   => $customer_no,
                'base_price'    => $base_price,
                'sale_price'    => $sale_price,
                'profit'        => $profit,
                'status'        => 'processing',
                'remote_trx_id' => $ref_id,
            ]);
            $local_trx_id = $wpdb->insert_id;

            // Panggil API
            $api = new WPPPOB_API();
            $response = $api->transaction($sku, $customer_no, $ref_id);

            // Proses response
            if (isset($response['data']['status'])) {
                $status = strtolower($response['data']['status']);
                $message = sanitize_text_field($response['data']['message'] ?? '');
                
                $new_status = 'pending';
                if (in_array($status, ['sukses', 'success'])) {
                    $new_status = 'success';
                    $order->add_order_note("Transaksi PPOB #{$local_trx_id} sukses: {$message}");
                    $order->update_status('completed');
                } elseif (in_array($status, ['gagal', 'fail', 'error'])) {
                    $new_status = 'failed';
                    $order->add_order_note("Transaksi PPOB #{$local_trx_id} gagal: {$message}");
                    $order->update_status('failed');
                } else { // Pending
                    $order->add_order_note("Transaksi PPOB #{$local_trx_id} sedang diproses oleh server.");
                }

                $wpdb->update("{$wpdb->prefix}wppob_transactions",
                    ['status' => $new_status, 'response_message' => $message],
                    ['id' => $local_trx_id]
                );

            } else {
                $error_msg = $response['message'] ?? 'Respons tidak valid dari server API.';
                $order->add_order_note("Transaksi PPOB #{$local_trx_id} gagal: {$error_msg}");
                $order->update_status('failed');
                 $wpdb->update("{$wpdb->prefix}wppob_transactions",
                    ['status' => 'failed', 'response_message' => $error_msg],
                    ['id' => $local_trx_id]
                );
            }
        }

        $order->update_meta_data('_wppob_processed', true);
        $order->save();
    }
}