<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wppob-form-container">
    <h3><?php _e( 'Isi Pulsa & Paket Data', 'wp-ppob' ); ?></h3>

    <form id="wppob-pulsa-form" class="wppob-form">
        <div class="form-group">
            <label><?php _e( 'Nomor HP', 'wp-ppob' ); ?></label>
            <input type="tel" name="customer_no" id="wppob-phone" placeholder="08xxxxxxxxxx" required>
        </div>

        <div class="form-group">
            <label><?php _e( 'Operator', 'wp-ppob' ); ?></label>
            <select name="operator" id="wppob-operator" required>
                <option value=""><?php _e( '-- Pilih Operator --', 'wp-ppob' ); ?></option>
                <option value="telkomsel">Telkomsel</option>
                <option value="xl">XL / Axis</option>
                <option value="indosat">Indosat / IM3</option>
                <option value="tri">Tri</option>
                <option value="smartfren">Smartfren</option>
            </select>
        </div>

        <div class="form-group">
            <label><?php _e( 'Produk', 'wp-ppob' ); ?></label>
            <select name="sku" id="wppob-nominal" required>
                <option value=""><?php _e( '-- Pilih Produk --', 'wp-ppob' ); ?></option>
            </select>
        </div>

        <div class="form-group">
            <label><?php _e( 'Harga Jual', 'wp-ppob' ); ?></label>
            <input type="text" id="wppob-price" readonly>
        </div>

        <button type="submit" class="wppob-btn">
            <?php _e( 'Beli Sekarang', 'wp-ppob' ); ?>
        </button>

        <div class="wppob-message-area"></div>
    </form>
</div>