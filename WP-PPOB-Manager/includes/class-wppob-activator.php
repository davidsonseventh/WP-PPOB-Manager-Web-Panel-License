<?php
defined('ABSPATH') || exit;

class WPPPOB_Activator {

    public static function activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // Tabel untuk Saldo Pengguna
        $sql_balances = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wppob_balances (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            balance decimal(15,2) DEFAULT 0.00 NOT NULL,
            updated_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_balances);

        // Tabel untuk Transaksi
        $sql_transactions = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wppob_transactions (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            user_id bigint(20) NOT NULL,
            order_id bigint(20) NOT NULL,
            product_code varchar(100) NOT NULL,
            customer_no varchar(50) NOT NULL,
            base_price decimal(15,2) NOT NULL,
            sale_price decimal(15,2) NOT NULL,
            profit decimal(15,2) DEFAULT 0.00 NOT NULL,
            status enum('pending','processing','success','failed','refunded') DEFAULT 'pending' NOT NULL,
            remote_trx_id varchar(100) DEFAULT '' NOT NULL,
            response_message text,
            created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id),
            KEY user_id (user_id)
        ) $charset_collate;";
        dbDelta($sql_transactions);

        // Tabel untuk Kategori Tampilan Kustom (Struktur Lengkap dan Benar)
       $sql_display_cats = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wppob_display_categories (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        parent_id bigint(20) DEFAULT 0 NOT NULL,
        name varchar(200) NOT NULL,
        image_id bigint(20) DEFAULT 0 NOT NULL,
        display_style enum('image_only','image_text','text_only') DEFAULT 'image_text' NOT NULL,
        display_mode enum('grid','list') DEFAULT 'grid' NOT NULL,
        image_size_px int(4) DEFAULT 80 NOT NULL,
        border_radius int(3) DEFAULT 15 NOT NULL,
        assigned_products longtext,
        sort_order int(11) DEFAULT 0 NOT NULL,
        -- KOLOM BARU DITAMBAHKAN DI BAWAH INI --
        product_display_style enum('image_text','image_only','text_only') NOT NULL DEFAULT 'image_text',
        product_display_mode enum('grid','list') NOT NULL DEFAULT 'grid',
        PRIMARY KEY (id),
        KEY parent_id (parent_id)
    ) $charset_collate;";
    dbDelta($sql_display_cats);

        add_option('wppob_api_provider', 'digiflazz');
        add_option('wppob_profit_type', 'fixed');
        add_option('wppob_profit_amount', 1000);
        add_option('wppob_grid_columns', 4);

        if (!wp_next_scheduled('wppob_hourly_sync')) {
            wp_schedule_event(time(), 'hourly', 'wppob_hourly_sync');
        }
    }
}