<?php
if ( ! is_user_logged_in() ) {
    echo '<p>' . __( 'Silakan login untuk melakukan top-up.', 'wp-ppob' ) . '</p>';
    return;
}
?>
<div class="wppob-form-container">
    <h3><?php _e( 'Top-Up Saldo', 'wp-ppob' ); ?></h3>

    <p><?php _e( 'Pilih nominal saldo yang ingin Anda beli. Pembayaran akan diproses melalui WooCommerce.', 'wp-ppob' ); ?></p>

    <div class="wppob-topup-options">
        <?php
        $nominals = [ 10000, 20000, 50000, 100000, 200000, 500000 ];
        foreach ( $nominals as $nom ) :
            $sku = 'topup-' . $nom;
            $product_id = wc_get_product_id_by_sku( $sku );
            if ( ! $product_id ) continue;
            $product = wc_get_product( $product_id );
            ?>
            <div class="wppob-topup-item">
                <h4><?php echo wppob_format_rp( $nom ); ?></h4>
                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>" class="wppob-btn">
                    <?php _e( 'Beli', 'wp-ppob' ); ?>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>