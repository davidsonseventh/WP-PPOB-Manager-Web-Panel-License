<?php
defined('ABSPATH') || exit;

$user_id = get_current_user_id();
$balance = WPPPOB_Balances::get($user_id);

global $wpdb;
$transactions = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}wppob_transactions WHERE user_id = %d ORDER BY created_at DESC LIMIT 20",
    $user_id
));
?>

<div class="wppob-user-dashboard">
    
    <div class="wppob-balance-card">
        <h4><?php _e('Saldo Anda Saat Ini', 'wp-ppob'); ?></h4>
        <div class="amount"><?php echo wppob_format_rp($balance); ?></div>
        <a href="/url-halaman-topup/" class="wppob-btn-outline"><?php _e('Top-Up Saldo', 'wp-ppob'); ?></a>
    </div>

    <h3><?php _e('Riwayat Transaksi Terbaru', 'wp-ppob'); ?></h3>
    <table class="wppob-table">
        <thead>
            <tr>
                <th><?php _e('Tanggal', 'wp-ppob'); ?></th>
                <th><?php _e('Produk', 'wp-ppob'); ?></th>
                <th><?php _e('Nomor Tujuan', 'wp-ppob'); ?></th>
                <th><?php _e('Total', 'wp-ppob'); ?></th>
                <th><?php _e('Status', 'wp-ppob'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($transactions)) : ?>
                <?php foreach ($transactions as $tx) : ?>
                    <tr>
                        <td><?php echo date_i18n('d M Y, H:i', strtotime($tx->created_at)); ?></td>
                        <td><?php echo esc_html($tx->product_code); ?></td>
                        <td><?php echo esc_html($tx->customer_no); ?></td>
                        <td><?php echo wppob_format_rp($tx->sale_price); ?></td>
                        <td>
                            <span class="wppob-status-badge status-<?php echo esc_attr($tx->status); ?>">
                                <?php echo wppob_get_status_label($tx->status); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php _e('Belum ada transaksi.', 'wp-ppob'); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>