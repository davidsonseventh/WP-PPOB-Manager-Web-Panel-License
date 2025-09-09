<?php
defined('ABSPATH') || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'wppob_display_categories';
$categories = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY name ASC");
?>
<div class="wrap">
    <h1><?php _e('Kelola Kategori Tampilan', 'wp-ppob'); ?></h1>
    
    <?php if (isset($_GET['message']) && $_GET['message'] === 'success') : ?>
        <div id="message" class="updated notice is-dismissible">
            <p><?php _e('Kategori berhasil disimpan.', 'wp-ppob'); ?></p>
        </div>
    <?php endif; ?>

    <p><?php _e('Buat dan atur kategori/sub-kategori yang akan ditampilkan di halaman depan.', 'wp-ppob'); ?></p>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <h2><?php _e('Tambah Kategori Baru', 'wp-ppob'); ?></h2>
                <form id="wppob-add-category-form" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="wppob_save_category">
                    <?php wp_nonce_field('wppob_save_category_nonce', '_wpnonce'); ?>

                    <div class="form-field form-required">
                        <label for="cat_name"><?php _e('Nama', 'wp-ppob'); ?></label>
                        <input name="cat_name" id="cat_name" type="text" value="" size="40" required>
                    </div>

                    <div class="form-field">
                        <label for="cat_parent"><?php _e('Induk Kategori', 'wp-ppob'); ?></label>
                        <select name="cat_parent" id="cat_parent" class="postform">
                            <option value="0"><?php _e('— Kategori Utama —', 'wp-ppob'); ?></option>
                            <?php foreach ($categories as $cat) { if ($cat->parent_id == 0) { echo '<option value="' . esc_attr($cat->id) . '">' . esc_html($cat->name) . '</option>'; } } ?>
                        </select>
                    </div>

                    <div class="form-field">
                        <label><?php _e('Gambar Kategori', 'wp-ppob'); ?></label>
                        <div class="wppob-image-uploader">
                            <img src="" class="wppob-image-preview" style="display:none; width:100px; height:100px; object-fit:cover; margin-bottom:10px; border:1px solid #ddd;">
                            <input type="hidden" name="cat_image_id" class="wppob-image-id" value="">
                            <button type="button" class="button wppob-upload-btn"><?php _e('Pilih Gambar', 'wp-ppob'); ?></button>
                            <button type="button" class="button wppob-remove-btn" style="display:none;"><?php _e('Hapus Gambar', 'wp-ppob'); ?></button>
                        </div>
                    </div>

                    <?php submit_button(__('Tambah Kategori', 'wp-ppob')); ?>
                </form>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <h2><?php _e('Daftar Kategori', 'wp-ppob'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead><tr><th><?php _e('Gambar', 'wp-ppob'); ?></th><th><?php _e('Nama', 'wp-ppob'); ?></th></tr></thead>
                    <tbody>
                        <?php if (!empty($categories)) { 
                            foreach ($categories as $cat) {
                                $image = $cat->image_id ? wp_get_attachment_image($cat->image_id, 'thumbnail', false, ['style' => 'width:50px;height:50px;object-fit:cover;']) : '';
                                echo '<tr><td>' . $image . '</td><td>' . ($cat->parent_id ? '— ' : '') . esc_html($cat->name) . '</td></tr>';
                            } 
                        } else { 
                            echo '<tr><td colspan="2">Belum ada kategori.</td></tr>'; 
                        } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>