<?php defined('ABSPATH') || exit; ?>

<div id="wppob-app-view" class="wppob-app-container">
    
    <div id="wppob-category-grid">
        <h3><?php _e('Pilih Kategori Layanan', 'wp-ppob'); ?></h3>
        <div class="wppob-grid">
            <?php
            $categories = get_terms(['taxonomy' => 'product_cat', 'hide_empty' => true]);
            foreach ($categories as $category) :
                $thumbnail_id = get_term_meta($category->term_id, 'thumbnail_id', true);
                $image = $thumbnail_id ? wp_get_attachment_url($thumbnail_id) : wc_placeholder_img_src();
            ?>
            <div class="wppob-grid-item" data-category-slug="<?php echo esc_attr($category->slug); ?>" data-category-name="<?php echo esc_attr($category->name); ?>">
                <img src="<?php echo esc_url($image); ?>" alt="<?php echo esc_attr($category->name); ?>">
                <span><?php echo esc_html($category->name); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <div id="wppob-product-view" style="display:none;">
        <button id="wppob-back-to-categories">&larr; <?php _e('Kembali', 'wp-ppob'); ?></button>
        <h3 id="wppob-product-view-title"></h3>
        
        <div class="form-group">
            <label for="wppob-customer-no-app"><?php _e('Nomor Tujuan / ID Pelanggan', 'wp-ppob'); ?></label>
            <input type="tel" id="wppob-customer-no-app" placeholder="<?php _e('Masukkan nomor tujuan...', 'wp-ppob'); ?>" required>
        </div>
        
        <div id="wppob-product-list" class="wppob-grid">
            </div>
    </div>

</div>