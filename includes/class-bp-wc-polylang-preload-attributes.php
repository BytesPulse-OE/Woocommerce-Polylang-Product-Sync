<?php
// includes/class-bp-wc-polylang-preload-attributes.php
if (!defined('ABSPATH')) exit;

/*
 * Προφόρτωση χαρακτηριστικών (attributes) προϊόντος όταν δημιουργούμε μετάφραση προϊόντος.
 *
 * Όταν δημιουργείται ένα νέο προϊόν μέσω "Μετάφραση με Polylang" (δηλ. υπάρχει ?from_post=...),
 * τότε το σύστημα ανιχνεύει τα χαρακτηριστικά του πρωτότυπου προϊόντος και τα "αντιγράφει"
 * στη νέα μετάφραση πριν προστεθούν χειροκίνητα.
 */

class BP_WC_Polylang_Preload_Attributes {
    public function __construct() {
        add_action('load-post-new.php', [$this, 'maybe_preload_attributes_meta']);
    }

    // Ελέγχει αν είμαστε στη σελίδα δημιουργίας μεταφρασμένου προϊόντος και προετοιμάζει injection
	public function maybe_preload_attributes_meta() {
        if (!is_admin() || !isset($_GET['from_post']) || !isset($_GET['post_type']) || $_GET['post_type'] !== 'product') return;

        // Όταν εμφανιστεί το φορμάρισμα μετά τον τίτλο, θα γίνει injection των attributes
		add_action('edit_form_after_title', [$this, 'inject_preloaded_attributes']);
    }

    // Εκτελεί την πραγματική αντιγραφή των attributes από το πρωτότυπο προϊόν
	public function inject_preloaded_attributes() {
        global $post;

        // Εξασφαλίζουμε ότι βρισκόμαστε σε προϊόν
		if (!$post || $post->post_type !== 'product') return;

        // Αν υπάρχουν ήδη attributes, δεν κάνουμε τίποτα (προστασία από overwrite)
        $existing = get_post_meta($post->ID, '_product_attributes', true);
        if (!empty($existing)) return;

        $from_post = intval($_GET['from_post']);
        $original_product = wc_get_product($from_post);
        if (!$original_product) return;

        $attributes_meta = [];
        $current_lang = function_exists('pll_current_language') ? pll_current_language() : '';

        // Επεξεργαζόμαστε κάθε χαρακτηριστικό (attribute) του πρωτότυπου
		foreach ($original_product->get_attributes() as $attribute) {
            if (!$attribute->is_taxonomy()) continue;

            $taxonomy = $attribute->get_name();
			// Παίρνουμε τους όρους (terms) που είναι συσχετισμένοι με αυτό το attribute
            $term_ids = wp_get_post_terms($from_post, $taxonomy, ['fields' => 'ids']);
            $translated_ids = [];
           
			// Μεταφράζουμε κάθε όρο στη γλώσσα της μετάφρασης
			foreach ($term_ids as $term_id) {
                $translated_id = function_exists('pll_get_term') ? pll_get_term($term_id, $current_lang) : $term_id;
                if ($translated_id) $translated_ids[] = $translated_id;
            }

            // Παίρνουμε τα slugs των μεταφρασμένων terms
			$translated_slugs = [];
            foreach ($translated_ids as $tid) {
                $term = get_term($tid, $taxonomy);
                if ($term && !is_wp_error($term)) {
                    $translated_slugs[] = $term->slug;
                }
            }

            // Δημιουργούμε το meta array για το attribute
			$attributes_meta[$taxonomy] = [
                'name'         => $taxonomy,
                'value'        => implode('|', $translated_slugs),
                'position'     => 0,
                'is_visible'   => $attribute->get_visible() ? 1 : 0,
                'is_variation' => $attribute->get_variation() ? 1 : 0,
                'is_taxonomy'  => 1
            ];
        }

        // Ενημερώνουμε το νέο προϊόν με τα μεταφρασμένα attributes
		if (!empty($attributes_meta)) {
            update_post_meta($post->ID, '_product_attributes', $attributes_meta);
        }
    }
}

new BP_WC_Polylang_Preload_Attributes();