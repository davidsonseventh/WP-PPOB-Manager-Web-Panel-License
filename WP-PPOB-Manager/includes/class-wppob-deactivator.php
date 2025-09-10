<?php
defined('ABSPATH') || exit;

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 */
class WPPPOB_Deactivator {

    /**
     * Membersihkan jadwal cron saat plugin dinonaktifkan.
     *
     * Ini adalah praktik terbaik untuk tidak menghapus data pada saat deaktivasi,
     * tetapi hanya membersihkan tugas terjadwal atau cache sementara.
     * Penghapusan data besar sebaiknya dilakukan pada saat uninstal.
     */
    public static function deactivate() {
        // Hapus cron job untuk sinkronisasi produk
        wp_clear_scheduled_hook('wppob_hourly_sync');
    }
}