<?php
defined('ABSPATH') || exit;

class WPPPOB_Users {

    public function __construct() {
        // Menambahkan field saldo di halaman edit profil pengguna
        add_action('show_user_profile', [$this, 'add_user_balance_field']);
        add_action('edit_user_profile', [$this, 'add_user_balance_field']);

        // Menyimpan field saldo
        add_action('personal_options_update', [$this, 'save_user_balance_field']);
        add_action('edit_user_profile_update', [$this, 'save_user_balance_field']);
        
        // Menambahkan kolom saldo di tabel daftar pengguna
        add_filter('manage_users_columns', [$this, 'add_balance_column']);
        add_filter('manage_users_custom_column', [$this, 'render_balance_column_content'], 10, 3);
    }

    /**
     * Menampilkan field input untuk saldo di halaman profil pengguna.
     * @param WP_User $user
     */
    public function add_user_balance_field($user) {
        if (!current_user_can('edit_users')) {
            return;
        }
        $balance = WPPPOB_Balances::get($user->ID);
        ?>
        <h3><?php _e('Manajemen PPOB', 'wp-ppob'); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="wppob_balance"><?php _e('Saldo PPOB', 'wp-ppob'); ?></label></th>
                <td>
                    <input type="number" step="any" name="wppob_balance" id="wppob_balance" value="<?php echo esc_attr($balance); ?>" class="regular-text" />
                    <p class="description"><?php _e('Ubah nilai ini untuk menimpa saldo pengguna saat ini. Untuk menambah/mengurangi, gunakan halaman Manajemen Pengguna.', 'wp-ppob'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Menyimpan nilai saldo dari halaman profil pengguna.
     * @param int $user_id
     */
    public function save_user_balance_field($user_id) {
        if (!current_user_can('edit_users', $user_id)) {
            return false;
        }
        if (isset($_POST['wppob_balance'])) {
            global $wpdb;
            $new_balance = floatval($_POST['wppob_balance']);
            
            // Menggunakan ON DUPLICATE KEY UPDATE untuk menimpa saldo
             $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->prefix}wppob_balances (user_id, balance) VALUES (%d, %f)
                     ON DUPLICATE KEY UPDATE balance = %f",
                    $user_id, $new_balance, $new_balance
                )
            );
        }
    }
    
    /**
     * Menambahkan kolom 'Saldo' pada tabel daftar pengguna.
     */
    public function add_balance_column($columns) {
        $columns['wppob_balance'] = __('Saldo PPOB', 'wp-ppob');
        return $columns;
    }
    
    /**
     * Menampilkan isi dari kolom 'Saldo'.
     */
    public function render_balance_column_content($value, $column_name, $user_id) {
        if ('wppob_balance' === $column_name) {
            return wppob_format_rp(WPPPOB_Balances::get($user_id));
        }
        return $value;
    }

    /**
     * Fungsi untuk memblokir pengguna (menonaktifkan kemampuan login).
     * @param int $user_id
     */
    public static function block_user($user_id) {
        // Implementasi blokir bisa dengan menambahkan meta-key
        update_user_meta($user_id, '_wppob_is_blocked', true);
    }

    /**
     * Fungsi untuk menghapus pengguna beserta data transaksinya.
     * @param int $user_id
     */
    public static function delete_user($user_id) {
        // Implementasi hapus pengguna dan datanya
        // Pastikan untuk menghapus dari tabel wppob_balances dan wppob_transactions
        // wp_delete_user($user_id);
    }
}