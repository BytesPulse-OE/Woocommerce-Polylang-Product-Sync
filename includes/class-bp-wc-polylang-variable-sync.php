<?php
if (!defined('ABSPATH')) exit;

/*
 * Συγχρονισμός τύπου προϊόντος (product type) όταν δημιουργείται μια νέα μετάφραση με Polylang.
 *
 * Τι κάνει:
 * - Όταν δημιουργείται νέο προϊόν (π.χ. μετάφραση), στέλνει AJAX αίτημα να μάθει τον τύπο του πρωτότυπου προϊόντος
 * - Το JS (`product-type-sync.js`) ορίζει αυτόματα τον ίδιο τύπο (π.χ. simple, variable, etc.)
 */

class BP_WC_Polylang_Product_Type_Sync {

    public function __construct() {
        add_action('admin_enqueue_scripts', array($this, 'enqueue_product_type_script'));
		// AJAX route για να πάρουμε τον τύπο προϊόντος από τον πρωτότυπο
        add_action('wp_ajax_bp_get_product_type', array($this, 'ajax_get_product_type'));
    }

    // Επισυνάπτει JS μόνο όταν ανοίγουμε νέο προϊόν ΚΑΙ έχουμε from_post (δηλ. μεταφρασμένο)
	public function enqueue_product_type_script($hook) {
        if ($hook !== 'post-new.php') return;

        if (!isset($_GET['from_post'])) return;

        wp_enqueue_script(
            'bp-polylang-product-type-sync',
            plugin_dir_url(__FILE__) . '../assets/js/product-type-sync.js',
            array('jquery'),
            '1.0',
            true
        );

        // Περνάμε το from_post (ID του πρωτότυπου προϊόντος) στο script
		wp_localize_script('bp-polylang-product-type-sync', 'bp_product_type_sync_data', array(
            'ajax_url'  => admin_url('admin-ajax.php'),
            'from_post' => intval($_GET['from_post'])
        ));
    }

    // AJAX handler που επιστρέφει τον τύπο του πρωτότυπου προϊόντος
	public function ajax_get_product_type() {
        if (!isset($_POST['from_post'])) {
            wp_send_json_error('Missing original ID');
        }

        $original_id = intval($_POST['from_post']);
        $product = wc_get_product($original_id);

        if (!$product) {
            wp_send_json_error('Invalid product');
        }

        // Επιστρέφει τον τύπο (π.χ. simple, variable, grouped κ.λπ.)
		wp_send_json_success(array(
            'type' => $product->get_type()
        ));
    }
}

new BP_WC_Polylang_Product_Type_Sync();