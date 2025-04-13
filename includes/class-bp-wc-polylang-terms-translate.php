<?php

// Ασφαλή φόρτωση του αρχείου
if (!defined('ABSPATH')) exit;

class BP_Polylang_Attribute_Sync {

    public function __construct() {
        // Δηλώνουμε όλα τα attribute taxonomies του WooCommerce ως μεταφράσιμα στο Polylang
        add_filter('pll_get_taxonomies', array($this, 'register_attribute_taxonomies_for_translation'), 10, 2);

        // Κλωνοποιεί μεταδεδομένα όταν δημιουργείται μια μετάφραση όρου
        add_action('pll_save_term', array($this, 'clone_term_meta_on_translation'), 10, 3);
    }

    // Δηλώνει δυναμικά όλα τα WooCommerce attribute taxonomies ως μεταφράσιμα
    public function register_attribute_taxonomies_for_translation($taxonomies, $is_settings) {
        if (!function_exists('wc_get_attribute_taxonomies')) return $taxonomies;

        $attributes = wc_get_attribute_taxonomies();
        if (!$attributes || !is_array($attributes)) return $taxonomies;

        foreach ($attributes as $attribute) {
            $taxonomy = 'pa_' . $attribute->attribute_name;

            // Αν δεν είναι ήδη καταχωρημένο στο Polylang, το προσθέτουμε
            if (!in_array($taxonomy, $taxonomies)) {
                $taxonomies[] = $taxonomy;
            }
        }

        return $taxonomies;
    }

    // Κλωνοποιεί meta δεδομένα του όρου κατά τη δημιουργία της μετάφρασης
    public function clone_term_meta_on_translation($term_id, $term, $lang) {
        if (!function_exists('pll_get_term') || !function_exists('pll_get_term_language')) return;

        $default_lang = pll_default_language();
        $current_lang = pll_get_term_language($term_id);

        // Δεν κάνουμε τίποτα αν είμαστε ήδη στη βασική γλώσσα
        if ($current_lang === $default_lang) return;

        $original_id = pll_get_term($term_id, $default_lang);
        if (!$original_id) return;

        // Αντιγραφή όλων των meta από τον original όρο
        $meta_keys = get_term_meta($original_id);
        if (!empty($meta_keys)) {
            foreach ($meta_keys as $key => $values) {
                foreach ($values as $val) {
                    update_term_meta($term_id, $key, maybe_unserialize($val));
                }
            }
        }
    }
}

new BP_Polylang_Attribute_Sync();

/*
 * ----------------------------------------
 * Βοηθητικές συναρτήσεις για το frontend
 * ----------------------------------------
 */

// Επιστρέφει το ID του μεταφρασμένου όρου
function bp_get_translated_term_id($term_id, $lang = null) {
    if (!function_exists('pll_get_term')) return $term_id;
    if (!$lang) $lang = pll_current_language();

    $translated = pll_get_term($term_id, $lang);
    return $translated ?: $term_id;
}

// Επιστρέφει το όνομα του μεταφρασμένου όρου
function bp_get_translated_term_name($term_id, $lang = null) {
    $translated_id = bp_get_translated_term_id($term_id, $lang);
    $term = get_term($translated_id);
    return ($term && !is_wp_error($term)) ? $term->name : '';
}

// Επιστρέφει το slug του μεταφρασμένου όρου
function bp_get_translated_term_slug($term_id, $lang = null) {
    $translated_id = bp_get_translated_term_id($term_id, $lang);
    $term = get_term($translated_id);
    return ($term && !is_wp_error($term)) ? $term->slug : '';
}

/*
 * ----------------------------------------
 * Εργαλείο για μαζικό συγχρονισμό όρων με Polylang
 * ----------------------------------------
 */
add_action('admin_init', 'bp_sync_existing_terms_with_polylang');

function bp_sync_existing_terms_with_polylang() {
    if (!is_admin() || !current_user_can('manage_options')) return;

    // Εκτελείται μόνο αν υπάρχει παράμετρος στο URL: ?action=bp_sync_terms
    if (!isset($_GET['action']) || $_GET['action'] !== 'bp_sync_terms') return;

    if (!function_exists('wc_get_attribute_taxonomies') || !function_exists('pll_set_term_language')) return;

    $attributes = wc_get_attribute_taxonomies();
    if (!$attributes) return;

    $default_lang = pll_default_language();
    $synced = [];

    foreach ($attributes as $attr) {
        $taxonomy = 'pa_' . $attr->attribute_name;

        $terms = get_terms([
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
        ]);

        foreach ($terms as $term) {
            $current_lang = pll_get_term_language($term->term_id);

            // Αν δεν έχει γλώσσα καταχωρημένη
            if (!$current_lang) {
                pll_set_term_language($term->term_id, $default_lang);
                $synced[] = $term->name . ' (' . $taxonomy . ')';
            }
        }
    }

    // Προβολή αποτελεσμάτων στο admin panel (μπορείς να αφαιρέσεις αν θες silent mode)
    if (!empty($synced)) {
        echo '<div class="notice notice-success"><p><strong>Όροι συγχρονίστηκαν στη γλώσσα:</strong> ' . esc_html($default_lang) . '</p>';
        echo '<ul>';
        foreach ($synced as $s) {
            echo '<li>' . esc_html($s) . '</li>';
        }
        echo '</ul></div>';
    } else {
        echo '<div class="notice notice-info"><p>Όλοι οι όροι είναι ήδη καταχωρημένοι στη γλώσσα Polylang.</p></div>';
    }
}