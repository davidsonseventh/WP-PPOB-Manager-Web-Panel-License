<?php
/**
 * File Perbaikan untuk Halaman Kelola Kategori Tampilan WP PPOB Manager
 */

add_action('admin_init', 'wppob_process_category_form_fix');

function wppob_process_category_form_fix() {
    // Hanya jalankan di halaman admin yang benar
    if (!isset($_GET['page']) || $_GET['page'] !== 'wppob-display-categories') {
        return;
    }

    // Hanya jalankan jika form kategori dikirim
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wppob_save_category_nonce')) {
        // Cek juga form grid, karena ada di halaman yang sama
        if (!isset($_POST['_grid_nonce']) || !wp_verify_nonce($_POST['_grid_nonce'], 'wppob_save_grid_nonce')) {
            return;
        }
    }

    if (!current_user_can('manage_options')) {
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'wppob_display_categories';

    // Logika untuk menyimpan PENGATURAN GRID
    if (isset($_POST['_grid_nonce'])) {
        if (isset($_POST['wppob_grid_columns'])) {
            update_option('wppob_grid_columns', intval($_POST['wppob_grid_columns']));
            add_action('admin_notices', function() {
                echo '<div class="notice notice-success is-dismissible"><p>Pengaturan grid berhasil disimpan.</p></div>';
            });
        }
        return; // Hentikan setelah menyimpan grid
    }

    // --- Logika untuk menyimpan KATEGORI ---

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    $data = [
        'name'                  => sanitize_text_field($_POST['cat_name']),
        'image_id'              => isset($_POST['cat_image_id']) ? intval($_POST['cat_image_id']) : 0,
        'display_style'         => isset($_POST['display_style']) ? sanitize_key($_POST['display_style']) : 'image_text',
        'product_display_style' => isset($_POST['product_display_style']) ? sanitize_key($_POST['product_display_style']) : 'image_text',
        'product_display_mode'  => isset($_POST['product_display_mode']) ? sanitize_key($_POST['product_display_mode']) : 'grid',
    ];

    // Cek jika kategori memiliki anak, maka tidak bisa diisi produk
    $has_children = false;
    if ($category_id > 0) {
        $child_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM {$table_name} WHERE parent_id = %d", $category_id));
        if ($child_count > 0) {
            $has_children = true;
        }
    }

    if ($has_children) {
        $data['assigned_products'] = '[]';
    } else {
        $assigned_products = isset($_POST['assigned_products']) && is_array($_POST['assigned_products']) ? array_map('intval', $_POST['assigned_products']) : [];
        $data['assigned_products'] = json_encode($assigned_products);
    }

    $format = ['%s', '%d', '%s', '%s', '%s', '%s'];

    if ($category_id > 0 && !empty($_POST['cat_name'])) {
        $where = ['id' => $category_id];
        $where_format = ['%d'];
        $wpdb->update($table_name, $data, $where, $format, $where_format);
    } elseif (!empty($_POST['cat_name'])) {
        $wpdb->insert($table_name, $data, $format);
    }

    // Redirect untuk membersihkan URL dan mencegah submit ulang
    wp_safe_redirect(admin_url('admin.php?page=wppob-display-categories&update=success'));
    exit;
}

// Tambahkan notifikasi setelah redirect
add_action('admin_notices', 'wppob_category_admin_notices');
function wppob_category_admin_notices() {
    if (isset($_GET['page']) && $_GET['page'] === 'wppob-display-categories' && isset($_GET['update']) && $_GET['update'] === 'success') {
        echo '<div class="notice notice-success is-dismissible"><p><strong>Kategori berhasil disimpan.</strong></p></div>';
    }
}