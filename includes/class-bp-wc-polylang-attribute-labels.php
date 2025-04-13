<?php
if (!defined('ABSPATH')) exit;

/**
 * Πολυγλωσσική υποστήριξη για τις ετικέτες χαρακτηριστικών (attribute labels) του WooCommerce.
 * 
 * Αυτό το component:
 * - Καταγράφει τις ετικέτες χαρακτηριστικών για μετάφραση στο Polylang
 * - Επιστρέφει την μεταφρασμένη ετικέτα σε κάθε προβολή προϊόντος (frontend ή admin)
 */
class BP_WC_Polylang_Attribute_Labels {
    public function __construct() {
        add_action('init', array($this, 'register_attribute_label_strings'));
        add_filter('woocommerce_attribute_label', array($this, 'translate_attribute_label'), 10, 2);
    }

    /**
     * Καταχωρεί όλα τα attribute labels του WooCommerce στο Polylang για μετάφραση.
     * Αυτό γίνεται μόνο αν υπάρχει ενεργό το Polylang και η συνάρτηση `pll_register_string`.
     */
    public function register_attribute_label_strings() {
        if (!function_exists('pll_register_string')) return;

        global $wpdb;
		// Παίρνουμε όλες τις ετικέτες χαρακτηριστικών από τη βάση
        $attribute_taxonomies = $wpdb->get_results("SELECT attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");

        foreach ($attribute_taxonomies as $attr) {
            $label = $attr->attribute_label;
            if ($label) {
				// Καταχωρούμε κάθε ετικέτα για μετάφραση στο Polylang
                pll_register_string($label, $label, 'WooCommerce Attributes');
            }
        }
    }

    /**
     * Επιστρέφει τη μεταφρασμένη εκδοχή της ετικέτας χαρακτηριστικού.
     *
     * @param string $label Η αρχική ετικέτα
     * @param string $name  Το internal name του χαρακτηριστικού (π.χ. pa_color)
     * @return string Η μεταφρασμένη (ή η αρχική) ετικέτα
     */
    public function translate_attribute_label($label, $name) {
        if (function_exists('pll__')) {
            return pll__($label); // Επιστρέφει τη μεταφρασμένη ετικέτα αν υπάρχει
        }
        return $label; // Αν όχι, επιστρέφει την αρχική
    }
}

new BP_WC_Polylang_Attribute_Labels();