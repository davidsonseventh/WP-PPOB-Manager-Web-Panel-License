<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package WP PPOB Manager
 */

// Jika uninstall tidak dipanggil dari WordPress, hentikan.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 1. Hapus tabel kustom yang dibuat oleh plugin
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wppob_balances");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wppob_transactions");

// 2. Hapus semua options yang disimpan di tabel wp_options
$options_to_delete = [
    'wppob_api_provider',
    'wppob_api_username',
    'wppob_api_key',
    'wppob_profit_type',
    'wppob_profit_amount',
    'wppob_last_sync',
    'wppob_version',
];

foreach ($options_to_delete as $option_name) {
    delete_option($option_name);
}

// 3. Hapus semua produk WooCommerce yang dibuat oleh plugin (opsional, tapi disarankan)
$product_ids = $wpdb->get_col(
    $wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value LIKE %s",
        'wppob-%'
    )
);

if (!empty($product_ids)) {
    foreach ($product_ids as $product_id) {
        wp_delete_post($product_id, true); // true untuk menghapus permanen
    }
}

// 4. Hapus jadwal cron
wp_clear_scheduled_hook('wppob_hourly_sync');