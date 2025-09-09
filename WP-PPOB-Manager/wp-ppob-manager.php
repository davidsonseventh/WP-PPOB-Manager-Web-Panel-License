<?php
/**
 * Plugin Name:       WP PPOB Manager
 * Plugin URI:        https://example.com/
 * Description:       Plugin PPOB lengkap untuk layanan Payment Point Online Bank yang terintegrasi penuh dengan WooCommerce.
 * Version:           1.0.0
 * Author:            Your Name
 * Author URI:        https://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-ppob
 * Domain Path:       /languages
 * WC requires at least: 6.0
 * WC tested up to: 8.8
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define Constants
define('WPPPOB_VERSION', '1.0.0');
define('WPPPOB_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPPPOB_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPPPOB_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Fungsi utama untuk menjalankan plugin setelah semua plugin lain dimuat.
 */
function wppob_run_plugin_init() {
    // Periksa apakah WooCommerce aktif
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'wppob_woocommerce_missing_notice_init');
        return;
    }

    // Memuat file loader utama
    require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-loader.php';

    // Inisialisasi plugin melalui class loader
    WPPPOB_Loader::get_instance();
}
add_action('plugins_loaded', 'wppob_run_plugin_init');

/**
 * Fungsi untuk menampilkan pesan error jika WooCommerce tidak aktif.
 */
function wppob_woocommerce_missing_notice_init() {
    echo '<div class="error"><p><strong>WP PPOB Manager Dinonaktifkan:</strong> Plugin ini membutuhkan WooCommerce untuk berfungsi. Silakan instal dan aktifkan WooCommerce.</p></div>';
}

// Hook untuk aktivasi dan deaktivasi (dijalankan bahkan sebelum plugins_loaded)
require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-activator.php';
require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-deactivator.php';
register_activation_hook(__FILE__, ['WPPPOB_Activator', 'activate']);
register_deactivation_hook(__FILE__, ['WPPPOB_Deactivator', 'deactivate']);