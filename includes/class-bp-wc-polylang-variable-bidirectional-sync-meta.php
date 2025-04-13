<?php
if (!defined('ABSPATH')) exit;

/*
 * Διπλής κατεύθυνσης συγχρονισμός μεταδεδομένων παραλλαγών μεταβλητών προϊόντων
 * μεταξύ Polylang μεταφράσεων.
 *
 * Συγκεκριμένα:
 * - Όταν αποθηκεύεται μεταφρασμένο προϊόν, συγχρονίζει τις παραλλαγές του προς το πρωτότυπο
 * - Όταν αποθηκεύεται το πρωτότυπο, συγχρονίζει τις παραλλαγές προς τις μεταφράσεις
 * - Ταυτοποίηση παραλλαγών γίνεται με βάση το custom meta `_original_variation_id`
 */

class BP_WC_Polylang_Variable_Bidirectional_Sync {

    public function __construct() {
        add_action('save_post_product', [$this, 'sync_variable_product'], 20, 3);
    }

    // Εκτελείται κατά την αποθήκευση προϊόντος
	public function sync_variable_product($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || get_post_type($post_id) !== 'product') return;

        $product = wc_get_product($post_id);
        if (!$product || !$product->is_type('variable')) return;

        if (!function_exists('pll_get_post_translations') || !function_exists('pll_get_post_language') || !function_exists('pll_default_language')) return;

        $current_lang = pll_get_post_language($post_id);
        $default_lang = pll_default_language();

        // Αν αποθηκεύεται μετάφραση, συγχρονίζει προς το πρωτότυπο
        if ($current_lang !== $default_lang) {
            $translations = pll_get_post_translations($post_id);
            $original_id = $translations[$default_lang] ?? null;

            if ($original_id && $original_id != $post_id) {
                $this->sync_variation_meta($post_id, $original_id);
                bp_debug_log("Sync από μετάφραση προς πρωτότυπο: $post_id → $original_id");
            }

            return; // Μην προχωρήσεις με το αντίστροφο
        }

        // Αν αποθηκεύεται το πρωτότυπο, συγχρονίζει προς τις μεταφράσεις
        $translations = pll_get_post_translations($post_id);

        foreach ($translations as $lang => $translated_id) {
            if ($translated_id == $post_id) continue;
            $this->sync_variation_meta($post_id, $translated_id);
            bp_debug_log("🔁 Sync από πρωτότυπο προς μετάφραση: $post_id → $translated_id");
        }
    }

    // Εκτελεί τον πραγματικό συγχρονισμό των μεταδεδομένων παραλλαγών
	private function sync_variation_meta($from_product_id, $to_product_id) {
        $from_vars = get_posts([
            'post_type' => 'product_variation',
            'post_parent' => $from_product_id,
            'numberposts' => -1
        ]);

        foreach ($from_vars as $from_var) {
            $from_id = $from_var->ID;

            // Βρίσκουμε αντίστοιχη παραλλαγή στον στόχο βάσει του _original_variation_id
			$matching = get_posts([
                'post_type' => 'product_variation',
                'post_parent' => $to_product_id,
                'meta_key' => '_original_variation_id',
                'meta_value' => $from_id,
                'numberposts' => 1,
                'fields' => 'ids'
            ]);

            if (!empty($matching)) {
                $to_id = $matching[0];

                // Πεδία προς συγχρονισμό
				$meta_keys = [
                    '_regular_price', '_sale_price', '_price',
                    '_sku', '_thumbnail_id',
                    '_manage_stock', '_stock',
                    '_weight', '_length', '_width', '_height'
                ];

                foreach ($meta_keys as $key) {
                    $val = get_post_meta($from_id, $key, true);
                    update_post_meta($to_id, $key, $val);
                }

                bp_debug_log("Παραλλαγή συγχρονίστηκε: $from_id → $to_id");
            }
        }
    }
}

new BP_WC_Polylang_Variable_Bidirectional_Sync();