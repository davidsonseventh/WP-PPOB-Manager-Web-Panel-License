<?php
defined('ABSPATH') || exit;

/**
 * Memformat angka menjadi format mata uang Rupiah (Rp).
 */
if (!function_exists('wppob_format_rp')) {
    function wppob_format_rp($number) {
        return 'Rp ' . number_format(is_numeric($number) ? $number : 0, 0, ',', '.');
    }
}

/**
 * Mengembalikan label yang mudah dibaca untuk status transaksi.
 */
if (!function_exists('wppob_get_status_label')) {
    function wppob_get_status_label($status) {
        $labels = [
            'pending'    => __('Pending', 'wp-ppob'),
            'processing' => __('Diproses', 'wp-ppob'),
            'success'    => __('Sukses', 'wp-ppob'),
            'failed'     => __('Gagal', 'wp-ppob'),
            'refunded'   => __('Dana Dikembalikan', 'wp-ppob'),
        ];
        return $labels[strtolower($status)] ?? ucfirst($status);
    }
}

/**
 * Mencari ID lampiran (attachment) di Media Library berdasarkan nama file.
 */
if (!function_exists('wppob_get_attachment_id_by_filename')) {
    function wppob_get_attachment_id_by_filename($filename) {
        global $wpdb;
        $attachment_id = $wpdb->get_var($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_wp_attached_file' AND meta_value LIKE %s",
            '%/' . $filename
        ));
        return $attachment_id ? (int) $attachment_id : null;
    }
}

/**
 * Mengatur featured image untuk sebuah produk dari file lokal dengan logika pencocokan fleksibel.
 * FUNGSI INI TELAH DIPERBARUI TOTAL SESUAI PERMINTAAN ANDA.
 *
 * @param int    $product_id ID produk WooCommerce.
 * @param string $brand      Nama merek dari API (e.g., 'TELKOMSEL', 'GO-PAY - DRIVER').
 */
if (!function_exists('wppob_set_product_image_from_brand')) {
    function wppob_set_product_image_from_brand($product_id, $brand) {
        // 1. Lewati jika produk sudah punya gambar thumbnail.
        if (has_post_thumbnail($product_id)) {
            return;
        }

        // 2. Normalisasi nama brand dari API menjadi kunci pencarian.
        // Contoh: 'GO-PAY - DRIVER' menjadi 'gopaydriver'
        $brand_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $brand));
        if (empty($brand_key)) {
            return;
        }

        // 3. Pindai semua file gambar di dalam direktori /assets/images/
        $image_dir = WPPPOB_PLUGIN_DIR . 'assets/images/';
        $image_files = glob($image_dir . '*.*'); // Mengambil semua file dengan ekstensi apapun

        $found_image_path = null;

        // 4. Loop setiap file gambar yang ditemukan dan cari kecocokan.
        foreach ($image_files as $image_path) {
            // Ambil nama file tanpa ekstensi. Contoh: '/path/to/go-pay_driver.jpg' menjadi 'go-pay_driver'
            $filename_no_ext = pathinfo($image_path, PATHINFO_FILENAME);
            
            // Normalisasi nama file gambar dengan cara yang sama seperti nama brand.
            // Contoh: 'go-pay_driver' menjadi 'gopaydriver'
            $file_key = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $filename_no_ext));

            // 5. Bandingkan. Jika cocok, gunakan gambar ini dan hentikan pencarian.
            if ($brand_key === $file_key) {
                $found_image_path = $image_path;
                break;
            }
        }

        // 6. Jika tidak ada gambar yang cocok ditemukan, hentikan proses.
        if (!$found_image_path) {
            return;
        }

        // 7. Proses upload dan penempatan gambar ke produk.
        $filename = basename($found_image_path);
        $attachment_id = wppob_get_attachment_id_by_filename($filename);

        // Jika gambar belum ada di Media Library, unggah sekarang.
        if (!$attachment_id) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');

            $file_array = ['name' => $filename, 'tmp_name' => $found_image_path];
            // @codingStandardsIgnoreStart
            $attachment_id = media_handle_sideload($file_array, $product_id, $brand . ' Logo');
            // @codingStandardsIgnoreEnd

            if (is_wp_error($attachment_id)) {
                return; // Gagal mengunggah.
            }
        }
        
        // Pasang gambar yang sudah ada atau yang baru diunggah ke produk.
        if ($attachment_id) {
            set_post_thumbnail($product_id, $attachment_id);
        }
    }
}