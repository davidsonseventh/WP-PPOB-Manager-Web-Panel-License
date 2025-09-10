<div class="wrap wppob-wrap">
    <h1><?php _e('Dashboard PPOB', 'wp-ppob'); ?></h1>
    <p><?php _e('Ringkasan aktivitas dan keuntungan bisnis PPOB Anda.', 'wp-ppob'); ?></p>

    <?php
    global $wpdb;
    $table = $wpdb->prefix . 'wppob_transactions';
    
    // Keuntungan
    $today_profit = $wpdb->get_var("SELECT SUM(profit) FROM {$table} WHERE status = 'success' AND DATE(created_at) = CURDATE()");
    $week_profit = $wpdb->get_var("SELECT SUM(profit) FROM {$table} WHERE status = 'success' AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $month_profit = $wpdb->get_var("SELECT SUM(profit) FROM {$table} WHERE status = 'success' AND MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
    
    // Transaksi
    $total_success = $wpdb->get_var("SELECT COUNT(id) FROM {$table} WHERE status = 'success'");
    $total_pending = $wpdb->get_var("SELECT COUNT(id) FROM {$table} WHERE status = 'processing'");
    
    // Pengguna
    $active_users = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$table}");
    
    // Saldo API
    $api = new WPPPOB_API();
    $balance_data = $api->check_balance();
    $api_balance = isset($balance_data['data']['deposit']) ? $balance_data['data']['deposit'] : 'Tidak dapat mengambil data';
    ?>

    <div id="wppob-dashboard-widgets-wrap">
        <div class="metabox-holder">
            <div class="postbox-container">
                
                <div class="wppob-col">
                    <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Keuntungan', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                            <ul>
                                <li><strong><?php _e('Hari Ini:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($today_profit ?: 0); ?></li>
                                <li><strong><?php _e('7 Hari Terakhir:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($week_profit ?: 0); ?></li>
                                <li><strong><?php _e('Bulan Ini:', 'wp-ppob'); ?></strong> <?php echo wppob_format_rp($month_profit ?: 0); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="wppob-col">
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Statistik Transaksi', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                            <ul>
                                <li><strong><?php _e('Transaksi Sukses:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($total_success); ?></li>
                                <li><strong><?php _e('Transaksi Pending:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($total_pending); ?></li>
                                <li><strong><?php _e('Pengguna Aktif:', 'wp-ppob'); ?></strong> <?php echo number_format_i18n($active_users); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>

                 <div class="wppob-col">
                     <div class="postbox">
                        <h2 class="hndle"><span><?php _e('Info Akun', 'wp-ppob'); ?></span></h2>
                        <div class="inside">
                           <ul>
                                <li><strong><?php _e('Saldo Server PPOB:', 'wp-ppob'); ?></strong> <?php echo is_numeric($api_balance) ? wppob_format_rp($api_balance) : $api_balance; ?></li>
                                <li><strong><?php _e('Sinkronisasi Terakhir:', 'wp-ppob'); ?></strong> <?php echo get_option('wppob_last_sync', 'Belum pernah'); ?></li>
                            </ul>
                            <button id="wppob-sync-products" class="button button-secondary"><?php _e('Sinkronkan Produk Sekarang', 'wp-ppob'); ?></button>
                            <span class="spinner"></span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>