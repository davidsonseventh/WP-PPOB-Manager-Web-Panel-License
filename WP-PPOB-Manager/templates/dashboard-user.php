<?php
if ( ! is_user_logged_in() ) {
    echo '<p>' . __( 'Silakan login untuk melihat dashboard.', 'wp-ppob' ) . '</p>';
    return;
}

$user_id  = get_current_user_id();
$balance  = WPPPOB_Balances::get( $user_id );
?>
<div class="wppob-user-dashboard">
    <h2><?php _e( 'Dashboard Pelanggan', 'wp-ppob' ); ?></h2>

    <div class="wppob-balance-card">
        <h4><?php _e( 'Saldo Anda', 'wp-ppob' ); ?></h4>
        <div class="amount"><?php echo wppob_format_rp( $balance ); ?></div>
        <a href="<?php echo site_url( '/topup-saldo' ); ?>" class="wppob-btn wppob-btn-outline">
            <?php _e( 'Top-Up Saldo', 'wp-ppob' ); ?>
        </a>
    </div>

    <h3><?php _e( 'Riwayat Transaksi', 'wp-ppob' ); ?></h3>
    <?php
    global $wpdb;
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wppob_transactions
             WHERE order_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_author = %d)
             ORDER BY created_at DESC LIMIT 50",
            $user_id
        )
    );
    ?>
    <table class="wppob-table">
        <thead>
            <tr>
                <th><?php _e( 'Tanggal', 'wp-ppob' ); ?></th>
                <th><?php _e( 'Produk', 'wp-ppob' ); ?></th>
                <th><?php _e( 'Nomor', 'wp-ppob' ); ?></th>
                <th><?php _e( 'Harga', 'wp-ppob' ); ?></th>
                <th><?php _e( 'Status', 'wp-ppob' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( $rows ) : ?>
                <?php foreach ( $rows as $r ) : ?>
                    <tr>
                        <td><?php echo date_i18n( 'd/m/Y H:i', strtotime( $r->created_at ) ); ?></td>
                        <td><?php echo esc_html( $r->product_code ); ?></td>
                        <td><?php echo esc_html( $r->customer_no ); ?></td>
                        <td><?php echo wppob_format_rp( $r->price ); ?></td>
                        <td>
                            <span class="wppob-status <?php echo esc_attr( $r->status ); ?>">
                                <?php echo wppob_get_transaction_status_label( $r->status ); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr>
                    <td colspan="5"><?php _e( 'Belum ada transaksi.', 'wp-ppob' ); ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>