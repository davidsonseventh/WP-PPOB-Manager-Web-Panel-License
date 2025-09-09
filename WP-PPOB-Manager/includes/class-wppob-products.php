<?php
defined('ABSPATH') || exit;

class WPPPOB_Products {
    public function __construct() {
        add_action('wppob_hourly_sync', [$this, 'sync_from_api']);
    }

   public function sync_from_api() {
    if (!function_exists('wc_get_product_id_by_sku')) {
        return new WP_Error('woocommerce_not_found', 'WooCommerce tidak aktif.');
    }

    $api = new WPPPOB_API();
    $list = $api->get_price_list();

    if (is_wp_error($list)) { return $list; }
    if (empty($list['data'])) { return new WP_Error('no_products', 'API tidak mengembalikan data produk.'); }

    $profit_type = get_option('wppob_profit_type', 'fixed');
    $profit_amount = (float) get_option('wppob_profit_amount', 0);

    foreach ($list['data'] as $item) {
        if ($item['buyer_product_status'] === false) continue;

        $sku = 'wppob-' . sanitize_key($item['buyer_sku_code']);
        $product_id = wc_get_product_id_by_sku($sku);
        $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

        if ($product) {
            if (!$product_id) {
                $product->set_sku($sku);
            }

            $base_price = (float) $item['price'];
            $sale_price = ($profit_type === 'fixed') ? $base_price + $profit_amount : $base_price + ($base_price * ($profit_amount / 100));
            
            $product->set_name(sanitize_text_field($item['product_name']));
            $product->set_virtual(true);
            $product->set_catalog_visibility('visible');
            $product->set_regular_price($sale_price);
            $product->set_status('publish');
            
            $brand = sanitize_text_field($item['brand']);
            $category_name = sanitize_text_field($item['category']);
            $product->update_meta_data('_wppob_base_price', $base_price);
            $product->update_meta_data('_wppob_brand', $brand);
            $product->update_meta_data('_wppob_category', $category_name);

            $term_id = $this->get_or_create_term($category_name, 'product_cat');
            if ($term_id) {
                $product->set_category_ids([$term_id]);
            }

            $saved_product_id = $product->save();

            // PERUBAHAN DI SINI: Gunakan Nama Produk untuk mencocokkan gambar
            if ($saved_product_id && !has_post_thumbnail($saved_product_id)) {
                // Mengirim nama produk (lebih deskriptif) sebagai sumber pencocokan
                wppob_set_product_image_from_brand($saved_product_id, $product->get_name());
            }
        }
    }
    update_option('wppob_last_sync', current_time('mysql'));
    return true;
}



public function bulk_update_prices() {
        $profit_type = get_option('wppob_profit_type', 'fixed');
        $profit_amount = (float) get_option('wppob_profit_amount', 0);

        $products = wc_get_products([
            'limit' => -1,
            'meta_key' => '_wppob_base_price',
        ]);

        if (empty($products)) {
            return;
        }

        foreach ($products as $product) {
            $base_price = (float) $product->get_meta('_wppob_base_price');
            if ($base_price <= 0) {
                continue;
            }

            $new_sale_price = ($profit_type === 'fixed') 
                ? $base_price + $profit_amount 
                : $base_price + ($base_price * ($profit_amount / 100));
            
            $product->set_regular_price($new_sale_price);
            $product->save();
        }
    }



    private function get_or_create_term($term_name, $taxonomy) {
        $term = get_term_by('name', $term_name, $taxonomy);
        if (!$term) {
            $term_data = wp_insert_term($term_name, $taxonomy);
            return is_wp_error($term_data) ? null : $term_data['term_id'];
        }
        return $term->term_id;
    }
}