<?php
defined('ABSPATH') || exit;

class WPPPOB_Frontend {

    public function __construct() {
        // Mendaftarkan shortcode
        add_shortcode('wppob_form', [$this, 'render_transaction_form']);
        add_shortcode('wppob_user_dashboard', [$this, 'render_user_dashboard']);
        add_shortcode('wppob_topup_form', [$this, 'render_topup_form']);
        add_shortcode('wppob_app_view', [$this, 'render_app_view']);
        add_shortcode('wppob_custom_view', [$this, 'render_custom_view']);
        // Memuat aset (CSS & JS)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        
        // Menambahkan input nomor pelanggan di halaman checkout
        add_action('woocommerce_before_add_to_cart_button', [$this, 'add_customer_no_field']);
        add_filter('woocommerce_add_to_cart_validation', [$this, 'validate_customer_no_field'], 10, 3);
        add_filter('woocommerce_add_cart_item_data', [$this, 'save_customer_no_field'], 10, 3);
        
        // Menampilkan nomor pelanggan di keranjang dan checkout
        add_filter('woocommerce_get_item_data', [$this, 'display_customer_no_in_cart'], 10, 2);
        add_action('woocommerce_checkout_create_order_line_item', [$this, 'save_customer_no_to_order_item'], 10, 4);
    }

   // Lokasi: includes/class-wppob-frontend.php

public function enqueue_frontend_assets() {
    global $post;
    // Tambahkan 'wppob_custom_view' ke dalam pengecekan
    if (is_a($post, 'WP_Post') && (has_shortcode($post->post_content, 'wppob_form') || has_shortcode($post->post_content, 'wppob_app_view') || has_shortcode($post->post_content, 'wppob_user_dashboard') || has_shortcode($post->post_content, 'wppob_custom_view'))) {
        wp_enqueue_style('wppob-frontend-css', WPPPOB_PLUGIN_URL . 'assets/css/frontend.css', [], WPPPOB_VERSION);
        wp_enqueue_script('wppob-frontend-js', WPPPOB_PLUGIN_URL . 'assets/js/frontend.js', ['jquery'], WPPPOB_VERSION, true);
        wp_localize_script('wppob-frontend-js', 'wppob_frontend_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wppob_frontend_nonce')
        ]);
    }
}

    public function render_transaction_form() {
        ob_start(); include_once WPPPOB_PLUGIN_DIR . 'templates/frontend/form-ppob.php'; return ob_get_clean();
    }
    
    public function render_user_dashboard() {
        if (!is_user_logged_in()) { return '<p>' . __('Silakan login untuk mengakses dasbor.', 'wp-ppob') . '</p>'; }
        ob_start(); include_once WPPPOB_PLUGIN_DIR . 'templates/frontend/dashboard-user.php'; return ob_get_clean();
    }

    public function render_topup_form() {
         if (!is_user_logged_in()) { return '<p>' . __('Silakan login untuk top-up saldo.', 'wp-ppob') . '</p>'; }
        ob_start(); include_once WPPPOB_PLUGIN_DIR . 'templates/frontend/topup-saldo.php'; return ob_get_clean();
    }
    
    public function render_app_view() {
        ob_start(); include_once WPPPOB_PLUGIN_DIR . 'templates/frontend/form-app-view.php'; return ob_get_clean();
    }
    
    public function add_customer_no_field() {
        global $product;
        if ($product && strpos($product->get_sku(), 'wppob-') === 0) {
            echo '<div class="wppob-customer-no-field" style="margin-bottom:15px;">
                <label for="wppob_customer_no">' . __('Nomor Tujuan (No. HP/ID Pelanggan)', 'wp-ppob') . '&nbsp;<abbr class="required" title="required">*</abbr></label>
                <input type="text" id="wppob_customer_no" name="wppob_customer_no" class="input-text" required>
            </div>';
        }
    }

    public function validate_customer_no_field($passed, $product_id, $quantity) {
        $product = wc_get_product($product_id);
        if ($product && strpos($product->get_sku(), 'wppob-') === 0 && empty($_POST['wppob_customer_no'])) {
            wc_add_notice(__('Mohon masukkan nomor tujuan.', 'wp-ppob'), 'error');
            return false;
        }
        return $passed;
    }

    public function save_customer_no_field($cart_item_data, $product_id, $variation_id) {
        if (isset($_POST['wppob_customer_no'])) {
            $cart_item_data['wppob_customer_no'] = sanitize_text_field($_POST['wppob_customer_no']);
        }
        return $cart_item_data;
    }
    
    public function display_customer_no_in_cart($item_data, $cart_item) {
        if (isset($cart_item['wppob_customer_no'])) {
            $item_data[] = [
                'key'     => __('Nomor Tujuan', 'wp-ppob'),
                'value'   => wc_clean($cart_item['wppob_customer_no']),
                'display' => '',
            ];
        }
        return $item_data;
    }


/**
     * Merender tampilan kategori kustom dari shortcode [wppob_custom_view].
     */
    public function render_custom_view() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';
        $all_categories = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY sort_order ASC, name ASC");

        // Susun kategori ke dalam struktur hierarki (induk -> anak)
        $categories_tree = [];
        foreach ($all_categories as $category) {
            if ($category->parent_id == 0) {
                // Ini adalah kategori utama
                $categories_tree[$category->id] = [
                    'details' => $category,
                    'children' => []
                ];
            }
        }

        foreach ($all_categories as $category) {
            if ($category->parent_id != 0 && isset($categories_tree[$category->parent_id])) {
                // Ini adalah sub-kategori
                $categories_tree[$category->parent_id]['children'][] = $category;
            }
        }

        // Kirim data ke file template
        ob_start();
        include WPPPOB_PLUGIN_DIR . 'templates/frontend/view-custom-categories.php';
        return ob_get_clean();
    }


    public function save_customer_no_to_order_item($item, $cart_item_key, $values, $order) {
        if (isset($values['wppob_customer_no'])) {
            $item->add_meta_data(__('Nomor Tujuan', 'wp-ppob'), $values['wppob_customer_no']);
        }
    }
}