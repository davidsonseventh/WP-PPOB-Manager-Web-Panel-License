<?php
defined('ABSPATH') || exit;

global $wpdb;
$table_name = $wpdb->prefix . 'wppob_display_categories';
$all_categories = $wpdb->get_results("SELECT * FROM {$table_name} ORDER BY parent_id ASC, sort_order ASC");

$categories_tree = [];
$category_lookup = [];
if (!empty($all_categories)) {
    foreach ($all_categories as $cat) {
        $category_lookup[$cat->id] = $cat;
        $cat->children = [];
    }
    foreach ($all_categories as $cat) {
        if ($cat->parent_id != 0 && isset($category_lookup[$cat->parent_id])) {
            $category_lookup[$cat->parent_id]->children[] = $cat;
        } else {
            $categories_tree[] = $cat;
        }
    }
}

if (!function_exists('wppob_display_sortable_categories')) {
    function wppob_display_sortable_categories($categories) {
        echo '<ol class="wppob-sortable-list">';
        foreach ($categories as $cat) {
            $has_children = !empty($cat->children);
            echo '<li id="cat-' . esc_attr($cat->id) . '">';
            echo '<div>';
            echo '<span class="dashicons dashicons-move"></span> ';
            if ($has_children) {
                echo '<span class="wppob-toggle-children dashicons dashicons-minus"></span>';
            }
            echo esc_html($cat->name);
            echo '<a href="?page=wppob-display-categories&action=edit&id=' . esc_attr($cat->id) . '" class="wppob-edit-link">Edit</a>';
            echo '</div>';
            if ($has_children) {
                wppob_display_sortable_categories($cat->children);
            }
            echo '</li>';
        }
        echo '</ol>';
    }
}

$edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$item_to_edit = null;
$selected_products_obj = [];

if ($edit_mode) { 
    $item_id = intval($_GET['id']); 
    $item_to_edit = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $item_id)); 
    $assigned_product_ids = !empty($item_to_edit->assigned_products) ? json_decode($item_to_edit->assigned_products, true) : [];
    
    if (!empty($assigned_product_ids)) {
        $args = [
            'limit' => -1,
            'include' => $assigned_product_ids,
            'orderby' => 'post__in', // This preserves the saved order
        ];
        $selected_products_obj = function_exists('wc_get_products') ? wc_get_products($args) : [];
    }
}

$ppob_products = function_exists('wc_get_products') ? wc_get_products(['limit' => -1, 'meta_key' => '_wppob_base_price', 'orderby' => 'name', 'order' => 'ASC']) : [];
$assigned_product_ids_for_checkbox = !empty($item_to_edit->assigned_products) ? json_decode($item_to_edit->assigned_products) : [];
?>
<div class="wrap">
    <h1>Kelola Kategori Tampilan</h1>
    <p>Gunakan antarmuka di sebelah kanan untuk menyusun urutan dan hierarki kategori. Klik "Edit" untuk mengubah detail dan mengurutkan produk di dalamnya.</p>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <h3><?php echo $edit_mode ? 'Edit Kategori: ' . esc_html($item_to_edit->name ?? '') : 'Tambah Kategori Baru'; ?></h3>
                <form method="post" action="">
                    <input type="hidden" name="category_id" value="<?php echo esc_attr($item_to_edit->id ?? 0); ?>">
                    <?php wp_nonce_field('wppob_save_category_nonce', '_wpnonce'); ?>

                    <h4>Pengaturan Dasar</h4>
                    <div class="form-field"><label for="cat_name">Nama Kategori</label><input name="cat_name" id="cat_name" type="text" value="<?php echo esc_attr($item_to_edit->name ?? ''); ?>" required></div>
                    
                    <h4>Pengaturan Tampilan Kategori</h4>
                    <div class="form-field">
                        <label>Gambar</label>
                        <div class="wppob-image-uploader">
                            <?php $image_url = ($item_to_edit->image_id ?? 0) ? wp_get_attachment_image_url($item_to_edit->image_id, 'thumbnail') : ''; ?>
                            <img src="<?php echo esc_url($image_url); ?>" class="wppob-image-preview" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?> max-width: 80px; height: auto;">
                            <input type="hidden" name="cat_image_id" class="wppob-image-id" value="<?php echo esc_attr($item_to_edit->image_id ?? 0); ?>">
                            <button type="button" class="button wppob-upload-btn" style="<?php echo !empty($image_url) ? 'display:none;' : ''; ?>">Pilih Gambar</button>
                            <button type="button" class="button wppob-remove-btn" style="<?php echo empty($image_url) ? 'display:none;' : ''; ?>">Hapus Gambar</button>
                        </div>
                    </div>
                    <div class="form-field">
                        <label for="display_style">Gaya Tampilan Kategori</label>
                        <select name="display_style" id="display_style">
                            <option value="image_text" <?php selected($item_to_edit->display_style ?? 'image_text', 'image_text'); ?>>Gambar & Teks</option>
                            <option value="image_only" <?php selected($item_to_edit->display_style ?? '', 'image_only'); ?>>Gambar Saja</option>
                            <option value="text_only" <?php selected($item_to_edit->display_style ?? '', 'text_only'); ?>>Teks Saja</option>
                        </select>
                    </div>

                    <h4>Konten Kategori</h4>
                    <div class="form-field">
                        <label>Pilih Produk (Centang untuk menambahkan ke kategori)</label>
                        <div class="wppob-product-checkbox-container">
                            <?php if (!empty($ppob_products)): foreach($ppob_products as $product): $product_id = $product->get_id(); $is_checked = in_array($product_id, $assigned_product_ids_for_checkbox); ?>
                                <div><label><input type="checkbox" name="assigned_products[]" value="<?php echo esc_attr($product_id); ?>" <?php checked($is_checked, true); ?>> <?php echo esc_html($product->get_name()); ?></label></div>
                            <?php endforeach; else: ?>
                                <p>Tidak ada produk PPOB. Silakan sinkronisasi.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <h4>Pengaturan Tampilan Produk</h4>
                    <p class="description">Atur bagaimana produk di dalam kategori ini akan ditampilkan di halaman depan.</p>
                    <div class="form-field">
                        <label for="product_display_style">Gaya Tampilan Produk</label>
                        <select name="product_display_style" id="product_display_style">
                            <option value="image_text" <?php selected($item_to_edit->product_display_style ?? 'image_text', 'image_text'); ?>>Gambar & Teks</option>
                            <option value="image_only" <?php selected($item_to_edit->product_display_style ?? '', 'image_only'); ?>>Gambar Saja</option>
                            <option value="text_only" <?php selected($item_to_edit->product_display_style ?? '', 'text_only'); ?>>Teks Saja</option>
                        </select>
                    </div>
                     <div class="form-field">
                        <label for="product_display_mode">Tata Letak Tampilan Produk</label>
                        <select name="product_display_mode" id="product_display_mode">
                            <option value="grid" <?php selected($item_to_edit->product_display_mode ?? 'grid', 'grid'); ?>>Grid</option>
                            <option value="list" <?php selected($item_to_edit->product_display_mode ?? '', 'list'); ?>>Daftar (List)</option>
                        </select>
                    </div>
                    
                    <?php submit_button($edit_mode ? 'Perbarui Kategori' : 'Tambah Kategori'); ?>
                </form>

                <?php if ($edit_mode && !empty($selected_products_obj)): ?>
                <hr>
                <div class="col-wrap">
                    <h3>Urutkan Produk <span class="spinner"></span></h3>
                    <p>Geser produk di bawah ini untuk mengatur urutannya. Urutan disimpan otomatis.</p>
                    <ul id="wppob-sortable-products">
                        <?php foreach($selected_products_obj as $product): ?>
                            <li id="product-<?php echo $product->get_id(); ?>" data-id="<?php echo $product->get_id(); ?>">
                                <span class="dashicons dashicons-move"></span>
                                <?php echo esc_html($product->get_name()); ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <h3>Struktur Kategori <span class="spinner"></span></h3>
                <div id="wppob-category-organizer">
                    <?php if (function_exists('wppob_display_sortable_categories')) { wppob_display_sortable_categories($categories_tree); } ?>
                </div>
                <p class="description">Gunakan ikon <span class="dashicons dashicons-minus"></span> untuk membuka/menutup sub-kategori. Perubahan urutan kategori disimpan otomatis.</p>
                <hr>
                <h3>Pengaturan Grid Global</h3>
                <form method="post" action="">
                    <?php wp_nonce_field('wppob_save_grid_nonce', '_grid_nonce'); ?>
                    <div class="form-field">
                        <label for="wppob_grid_columns">Jumlah Kategori per Baris</label>
                        <input type="number" name="wppob_grid_columns" id="wppob_grid_columns" value="<?php echo esc_attr(get_option('wppob_grid_columns', 4)); ?>" min="1" max="8">
                    </div>
                    <?php submit_button('Simpan Pengaturan Grid'); ?>
                </form>
            </div>
        </div>
    </div>
</div>