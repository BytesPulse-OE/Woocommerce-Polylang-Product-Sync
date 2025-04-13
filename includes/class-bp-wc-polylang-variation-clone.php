<?php

// Κλάση που χειρίζεται την κλωνοποίηση παραλλαγών WooCommerce κατά τη μετάφραση προϊόντων με Polylang
class BP_WC_Polylang_Variation_Clone {
	
    public function __construct() {
		// Δηλώνει AJAX route για τη λήψη των παραλλαγών του πρωτότυπου προϊόντος
        add_action('wp_ajax_bp_wc_clone_variations', [$this, 'handle_clone_request']);
		
		// Δηλώνει AJAX route για τη σήμανση της παραλλαγής ως "κλωνοποιημένη" από συγκεκριμένο ID
        add_action('wp_ajax_bp_wc_mark_cloned_variation', [$this, 'mark_cloned_variation']);
		
		// Φορτώνει το σχετικό JS στο admin panel όταν ανοίγουμε προϊόν
        add_action('admin_enqueue_scripts', [$this, 'enqueue_script']);
    }

    public function enqueue_script() {
        global $post;

        if (!isset($post) || get_post_type($post) !== 'product') {
            return;
        }

        $js_url = plugins_url('../assets/js/variation-clone.js', __FILE__);

        wp_enqueue_script(
            'bp-wc-variation-clone',
            $js_url,
            ['jquery'],
            time() + rand(10, 100) * 1000,
            true
        );

        wp_localize_script('bp-wc-variation-clone', 'bp_wc_variation_clone', [
            'nonce' => wp_create_nonce('bp_wc_polylang_variation_clone'),
            'original_product_id' => isset($_GET['from_post']) ? intval($_GET['from_post']) : 0,
        ]);
    }

    // Επιστρέφει τις παραλλαγές του πρωτότυπου προϊόντος μέσω AJAX για χρήση στο backend
	public function handle_clone_request() {
        check_ajax_referer('bp_wc_polylang_variation_clone', 'nonce');

        $original_id = intval($_POST['original_product_id'] ?? 0);
        
        if (!$original_id || get_post_type($original_id) !== 'product') {
            wp_send_json_error(['message' => 'Invalid original product']);
        }

        $product = wc_get_product($original_id);

        if (!$product || !$product->is_type('variable')) {
            wp_send_json_error(['message' => 'Original product is not variable']);
        }

        $variations = [];
        $variation_ids = $product->get_children();

        foreach ($variation_ids as $variation_id) {
            $variation = wc_get_product($variation_id);
            if (!$variation) continue;

            $term = null;
            $attribute_data = [];

            // Λαμβάνουμε μεταφρασμένες τιμές όρων αν υπάρχουν
			foreach ($variation->get_attributes() as $taxonomy => $slug) {
                if (empty($slug)) continue;

                $taxonomy_clean = str_replace('attribute_', '', $taxonomy);

                if (!taxonomy_exists($taxonomy_clean)) continue;

                global $wpdb;
                $term_id = $wpdb->get_var(
                    $wpdb->prepare("
                        SELECT t.term_id FROM {$wpdb->terms} t
                        JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                        WHERE tt.taxonomy = %s AND t.slug = %s
                        LIMIT 1
                    ", $taxonomy_clean, $slug)
                );

                $term = null;
                $lang = function_exists('pll_current_language') ? pll_current_language() : '';
                
                if(function_exists('pll_get_term'))
                    $term = get_term(pll_get_term($term_id, $lang));

                $attribute_data[] = [
                    'taxonomy'  => $taxonomy_clean,
                    'slug'      => isset($term) ? $term->slug : $slug,
                    'name'      => isset($term) ? $term->name : $slug,
                ];    
            }

            // Επιστροφή των δεδομένων κάθε παραλλαγής
			$variations[] = [
                'id'             => $variation_id,
                'attributes'     => $attribute_data,
                'price'          => $variation->get_price(),
                'regular_price'  => $variation->get_regular_price(),
                'sale_price'     => $variation->get_sale_price(),
                'stock_qty'      => $variation->get_stock_quantity(),
                'manage_stock'   => $variation->managing_stock(),
                'weight'         => $variation->get_weight(),
                'length'         => $variation->get_length(),
                'width'          => $variation->get_width(),
                'height'         => $variation->get_height(),
                'image' => [
                    'id'  => $variation->get_image_id(),
                    'url' => wp_get_attachment_url($variation->get_image_id()),
                ],
                'sku'            => $variation->get_sku(),
                'enabled'        => $variation->get_status() === 'publish',
            ];
        }
    
        wp_send_json_success([
            'variations' => $variations,
        ]);
    }

    // Σημειώνει ποια παραλλαγή είναι "κλωνοποιημένη" από ποια (για μελλοντικό συγχρονισμό)
	public function mark_cloned_variation() {
        check_ajax_referer('bp_wc_polylang_variation_clone', 'nonce');

        $new_id = intval($_POST['new_id'] ?? 0);
        $original_id = intval($_POST['original_id'] ?? 0);

        if (!$new_id || !$original_id) {
            wp_send_json_error(['message' => 'Invalid variation IDs']);
        }

        update_post_meta($new_id, '_original_variation_id', $original_id);
        wp_send_json_success(['message' => 'Mapped successfully']);
    }
}

new BP_WC_Polylang_Variation_Clone();