<?php
defined('ABSPATH') || exit;

final class WPPPOB_Loader {
    private static $_instance = null;

    public static function get_instance() {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    private function load_dependencies() {
        // Helpers & API
        require_once WPPPOB_PLUGIN_DIR . 'includes/helpers.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-api.php';

        // Fungsionalitas Inti
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-products.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-orders.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-balances.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-users.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-frontend.php';
        require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-ajax.php';

        // Hanya muat file admin jika di area admin
        if (is_admin()) {
            require_once WPPPOB_PLUGIN_DIR . 'includes/class-wppob-admin.php';
        }
    }

    private function init_hooks() {
        new WPPPOB_Products();
        new WPPPOB_Orders();
        new WPPPOB_Balances();
        new WPPPOB_Users();
        new WPPPOB_Frontend();
        new WPPPOB_Ajax();

        if (is_admin()) {
            new WPPPOB_Admin();
        }
    }
}