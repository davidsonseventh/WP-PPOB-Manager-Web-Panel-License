<?php
defined('ABSPATH') || exit;
// Variabel $categories_tree dikirim dari fungsi render_custom_view()

if (empty($categories_tree)) {
    echo '<p>' . __('Belum ada kategori yang dikonfigurasi.', 'wp-ppob') . '</p>';
    return;
}
?>
<div id="wppob-app-container" class="wppob-custom-view-container">
    <div class="wppob-app-header">
        <button id="wppob-app-back-btn" style="display: none;">&larr;</button>
        <h2 id="wppob-app-title"><?php _e('Pilih Layanan', 'wp-ppob'); ?></h2>
    </div>
    <div id="wppob-app-content" class="wppob-category-grid" style="--wppob-grid-columns: <?php echo esc_attr(get_option('wppob_grid_columns', 4)); ?>;">
        <?php
        foreach ($categories_tree as $category_item) {
            $cat = $category_item['details'];
            $image_url = $cat->image_id ? wp_get_attachment_image_url($cat->image_id, 'thumbnail') : wc_placeholder_img_src();
            $style_attribute = "width: {$cat->image_size_px}px; height: {$cat->image_size_px}px; border-radius: {$cat->border_radius}%;";

            echo '<div class="wppob-category-item" data-id="' . esc_attr($cat->id) . '" data-name="' . esc_attr($cat->name) . '">';
            if ($cat->display_style !== 'text_only') {
                echo '<div class="wppob-category-image-wrapper" style="' . esc_attr($style_attribute) . '">';
                echo '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($cat->name) . '">';
                echo '</div>';
            }
            if ($cat->display_style !== 'image_only') {
                echo '<span class="wppob-category-name">' . esc_html($cat->name) . '</span>';
            }
            echo '</div>';
        }
        ?>
    </div>
    <div id="wppob-app-loader" style="display: none;"><div class="wppob-loader-inline"></div></div>
</div>