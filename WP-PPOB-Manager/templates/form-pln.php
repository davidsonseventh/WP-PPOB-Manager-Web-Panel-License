<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wppob-form-container">
    <h3><?php _e( 'Token Listrik PLN', 'wp-ppob' ); ?></h3>

    <form id="wppob-pln-form" class="wppob-form">
        <div class="form-group">
            <label><?php _e( 'ID Pelanggan / Nomor Meter', 'wp-ppob' ); ?></label>
            <input type="text" name="customer_no" id="wppob-idpel" placeholder="112233445566" required>
        </div>

        <div class="form-group">
            <label><?php _e( 'Nominal Token', 'wp-ppob' ); ?></label>
            <select name="sku" id="wppob-token-nominal" required>
                <option value=""><?php _e( '-- Pilih Token --', 'wp-ppob' ); ?></option>
                <option value="PLN20K">20.000 - 20.4 kWh</option>
                <option value="PLN50K">50.000 - 51.8 kWh</option>
                <option value="PLN100K">100.000 - 104.4 kWh</option>
                <option value="PLN200K">200.000 - 208.8 kWh</option>
                <option value="PLN500K">500.000 - 522 kWh</option>
                <option value="PLN1000K">1.000.000 - 1.044 kWh</option>
            </select>
        </div>

        <div class="form-group">
            <label><?php _e( 'Harga Jual', 'wp-ppob' ); ?></label>
            <input type="text" id="wppob-token-price" readonly>
        </div>

        <button type="submit" class="wppob-btn">
            <?php _e( 'Beli Token', 'wp-ppob' ); ?>
        </button>

        <div class="wppob-message-area"></div>
    </form>
</div>