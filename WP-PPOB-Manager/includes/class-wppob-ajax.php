<?php
defined('ABSPATH') || exit;

/**
 * Class WPPPOB_Ajax
 *
 * Menangani semua permintaan AJAX (Asynchronous JavaScript and XML)
 * dari sisi admin (backend) dan pengguna (frontend).
 */
class WPPPOB_Ajax {
    
    public function update_product_order_in_category() {
    check_ajax_referer('wppob-admin-nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Anda tidak memiliki izin.']);
    }

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $product_ids = isset($_POST['product_ids']) && is_array($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : [];

    if (empty($category_id)) {
        wp_send_json_error(['message' => 'ID Kategori tidak valid.']);
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wppob_display_categories';
    
    // Simpan urutan baru sebagai JSON
    $encoded_product_ids = json_encode($product_ids);
    
    $result = $wpdb->update(
        $table_name,
        ['assigned_products' => $encoded_product_ids],
        ['id' => $category_id],
        ['%s'], // format for data
        ['%d']  // format for where
    );

    // Periksa apakah update database berhasil atau tidak
    if (false === $result) {
        wp_send_json_error(['message' => 'Gagal memperbarui database. Periksa error log.']);
    } else {
        wp_send_json_success(['message' => 'Urutan produk berhasil disimpan.']);
    }
}
    
    public function update_product_order() {
    check_ajax_referer('wppob-admin-nonce', 'nonce');

    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Anda tidak memiliki izin.']);
    }

    $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : [];

    if (empty($product_ids)) {
        wp_send_json_error(['message' => 'Tidak ada produk yang dikirim.']);
    }

    foreach ($product_ids as $order => $product_id) {
        // WordPress menggunakan 'menu_order' untuk mengatur urutan
        wp_update_post([
            'ID' => $product_id,
            'menu_order' => $order
        ]);
    }

    wp_send_json_success(['message' => 'Urutan produk berhasil disimpan.']);
}
    
    

    /**
     * Constructor untuk mendaftarkan semua hook AJAX.
     */
    public function __construct() {
        // --- HOOKS UNTUK ADMIN (BACKEND) ---
        // Hanya bisa diakses oleh pengguna yang login
        add_action('wp_ajax_wppob_sync_products', [$this, 'ajax_sync_products']);
        add_action('wp_ajax_wppob_adjust_balance', [$this, 'ajax_adjust_balance']);
        add_action('wp_ajax_wppob_user_search', [$this, 'ajax_user_search']);

        // --- HOOKS UNTUK PENGGUNA (FRONTEND) ---
        // Bisa diakses oleh semua orang (login dan tidak login)
        add_action('wp_ajax_wppob_get_products_by_category_slug', [$this, 'ajax_get_products_by_category_slug']);
        add_action('wp_ajax_nopriv_wppob_get_products_by_category_slug', [$this, 'ajax_get_products_by_category_slug']);
        
        add_action('wp_ajax_wppob_get_category_content', [$this, 'ajax_get_category_content']);
        add_action('wp_ajax_nopriv_wppob_get_category_content', [$this, 'ajax_get_category_content']);
        add_action('wp_ajax_wppob_bulk_update_images', [$this, 'ajax_bulk_update_images']);

        add_action('wp_ajax_wppob_update_product_order', $plugin_ajax, 'update_product_order');
        
        add_action('wp_ajax_wppob_update_product_order_in_category', $plugin_ajax, 'update_product_order_in_category');

    }

    // =================================================================
    // == METODE-METODE UNTUK ADMIN
    // =================================================================

    /**
     * Menangani permintaan sinkronisasi produk dari API.
     */
    public function ajax_sync_products() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Anda tidak memiliki izin untuk melakukan sinkronisasi.'], 403);
        }

        $products_manager = new WPPPOB_Products();
        $result = $products_manager->sync_from_api();

        if (is_wp_error($result)) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        }
        
        wp_send_json_success([
            'message' => __('Produk berhasil disinkronkan dari server API.', 'wp-ppob')
        ]);
    }



public function ajax_bulk_update_images() {
    check_ajax_referer('wppob_admin_nonce', 'nonce');
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Anda tidak memiliki izin.']);
    }

    $product_ids = isset($_POST['product_ids']) ? array_map('intval', $_POST['product_ids']) : [];
    $image_id = isset($_POST['image_id']) ? intval($_POST['image_id']) : 0;

    if (empty($product_ids) || $image_id === 0) {
        wp_send_json_error(['message' => 'Produk atau gambar tidak dipilih.']);
    }

    foreach ($product_ids as $product_id) {
        set_post_thumbnail($product_id, $image_id);
    }

    wp_send_json_success(['message' => count($product_ids) . ' produk berhasil diperbarui.']);
}



    /**
     * Menangani penyesuaian saldo pengguna.
     */
    public function ajax_adjust_balance() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('edit_users')) {
            wp_send_json_error(['message' => 'Anda tidak memiliki izin untuk mengubah saldo pengguna.'], 403);
        }
        
        $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
        $amount  = isset($_POST['amount']) ? floatval($_POST['amount']) : 0;
        $note    = isset($_POST['note']) ? sanitize_text_field($_POST['note']) : 'Penyesuaian oleh admin';

        if (!$user_id || $amount == 0) {
            wp_send_json_error(['message' => 'User ID dan jumlah tidak boleh kosong.']);
        }

        if ($amount > 0) {
            WPPPOB_Balances::add($user_id, $amount, $note);
        } else {
            WPPPOB_Balances::deduct($user_id, abs($amount), $note);
        }
        
        wp_send_json_success([
            'message'     => __('Saldo pengguna berhasil diperbarui.', 'wp-ppob'),
            'new_balance' => wppob_format_rp(WPPPOB_Balances::get($user_id))
        ]);
    }

    /**
     * Menangani pencarian pengguna di halaman admin.
     */
    public function ajax_user_search() {
        check_ajax_referer('wppob_admin_nonce', 'nonce');
        if (!current_user_can('list_users')) {
            wp_send_json_error(['message' => 'Akses ditolak.'], 403);
        }
        
        $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
        if (strlen($search) < 3) {
            wp_send_json_error(['message' => 'Masukkan minimal 3 karakter untuk mencari.']);
        }
        
        $users = get_users(['search' => "*{$search}*", 'search_columns' => ['user_login', 'user_email']]);
        $results = [];
        foreach ($users as $user) {
            $results[] = [
                'id'    => $user->ID,
                'text'  => $user->user_login . ' (' . $user->user_email . ')',
                'balance' => wppob_format_rp(WPPPOB_Balances::get($user->ID))
            ];
        }
        wp_send_json_success($results);
    }


    // =================================================================
    // == METODE-METODE UNTUK FRONTEND
    // =================================================================

    /**
     * Mengambil daftar produk berdasarkan slug kategori untuk tampilan aplikasi.
     */
    public function ajax_get_products_by_category_slug() {
        check_ajax_referer('wppob_frontend_nonce', 'nonce');
        $slug = isset($_POST['category_slug']) ? sanitize_key($_POST['category_slug']) : '';

        if (empty($slug)) {
            wp_send_json_error(['message' => 'Slug kategori tidak boleh kosong.']);
        }

        $products = wc_get_products([
            'limit'    => -1,
            'status'   => 'publish',
            'category' => [$slug],
            'orderby'  => 'menu_order',
            'order'    => 'ASC',
        ]);

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id'         => $product->get_id(),
                'name'       => $product->get_name(),
                'price_html' => $product->get_price_html(),
                'permalink'  => $product->get_permalink(),
            ];
        }

        wp_send_json_success($data);
    }
    
    
    
    /**
     * [Frontend] Mengambil konten dari sebuah kategori tampilan (sub-kategori atau produk).
     */
    public function ajax_get_category_content() {
        check_ajax_referer('wppob_frontend_nonce', 'nonce');
        $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

        if (!$category_id) {
            wp_send_json_error();
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'wppob_display_categories';
        
        // Cari sub-kategori dari ID yang diklik
        $sub_categories = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_name} WHERE parent_id = %d ORDER BY sort_order ASC, name ASC", $category_id));
        
        $data = [
            'sub_categories' => [],
            'products' => []
        ];

        // Format data sub-kategori
        foreach ($sub_categories as $cat) {
            $data['sub_categories'][] = [
                'id' => $cat->id,
                'name' => $cat->name,
                'image_url' => $cat->image_id ? wp_get_attachment_image_url($cat->image_id, 'thumbnail') : wc_placeholder_img_src(),
                'display_style' => $cat->display_style,
                'image_size_px' => $cat->image_size_px,
                'border_radius' => $cat->border_radius,
            ];
        }

        // Cari produk yang ditugaskan ke kategori ini
        $parent_category = $wpdb->get_row($wpdb->prepare("SELECT assigned_products FROM {$table_name} WHERE id = %d", $category_id));
        if ($parent_category && !empty($parent_category->assigned_products)) {
            $product_ids = json_decode($parent_category->assigned_products);
            if (!empty($product_ids)) {
                $products = wc_get_products(['include' => $product_ids]);
                foreach ($products as $product) {
                    $data['products'][] = [
                        'id' => $product->get_id(),
                        'name' => $product->get_name(),
                        'price_html' => $product->get_price_html(),
                        'image_url' => has_post_thumbnail($product->get_id()) ? get_the_post_thumbnail_url($product->get_id(), 'thumbnail') : wc_placeholder_img_src(),
                        'permalink' => $product->get_permalink(),
                    ];
                }
            }
        }

        wp_send_json_success($data);
    }
    
}