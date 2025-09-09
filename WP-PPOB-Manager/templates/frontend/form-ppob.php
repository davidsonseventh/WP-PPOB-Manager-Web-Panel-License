<?php defined('ABSPATH') || exit; ?>

<div id="wppob-app" class="wppob-form-container">
    <h3><?php _e('Transaksi PPOB', 'wp-ppob'); ?></h3>

    <div class="wppob-message-area"></div>

    <form class="wppob-form" method="post">
        
        <div class="form-group">
            <label for="wppob-category"><?php _e('Pilih Layanan', 'wp-ppob'); ?></label>
            <select id="wppob-category" name="wppob_category" required>
                <option value=""><?php _e('-- Pilih Layanan --', 'wp-ppob'); ?></option>
                <option value="PULSA"><?php _e('Pulsa Regular', 'wp-ppob'); ?></option>
                <option value="DATA"><?php _e('Paket Data', 'wp-ppob'); ?></option>
                <option value="PLN"><?php _e('Token Listrik (PLN Prabayar)', 'wp-ppob'); ?></option>
                <option value="GAME"><?php _e('Voucher Game', 'wp-ppob'); ?></option>
                </select>
        </div>

        <div class="form-group">
            <label for="wppob-customer-no"><?php _e('Nomor Tujuan / ID Pelanggan', 'wp-ppob'); ?></label>
            <input type="tel" id="wppob-customer-no" name="wppob_customer_no" placeholder="<?php _e('Contoh: 081234567890', 'wp-ppob'); ?>" required>
        </div>

        <div class="form-group">
            <label for="wppob-product-sku"><?php _e('Pilih Produk / Nominal', 'wp-ppob'); ?></label>
            <select id="wppob-product-sku" name="product_sku" required disabled>
                <option value=""><?php _e('-- Pilih Layanan terlebih dahulu --', 'wp-ppob'); ?></option>
            </select>
            <div class="wppob-loader" style="display: none;"></div>
        </div>

        <div class="form-group">
            <label><?php _e('Harga', 'wp-ppob'); ?></label>
            <input type="text" id="wppob-price-display" readonly value="Rp 0">
        </div>

        <?php if (!is_user_logged_in()): ?>
            <p><?php _e('Anda harus login untuk melanjutkan transaksi.', 'wp-ppob'); ?></p>
        <?php else: ?>
            <button type="submit" class="wppob-btn" disabled><?php _e('Beli Sekarang', 'wp-ppob'); ?></button>
        <?php endif; ?>
    </form>
</div>