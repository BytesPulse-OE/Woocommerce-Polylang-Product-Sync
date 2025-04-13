<?php
if (!defined('ABSPATH')) exit;

/*
 * Συγχρονισμός των όρων χαρακτηριστικών (terms of attributes) μεταξύ Polylang μεταφράσεων.
 *
 * Όταν αποθηκεύεται ένα μεταφρασμένο προϊόν:
 * - Βρίσκει τους όρους του πρωτότυπου προϊόντος (π.χ. Color: Red)
 * - Βρίσκει τις αντίστοιχες μεταφράσεις αυτών των όρων
 * - Τους συσχετίζει με το μεταφρασμένο προϊόν
 * - Αναδημιουργεί σωστά τα WC Product Attributes ώστε να φαίνονται στο admin και frontend
 */

class BP_WC_Polylang_Terms_Sync {

    public function __construct() {
        add_action('pll_save_post', array($this, 'sync_terms_backend'), 20, 2);
    }

    // Συγχρονίζει τους όρους από το πρωτότυπο προϊόν στο μεταφρασμένο προϊόν
	public function sync_terms_backend($post_id, $post) {
        // Αγνοούμε autosaves και μη προϊόντα
		if (wp_is_post_autosave($post_id) || get_post_type($post_id) !== 'product') return;

        $current_lang = function_exists('pll_get_post_language') ? pll_get_post_language($post_id) : '';
        $default_lang = function_exists('pll_default_language') ? pll_default_language() : '';

        // Αν το προϊόν είναι ήδη στην κύρια γλώσσα, δεν χρειάζεται συγχρονισμός
		if ($current_lang === $default_lang) return;

        // Παίρνουμε το ID του πρωτότυπου προϊόντος
		$original_id = pll_get_post($post_id, $default_lang);
        if (!$original_id) return;

        $original_product = wc_get_product($original_id);
        $translated_product = wc_get_product($post_id);
        if (!$original_product || !$translated_product) return;

        $attributes = $original_product->get_attributes();
        $translated_attributes = array();

        // Για κάθε χαρακτηριστικό του πρωτότυπου προϊόντος
		foreach ($attributes as $attribute) {
            if (!$attribute->is_taxonomy()) continue;

            $taxonomy = $attribute->get_name();
            // Παίρνουμε τους όρους που έχουν επιλεγεί στο πρωτότυπο
			$term_ids = wp_get_post_terms($original_id, $taxonomy, array('fields' => 'ids'));
            $translated_ids = array();

            // Μεταφράζουμε κάθε όρο στη γλώσσα της μετάφρασης
			foreach ($term_ids as $term_id) {
                $translated_id = pll_get_term($term_id, $current_lang);
                if ($translated_id) {
                    $translated_ids[] = $translated_id;
                }
            }

            if (!empty($translated_ids)) {
                // Ενημερώνουμε το προϊόν με τους μεταφρασμένους όρους
                wp_set_object_terms($post_id, $translated_ids, $taxonomy);

                // Παίρνουμε τα ονόματα των όρων για να τα εμφανίζει σωστά το WooCommerce
                $term_names = array();
                foreach ($translated_ids as $tid) {
                    $term = get_term($tid, $taxonomy);
                    if ($term && !is_wp_error($term)) {
                        $term_names[] = $term->name;
                    }
                }

                // Παίρνουμε το ID του attribute από WooCommerce (όχι από Polylang)
                $attribute_tax_id = wc_attribute_taxonomy_id_by_name(wc_sanitize_taxonomy_name($taxonomy));

                // Δημιουργούμε ένα WC_Product_Attribute αντικείμενο για να περαστούν σωστά τα options
                $wc_attr = new WC_Product_Attribute();
                $wc_attr->set_id($attribute_tax_id);
                $wc_attr->set_name($taxonomy);
                $wc_attr->set_options($term_names);
                $wc_attr->set_position(0);
                $wc_attr->set_visible(true);
                $wc_attr->set_variation(true);

                $translated_attributes[] = $wc_attr;
            }
        }

        if (!empty($translated_attributes)) {
            $translated_product->set_attributes($translated_attributes);
            $translated_product->save();
        }
    }
}

new BP_WC_Polylang_Terms_Sync();