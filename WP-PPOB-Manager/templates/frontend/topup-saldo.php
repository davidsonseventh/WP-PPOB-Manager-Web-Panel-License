<?php
defined('ABSPATH') || exit;

// Ambil produk 'saldo-topup' yang telah kita buat
$topup_product_id = wc_get_product_id_by_sku('saldo-topup');

if (!$topup_product_id) {
    echo '<p>' . __('Produk top-up saldo tidak tersedia saat ini.', 'wp-ppob') . '</p>';
    return;
}

$product = wc_get_product($topup_product_id);
?>

<div class="wppob-form-container wppob-topup-container">
    <h3><?php _e('Top-Up Saldo', 'wp-ppob'); ?></h3>
    <p><?php _e('Masukkan jumlah saldo yang ingin Anda tambahkan ke akun Anda. Pembayaran akan diproses melalui halaman checkout.', 'wp-ppob'); ?></p>

    <form class="cart" action="<?php echo esc_url($product->add_to_cart_url()); ?>" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="wppob-topup-amount"><?php _e('Jumlah Top-Up (Rp)', 'wp-ppob'); ?></label>
            <input type="number" id="wppob-topup-amount" name="quantity" min="10000" step="1000" class="input-text" placeholder="<?php _e('Contoh: 50000', 'wp-ppob'); ?>" required>
             <p class="description"><?php _e('Minimal top-up adalah Rp 10.000.', 'wp-ppob'); ?></p>
        </div>
        
        <button type="submit" class="wppob-btn"><?php _e('Lanjutkan ke Pembayaran', 'wp-ppob'); ?></button>
    </form>
</div>

<?php
/**
 * Catatan:
 * Agar ini berfungsi, Anda perlu membuat satu produk "Simple" & "Virtual" di WooCommerce
 * dengan SKU `saldo-topup` dan harga 1.
 * Kita akan menggunakan filter untuk mengubah harganya secara dinamis berdasarkan input pengguna.
 */
?>