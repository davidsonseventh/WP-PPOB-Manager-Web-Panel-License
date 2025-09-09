<?php
defined('ABSPATH') || exit;

// Kunci rahasia untuk validasi lisensi offline
define('WPPPOB_LICENSE_ENCRYPTION_KEY', 'ganti-dengan-kunci-rahasia-anda-yang-sangat-panjang');
define('WPPPOB_LICENSE_ENCRYPTION_IV', 'ganti-dengan-16-karakter-acak'); // Harus tepat 16 karakter

class WPPPOB_Admin {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        
        // Hook untuk menangani penyimpanan urutan kategori via AJAX (drag & drop)
        add_action('wp_ajax_wppob_update_category_order', [$this, 'ajax_update_category_order']);
        
        // Hook untuk update harga produk secara otomatis saat profit diubah
        add_action('update_option_wppob_profit_amount', [$this, 'handle_settings_update'], 10, 2);
        add_action('update_option_wppob_profit_type', [$this, 'handle_settings_update'], 10, 2);
        
        // Hook untuk memvalidasi kunci lisensi saat disimpan
        add_action('update_option_wppob_license_key', [$this, 'validate_license_key_offline'], 10, 2);
    }

    public function add_admin_menu() {
        add_menu_page('PPOB Manager', 'PPOB Manager', 'manage_options', 'wppob-dashboard', [$this, 'render_dashboard_page'], 'dashicons-store', 58);
        add_submenu_page('wppob-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'wppob-dashboard', [$this, 'render_dashboard_page']);
        add_submenu_page('wppob-dashboard', 'Transaksi', 'Transaksi', 'manage_options', 'wppob-transactions', [$this, 'render_transactions_page']);
        add_submenu_page('wppob-dashboard', 'Manajemen Produk', 'Produk', 'manage_options', 'wppob-products', [$this, 'render_products_page']);
        add_submenu_page('wppob-dashboard', 'Manajemen Pengguna', 'Pengguna', 'manage_options', 'wppob-users', [$this, 'render_users_page']);
        add_submenu_page('wppob-dashboard', 'Kategori Tampilan', 'Kategori Tampilan', 'manage_options', 'wppob-display-categories', [$this, 'render_display_categories_page']);
        add_submenu_page('wppob-dashboard', 'Pengaturan', 'Pengaturan', 'manage_options', 'wppob-settings', [$this, 'render_settings_page']);
        add_submenu_page(
        'wppob-dashboard',
        'Edit Gambar Massal',
        'Edit Gambar Massal',
        'manage_options',
        'wppob-bulk-edit-images', // slug baru
        [$this, 'render_bulk_edit_images_page'] // fungsi render baru
    );
       add_submenu_page('wppob-dashboard', 'Pengaturan', 'Pengaturan', 'manage_options', 'wppob-settings', [$this, 'render_settings_page']);

    }

    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'wppob-') !== false) {
            wp_enqueue_media();
            if (strpos($hook, 'wppob-display-categories') !== false) {
                wp_enqueue_script('jquery-ui-sortable');
            }
            wp_enqueue_style('wppob-admin-css', WPPPOB_PLUGIN_URL . 'assets/css/admin.css', [], WPPPOB_VERSION);
            wp_enqueue_script('wppob-admin-js', WPPPOB_PLUGIN_URL . 'assets/js/admin.js', ['jquery', 'jquery-ui-sortable'], WPPPOB_VERSION, true);
            wp_localize_script('wppob-admin-js', 'wppob_admin_params', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('wppob_admin_nonce')
            ]);
        }
    }


    public function render_bulk_edit_images_page() {
    include_once WPPPOB_PLUGIN_DIR . 'templates/admin/bulk-edit-images.php';
}


    public function handle_settings_update($old_value, $new_value) {
        if ($old_value !== $new_value && class_exists('WPPPOB_Products')) {
            $product_manager = new WPPPOB_Products();
            $product_manager->bulk_update_prices();
        }
    }

    public function ajax_update_category_order() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) { wp_send_json_error(); }

        $order = isset($_POST['order']) ? (array) $_POST['order'] : [];
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';

        foreach ($order as $sort_order => $item) {
            $id = intval(str_replace('cat-', '', $item['id']));
            $parent_id = !empty($item['parent_id']) ? intval(str_replace('cat-', '', $item['parent_id'])) : 0;
            if ($id > 0) {
                $wpdb->update($table_name, ['sort_order' => $sort_order + 1, 'parent_id' => $parent_id], ['id' => $id]);
            }
        }
        wp_send_json_success();
    }

    public function validate_license_key_offline($old_value, $new_value) {
        $license_key = trim($new_value);
        if (empty($license_key)) { update_option('wppob_license_status', 'invalid'); return; }

        $encrypted_data = base64_decode($license_key, true);
        if ($encrypted_data === false) { update_option('wppob_license_status', 'invalid'); return; }

        $decrypted_string = openssl_decrypt($encrypted_data, 'aes-256-cbc', WPPPOB_LICENSE_ENCRYPTION_KEY, 0, WPPPOB_LICENSE_ENCRYPTION_IV);
        if ($decrypted_string === false) { update_option('wppob_license_status', 'invalid'); return; }
        
        $parts = explode('|', $decrypted_string);
        if (count($parts) !== 2) { update_option('wppob_license_status', 'invalid'); return; }

        $license_domain = $parts[0];
        $expiry_date_str = $parts[1];

        $site_domain = home_url();
        $site_domain = preg_replace('/^https?:\/\//', '', $site_domain);
        $site_domain = preg_replace('/^www\./', '', $site_domain);
        $site_domain = rtrim($site_domain, '/');

        if ($license_domain !== $site_domain) { update_option('wppob_license_status', 'invalid'); return; }

        if (time() > strtotime($expiry_date_str)) { update_option('wppob_license_status', 'expired'); return; }

        update_option('wppob_license_status', 'valid');
    }

    public function register_settings() {
        register_setting('wppob_settings_group', 'wppob_api_username');
        register_setting('wppob_settings_group', 'wppob_api_key');
        register_setting('wppob_settings_group', 'wppob_profit_type');
        register_setting('wppob_settings_group', 'wppob_profit_amount');
        register_setting('wppob_settings_group', 'wppob_license_key');

        add_settings_section('wppob_api_section', __('Kredensial API', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_api_username', __('Username API', 'wp-ppob'), [$this, 'render_api_username_field'], 'wppob-settings', 'wppob_api_section');
        add_settings_field('wppob_api_key', __('API Key', 'wp-ppob'), [$this, 'render_api_key_field'], 'wppob-settings', 'wppob_api_section');
        
        add_settings_section('wppob_license_section', __('Aktivasi Lisensi Plugin', 'wp-ppob'), [$this, 'render_license_section_text'], 'wppob-settings');
        add_settings_field('wppob_license_key', __('Kode Lisensi', 'wp-ppob'), [$this, 'render_license_key_field'], 'wppob-settings', 'wppob_license_section');

        add_settings_section('wppob_profit_section', __('Pengaturan Keuntungan', 'wp-ppob'), null, 'wppob-settings');
        add_settings_field('wppob_profit_type', __('Tipe Keuntungan', 'wp-ppob'), [$this, 'render_profit_type_field'], 'wppob-settings', 'wppob_profit_section');
        add_settings_field('wppob_profit_amount', __('Jumlah Keuntungan', 'wp-ppob'), [$this, 'render_profit_amount_field'], 'wppob-settings', 'wppob_profit_section');
    }

    public function render_api_username_field() {
        $value = get_option('wppob_api_username', '');
        echo '<input type="text" name="wppob_api_username" value="' . esc_attr($value) . '" class="regular-text" placeholder="Username Digiflazz Anda">';
    }

    public function render_api_key_field() {
        $value = get_option('wppob_api_key', '');
        echo '<input type="password" name="wppob_api_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="Production API Key Anda">';
    }

    public function render_license_section_text() {
        $status = get_option('wppob_license_status', 'invalid');
        $status_text = ucfirst($status);
        $status_color = ($status === 'valid') ? 'green' : 'red';
        echo '<p>Masukkan kode lisensi Anda untuk mengaktifkan semua fitur premium, termasuk pengaturan keuntungan.</p>';
        echo '<strong>Status Lisensi: <span style="color:' . $status_color . '; text-transform: uppercase;">' . $status_text . '</span></strong>';
    }

    public function render_license_key_field() {
        $value = get_option('wppob_license_key', '');
        echo '<input type="text" name="wppob_license_key" value="' . esc_attr($value) . '" class="regular-text" placeholder="XXXX-XXXX-XXXX-XXXX">';
        echo '<p class="description">Tidak punya lisensi? <a href="https://kasir.webpulsa.shop/license/purchase.php?plugin=wppob-manager" target="_blank">Beli di sini</a>.</p>';
    }

    public function render_profit_type_field() {
        $is_valid = get_option('wppob_license_status', 'invalid') === 'valid';
        if (!$is_valid) {
            echo '<select disabled><option>Fitur Terkunci</option></select>';
            echo '<p class="description">Aktifkan lisensi untuk menggunakan fitur ini.</p>';
            return;
        }
        $value = get_option('wppob_profit_type', 'fixed');
        echo '<select name="wppob_profit_type"><option value="fixed"'.selected($value,'fixed',false).'>'.__('Tetap (Fixed)','wp-ppob').'</option><option value="percentage"'.selected($value,'percentage',false).'>'.__('Persentase (%)','wp-ppob').'</option></select>';
    }

    public function render_profit_amount_field() {
        $is_valid = get_option('wppob_license_status', 'invalid') === 'valid';
        if (!$is_valid) {
            echo '<input type="number" value="0" class="regular-text" readonly>';
            echo '<input type="hidden" name="wppob_profit_amount" value="0">';
            return;
        }
        $value = get_option('wppob_profit_amount', 1000);
        echo '<input type="number" name="wppob_profit_amount" value="' . esc_attr($value) . '" class="regular-text">';
        echo '<p class="description">' . __('Isi angka saja. Contoh: 1000 untuk profit Rp 1.000, atau 5 untuk profit 5%.', 'wp-ppob') . '</p>';
    }

    public function render_dashboard_page() { include_once WPPPOB_PLUGIN_DIR . 'templates/admin/dashboard.php'; }
    public function render_transactions_page() { include_once WPPPOB_PLUGIN_DIR . 'templates/admin/transactions.php'; }
    public function render_products_page() { include_once WPPPOB_PLUGIN_DIR . 'templates/admin/products.php'; }
    public function render_users_page() { include_once WPPPOB_PLUGIN_DIR . 'templates/admin/users.php'; }
    public function render_settings_page() { include_once WPPPOB_PLUGIN_DIR . 'templates/admin/settings.php'; }
    
    public function render_display_categories_page() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_wpnonce'])) {
    if (wp_verify_nonce($_POST['_wpnonce'], 'wppob_save_category_nonce')) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';
        $id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
        $is_new = ($id === 0);

        // 1. Siapkan data dasar yang selalu ada
        $data = [
            'name'                  => sanitize_text_field($_POST['cat_name']),
            'image_id'              => intval($_POST['cat_image_id']),
            'display_style'         => sanitize_key($_POST['display_style']),
            'product_display_style' => sanitize_key($_POST['product_display_style']),
            'product_display_mode'  => sanitize_key($_POST['product_display_mode']),
        ];

        // 2. Cek apakah kategori yang sedang diedit ini memiliki sub-kategori (anak)
        $has_children = false;
        if (!$is_new) {
            $child_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$table_name} WHERE parent_id = %d", $id));
            $has_children = ($child_count > 0);
        }

        // 3. Tentukan isi dari 'assigned_products' berdasarkan kondisi
        if ($has_children) {
            // JIKA punya anak, KATEGORI INDUK TIDAK BOLEH punya produk. Paksa kosong.
            $data['assigned_products'] = '[]';
        } else {
            // JIKA TIDAK punya anak (baik itu sub-kategori atau kategori baru),
            // ambil data produk dari form.
            $products = isset($_POST['assigned_products']) ? array_map('intval', $_POST['assigned_products']) : [];
            $data['assigned_products'] = json_encode($products);
        }

        // 4. Simpan ke database
        if ($is_new) {
            $wpdb->insert($table_name, $data);
        } else {
            $wpdb->update($table_name, $data, ['id' => $id]);
        }
        
        echo '<div class="notice notice-success is-dismissible"><p>Kategori berhasil disimpan.</p></div>';
    }
}
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_grid_nonce'])) {
            if (wp_verify_nonce($_POST['_grid_nonce'], 'wppob_save_grid_nonce')) {
                update_option('wppob_grid_columns', intval($_POST['wppob_grid_columns']));
                echo '<div class="notice notice-success is-dismissible"><p>Pengaturan grid disimpan.</p></div>';
            }
        }
        include_once WPPPOB_PLUGIN_DIR . 'templates/admin/manage-display-categories.php';
    }
}